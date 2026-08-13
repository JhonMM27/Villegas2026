<?php

/**
 * Servicio de Movimientos (Kardex Valorizado).
 *
 * Núcleo central del sistema de inventario. Todas las operaciones
 * que afectan stock y costo pasan por este servicio.
 *
 * Responsabilidades:
 * - Registrar ingresos (compras, producto final de preparadas)
 * - Registrar salidas (ventas, insumos de preparadas)
 * - Calcular el Costo Promedio Ponderado (CPP) móvil
 * - Recalcular kardex en cascada desde un movimiento corregido
 * - Actualizar saldos en la tabla `productos`
 *
 * Fórmulas CPP:
 * - ENTRADA: costo_nuevo = (valor_anterior + costo_entrada) / stock_nuevo
 * - SALIDA:  costo_nuevo = costo_actual (no cambia)
 */

namespace App\Services;

use App\Models\Movimiento;
use App\Models\Producto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MovimientoService
{
    // ─── Constantes de tipos de movimiento ───────────────────

    /** Tipo: Ingreso por compra */
    const TIPO_COMPRA = 'COMPRA';

    /** Tipo: Salida por venta */
    const TIPO_VENTA = 'VENTA';

    /** Tipo: Salida de insumos para producción */
    const TIPO_PREPARADA_SALIDA = 'PREPARADA_SALIDA';

    /** Tipo: Ingreso de producto final de producción */
    const TIPO_PREPARADA_INGRESO = 'PREPARADA_INGRESO';

    /** Tipo: Reversión por anulación de compra */
    const TIPO_ANULACION_COMPRA = 'ANULACION_COMPRA';

    /** Tipo: Reversión por anulación de venta (devuelve stock al almacén) */
    const TIPO_ANULACION_VENTA = 'ANULACION_VENTA';

    /** Tipo: Reversión por anulación de preparada (devuelve insumos) */
    const TIPO_ANULACION_PREPARADA = 'ANULACION_PREPARADA';

    /** Tipo: Salida por préstamo otorgado (PA) o devolución a tercero (DA) */
    const TIPO_PRESTAMO_SALIDA = 'PRESTAMO_SALIDA';

    /** Tipo: Ingreso por préstamo recibido (PD) o devolución recibida (DD) */
    const TIPO_PRESTAMO_INGRESO = 'PRESTAMO_INGRESO';

    /** Tipo: Reversión por anulación de préstamo */
    const TIPO_ANULACION_PRESTAMO = 'ANULACION_PRESTAMO';

    /** Tipo: Entrada por ajuste de inventario (conteo físico > stock sistema) */
    const TIPO_AJUSTE_ENTRADA = 'AJUSTE_ENTRADA';

    /** Tipo: Salida por ajuste de inventario (conteo físico < stock sistema) */
    const TIPO_AJUSTE_SALIDA = 'AJUSTE_SALIDA';

    /** Punto de control absoluto importado del sistema anterior. */
    const TIPO_APERTURA_LEGADO = 'APERTURA_LEGADO';

    // ─── Constantes de tipos de transacción ────────────────

    const TRANSACCION_AJUSTES = 'ajustes';

    const TRANSACCION_APERTURA_LEGADO = 'apertura_legacy';

    const TRANSACCION_ID_APERTURA_LEGADO = 20260531;

    // ─── Métodos públicos ───────────────────────────────────

    /**
     * Bloquea productos en un orden estable para evitar actualizaciones
     * perdidas y reducir el riesgo de deadlocks entre operaciones múltiples.
     */
    public function bloquearProductos(array $productoIds): void
    {
        $ids = collect($productoIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $bloqueados = Producto::whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id');

        if ($bloqueados->count() !== $ids->count()) {
            throw new \Exception('Uno o más productos del movimiento no existen.');
        }
    }

    /**
     * Registra un movimiento de INGRESO al almacén.
     *
     * Calcula el nuevo CPP usando promedio ponderado:
     *   costoTotal  = cantidad (unidades) × costo_unitario
     *   stockNuevo  = stockAnterior + cantidadStock (convertida a unidad base)
     *   valorNuevo  = valorAnterior + costoTotal
     *   costoNuevo  = valorNuevo / stockNuevo
     *
     * IMPORTANTE: stock_almacen se almacena en UNIDADES (sacos/empaques base), NO en KG.
     * Se usa la misma conversión de empaque que usaba el viejo Producto::updateStock().
     *
     * @param  array  $params  Datos del ingreso
     * @return Movimiento El movimiento registrado
     */
    public function registrarIngreso(array $params): Movimiento
    {
        return DB::transaction(
            fn (): Movimiento => $this->registrarIngresoBloqueado($params),
            3
        );
    }

    private function registrarIngresoBloqueado(array $params): Movimiento
    {
        $this->validarParametrosMovimiento($params, true);

        $producto = Producto::whereKey($params['producto_id'])
            ->lockForUpdate()
            ->firstOrFail();

        // Detectar si el movimiento es retroactivo (fecha anterior al último movimiento del producto)
        $ultimoMovimiento = Movimiento::where('producto_id', $producto->id)
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $esRetroactivo = $ultimoMovimiento
            && Carbon::parse($params['fecha'])->lt(Carbon::parse($ultimoMovimiento->fecha));

        $primerMovimientoExistente = $esRetroactivo
            ? Movimiento::where('producto_id', $producto->id)
                ->orderBy('fecha', 'asc')
                ->orderBy('id', 'asc')
                ->first()
            : null;

        // Saldos ANTES del movimiento (en unidades base del producto)
        $stockAnterior = (float) $producto->stock_almacen;
        $costoActual = (float) $producto->costo_unitario;
        $valorAnterior = $stockAnterior * $costoActual;

        // Cantidades del movimiento
        $cantidad = (float) ($params['cantidad'] ?? 0);
        $cantidadKg = (float) ($params['cantidad_kg'] ?? 0);
        $costoUnitario = (float) $params['costo_unitario'];

        // Conversión de empaque: convertir cantidad a unidades base del producto
        // Replica la lógica del viejo Producto::updateStock()
        $empaqueDetalle = (float) ($params['empaque'] ?? $producto->empaque);
        $empaqueProducto = (float) $producto->empaque;

        if ($empaqueDetalle <= 0 || $empaqueProducto <= 0) {
            throw new \Exception("Empaque inválido para el producto {$producto->nombre}");
        }

        // Stock en unidades base: si empaque coincide, usar cantidad directa
        $cantidadStock = ($empaqueDetalle == $empaqueProducto)
            ? $cantidad
            : round($cantidad * ($empaqueDetalle / $empaqueProducto), 4);

        // Costo total = cantidad (unidades compradas) × costo por unidad
        $costoTotal = $cantidad * $costoUnitario;

        // Cálculo CPP: Saldos DESPUÉS del movimiento
        // Stock en unidades base del producto
        $stockNuevo = round($stockAnterior + $cantidadStock, 4);
        $valorNuevo = $valorAnterior + $costoTotal;

        $stockNegativoAnterior = $stockAnterior <= 0;
        $costoUnitario = (float) ($params['costo_unitario'] ?? 0);

        if ($stockNegativoAnterior && $costoUnitario > 0) {
            $costoNuevo = $costoUnitario;
            $valorNuevo = $stockNuevo * $costoUnitario;
        } else {
            $costoNuevo = round($valorNuevo / $stockNuevo, 4);
        }

        // Costo unitario del movimiento: precio real de compra (por unidad base)
        // cpp del movimiento = costoUnitario, no costoNuevo (que es el CPP resultante)
        $costoUnitarioBase = $cantidadStock > 0 ? $costoTotal / $cantidadStock : $costoUnitario;

        // Crear registro de movimiento
        $movimiento = Movimiento::create([
            'fecha' => $params['fecha'],
            'tipo' => $params['tipo'],
            'transaccion_tipo' => $params['transaccion_tipo'],
            'transaccion_id' => $params['transaccion_id'],
            'detalle_id' => $params['detalle_id'] ?? null,
            'producto_id' => $params['producto_id'],
            'producto_nombre' => $params['producto_nombre'] ?? $producto->nombre,
            'empaque' => $empaqueDetalle,
            'unidad_codigo' => $params['unidad_codigo'] ?? $producto->unidad_codigo,
            'cantidad' => $cantidad,
            'cantidad_kg' => $cantidadKg,
            'entrada' => $cantidadStock,
            'salida' => 0,
            'costo_unitario' => round($costoUnitarioBase, 4),
            'costo_total' => round($costoTotal, 4),
            'stock_anterior' => round($stockAnterior, 4),
            'costo_actual' => round($costoActual, 4),
            'valor_anterior' => round($valorAnterior, 4),
            'stock_nuevo' => round($stockNuevo, 4),
            'costo_nuevo' => round($costoNuevo, 4),
            'valor_nuevo' => round($valorNuevo, 4),
            'user_id' => $params['user_id'] ?? auth()->id(),
            'comentario' => $params['comentario'] ?? null,
        ]);

        // Si es retroactivo, NO actualizar stock_almacen aquí; se recalculará desde el movimiento retroactivo.
        if ($esRetroactivo) {
            $this->recalcularKardexProductoBloqueado(
                $producto->id,
                (int) $movimiento->id,
                true,
                $primerMovimientoExistente ? (float) $primerMovimientoExistente->stock_anterior : null,
                $primerMovimientoExistente ? (float) $primerMovimientoExistente->costo_actual : null
            );
        } else {
            // Actualizar producto con saldos nuevos
            $producto->update([
                'stock_almacen' => round($stockNuevo, 4),
                'costo_unitario' => round($costoNuevo, 4),
            ]);
        }

        return $movimiento;
    }

    /**
     * Registra un movimiento de SALIDA del almacén.
     *
     * En salidas, el CPP NO cambia. Se usa el costo actual:
     *   costo_total  = cantidadStock × costoActual (CPP vigente)
     *   stock_nuevo  = stockAnterior - cantidadStock
     *   costo_nuevo  = costoActual (se mantiene)
     *
     * IMPORTANTE: stock_almacen se almacena en UNIDADES (sacos/empaques base), NO en KG.
     *
     * @param  array  $params  Datos de la salida
     * @return Movimiento El movimiento registrado
     */
    public function registrarSalida(array $params): Movimiento
    {
        return DB::transaction(
            fn (): Movimiento => $this->registrarSalidaBloqueada($params),
            3
        );
    }

    private function registrarSalidaBloqueada(array $params): Movimiento
    {
        $this->validarParametrosMovimiento($params, false);

        $producto = Producto::whereKey($params['producto_id'])
            ->lockForUpdate()
            ->firstOrFail();

        // Detectar si el movimiento es retroactivo (fecha anterior al último movimiento del producto)
        $ultimoMovimiento = Movimiento::where('producto_id', $producto->id)
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $esRetroactivo = $ultimoMovimiento
            && Carbon::parse($params['fecha'])->lt(Carbon::parse($ultimoMovimiento->fecha));

        $primerMovimientoExistente = $esRetroactivo
            ? Movimiento::where('producto_id', $producto->id)
                ->orderBy('fecha', 'asc')
                ->orderBy('id', 'asc')
                ->first()
            : null;

        // Saldos ANTES del movimiento (en unidades base del producto)
        $stockAnterior = (float) $producto->stock_almacen;
        $costoActual = (float) $producto->costo_unitario;
        $valorAnterior = $stockAnterior * $costoActual;

        // Cantidades del movimiento
        $cantidad = (float) ($params['cantidad'] ?? 0);
        $cantidadKg = (float) ($params['cantidad_kg'] ?? 0);

        // Conversión de empaque: convertir cantidad a unidades base del producto
        $empaqueDetalle = (float) ($params['empaque'] ?? $producto->empaque);
        $empaqueProducto = (float) $producto->empaque;

        if ($empaqueDetalle <= 0 || $empaqueProducto <= 0) {
            throw new \Exception("Empaque inválido para el producto {$producto->nombre}");
        }

        // Stock en unidades base: si empaque coincide, usar cantidad directa
        $cantidadStock = ($empaqueDetalle == $empaqueProducto)
            ? $cantidad
            : round($cantidad * ($empaqueDetalle / $empaqueProducto), 4);

        // En salidas: costo = CPP vigente (no cambia, salvo excepciones)
        $usarCostoExacto = ! empty($params['usar_costo_exacto']);
        $costoMovimiento = $usarCostoExacto ? (float) ($params['costo_unitario'] ?? $costoActual) : $costoActual;

        $costoTotal = $cantidadStock * $costoMovimiento;

        // Saldos DESPUÉS del movimiento
        $stockNuevo = round($stockAnterior - $cantidadStock, 4);
        $valorNuevo = $valorAnterior - $costoTotal;
        $costoNuevo = ($usarCostoExacto && $stockNuevo > 0) ? round($valorNuevo / $stockNuevo, 4) : $costoActual;

        // Crear registro de movimiento
        $movimiento = Movimiento::create([
            'fecha' => $params['fecha'],
            'tipo' => $params['tipo'],
            'transaccion_tipo' => $params['transaccion_tipo'],
            'transaccion_id' => $params['transaccion_id'],
            'detalle_id' => $params['detalle_id'] ?? null,
            'producto_id' => $params['producto_id'],
            'producto_nombre' => $params['producto_nombre'] ?? $producto->nombre,
            'empaque' => $empaqueDetalle,
            'unidad_codigo' => $params['unidad_codigo'] ?? $producto->unidad_codigo,
            'cantidad' => $cantidad,
            'cantidad_kg' => $cantidadKg,
            'entrada' => 0,
            'salida' => $cantidadStock,
            'costo_unitario' => round($costoMovimiento, 4),
            'costo_total' => round($costoTotal, 4),
            'stock_anterior' => round($stockAnterior, 4),
            'costo_actual' => round($costoActual, 4),
            'valor_anterior' => round($valorAnterior, 4),
            'stock_nuevo' => round($stockNuevo, 4),
            'costo_nuevo' => round($costoNuevo, 4),
            'valor_nuevo' => round($valorNuevo, 4),
            'user_id' => $params['user_id'] ?? auth()->id(),
            'comentario' => $params['comentario'] ?? null,
        ]);

        // Si es retroactivo, NO actualizar stock_almacen aquí; se recalculará desde el movimiento retroactivo.
        if ($esRetroactivo) {
            $this->recalcularKardexProductoBloqueado(
                $producto->id,
                (int) $movimiento->id,
                true,
                $primerMovimientoExistente ? (float) $primerMovimientoExistente->stock_anterior : null,
                $primerMovimientoExistente ? (float) $primerMovimientoExistente->costo_actual : null
            );
        } else {
            // Actualizar producto: solo stock cambia, CPP se mantiene
            $producto->update([
                'stock_almacen' => round($stockNuevo, 4),
                'costo_unitario' => round($costoNuevo, 4),
            ]);
        }

        return $movimiento;
    }

    /**
     * Recalcula el kardex de un producto desde la fecha de un movimiento específico.
     *
     * Se usa cuando se corrige un dato histórico (ej: precio de compra).
     * Recalcula TODOS los movimientos posteriores aplicando CPP en orden.
     *
     * Algoritmo:
     * 1. Obtiene saldos del movimiento ANTERIOR al punto de corrección
     * 2. Recorre todos los movimientos posteriores por fecha ASC, id ASC
     * 3. Recalcula cada uno con las fórmulas CPP correctas
     * 4. Si es VENTA, actualiza rentabilidad en venta_detalles
     * 5. Al final, actualiza el producto con los saldos del último movimiento
     *
     * @param  int  $productoId  ID del producto a recalcular
     * @param  int  $desdeMovimientoId  ID del movimiento desde donde recalcular
     * @param  bool  $actualizarStock  Si true (default), actualiza productos.stock_almacen al final.
     *                                 Pasar FALSE cuando el recálculo solo propaga COSTOS     /**
     *                                 Recalcula el kardex completo de un producto desde un movimiento específico (por ID).
     *
     * Ordena estrictamente por ID (orden secuencial físico de inserción).
     * @param  int  $productoId  ID del producto a recalcular
     * @param  int  $desdeMovimientoId  ID del movimiento desde donde recalcular
     * @param  bool  $actualizarStock  Si true (default), actualiza productos.stock_almacen al final.
     */
    public function recalcularKardexProducto(int $productoId, int $desdeMovimientoId, bool $actualizarStock = true): void
    {
        DB::transaction(function () use ($productoId, $desdeMovimientoId, $actualizarStock): void {
            Producto::whereKey($productoId)->lockForUpdate()->firstOrFail();
            $this->recalcularKardexProductoBloqueado($productoId, $desdeMovimientoId, $actualizarStock);
        }, 3);
    }

    /**
     * Recalcula el kardex completo de un producto desde una fecha específica.
     *
     * Busca el primer movimiento con fecha >= $fechaDesde y delega el recálculo por ID.
     *
     * @param  int  $productoId  ID del producto a recalcular
     * @param  string  $fechaDesde  Fecha desde la cual recalcular (formato Y-m-d)
     * @param  bool  $actualizarStock  Si true (default), actualiza productos.stock_almacen al final.
     */
    public function recalcularKardexProductoDesdeFecha(int $productoId, string $fechaDesde, bool $actualizarStock = true): void
    {
        DB::transaction(function () use ($productoId, $fechaDesde, $actualizarStock): void {
            Producto::whereKey($productoId)->lockForUpdate()->firstOrFail();

            $primerMov = Movimiento::where('producto_id', $productoId)
                ->where('fecha', '>=', $fechaDesde)
                ->orderBy('fecha', 'asc')
                ->orderBy('id', 'asc')
                ->first();

            if (! $primerMov) {
                return;
            }

            $this->recalcularKardexProductoBloqueado($productoId, (int) $primerMov->id, $actualizarStock);
        }, 3);
    }

    private function recalcularKardexProductoBloqueado(
        int $productoId,
        int $desdeMovimientoId,
        bool $actualizarStock = true,
        ?float $stockInicialSinAnterior = null,
        ?float $costoInicialSinAnterior = null
    ): void {
        // ────────────────────────────────────────────────────────────
        // BLOQUE: Búsqueda del movimiento anterior (punto de partida)
        // ────────────────────────────────────────────────────────────
        // Localiza el movimiento anterior por ID (id < $desdeMovimientoId).
        // Sus campos `stock_nuevo` y `costo_nuevo` sirven como semilla del bucle CPP.
        // ────────────────────────────────────────────────────────────
        $movimientoInicial = Movimiento::where('producto_id', $productoId)
            ->find($desdeMovimientoId);

        if ($movimientoInicial) {
            $movimientoAnterior = Movimiento::where('producto_id', $productoId)
                ->where(function ($query) use ($movimientoInicial): void {
                    $query->where('fecha', '<', $movimientoInicial->fecha)
                        ->orWhere(function ($sameDateQuery) use ($movimientoInicial): void {
                            $sameDateQuery->where('fecha', $movimientoInicial->fecha)
                                ->where('id', '<', $movimientoInicial->id);
                        });
                })
                ->orderBy('fecha', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $movimientos = Movimiento::where('producto_id', $productoId)
                ->where(function ($query) use ($movimientoInicial): void {
                    $query->where('fecha', '>', $movimientoInicial->fecha)
                        ->orWhere(function ($sameDateQuery) use ($movimientoInicial): void {
                            $sameDateQuery->where('fecha', $movimientoInicial->fecha)
                                ->where('id', '>=', $movimientoInicial->id);
                        });
                })
                ->orderBy('fecha', 'asc')
                ->orderBy('id', 'asc')
                ->get();
        } else {
            $movimientoAnterior = Movimiento::where('producto_id', $productoId)
                ->where('id', '<', $desdeMovimientoId)
                ->orderBy('id', 'desc')
                ->first();

            $movimientos = Movimiento::where('producto_id', $productoId)
                ->where('id', '>=', $desdeMovimientoId)
                ->orderBy('id', 'asc')
                ->get();
        }

        if ($movimientos->isEmpty()) {
            return;
        }

        // El stock inicial fue migrado sin un movimiento de apertura.
        // Al recalcular desde el primer movimiento se conserva su saldo previo.
        $stockActual = $movimientoAnterior
            ? (float) $movimientoAnterior->stock_nuevo
            : ($stockInicialSinAnterior ?? (float) $movimientos->first()->stock_anterior);
        $costoActual = $movimientoAnterior
            ? (float) $movimientoAnterior->costo_nuevo
            : ($costoInicialSinAnterior ?? (float) $movimientos->first()->costo_actual);

        foreach ($movimientos as $mov) {
            /** @var Movimiento $mov */
            if ($this->esAperturaLegado($mov)) {
                [$stockActual, $costoActual] = $this->aplicarAperturaLegado(
                    $mov,
                    $stockActual,
                    $costoActual
                );

                continue;
            }

            $valorAnterior = $stockActual * $costoActual;

            if ($mov->entrada > 0) {
                // === ENTRADA (Compra / Preparada Ingreso) ===
                $cantidadKg = (float) $mov->entrada;
                $costoMov = (float) $mov->costo_unitario;
                $costoTotal = $cantidadKg * $costoMov;

                $stockNuevo = $stockActual + $cantidadKg;
                $valorNuevo = $valorAnterior + $costoTotal;

                if ($stockActual <= 0) {
                    $costoNuevo = $costoMov;
                    $valorNuevo = $stockNuevo * $costoMov;
                } elseif ($stockNuevo != 0.0) {
                    $costoNuevo = round($valorNuevo / $stockNuevo, 4);
                } else {
                    $costoNuevo = $costoMov;
                    $valorNuevo = 0.0;
                }
            } else {
                // === SALIDA (Venta / Preparada Salida / Préstamo) ===
                $cantidadKg = (float) $mov->salida;

                // Verificar si es una salida con costo exacto forzado (ej. Devolución de préstamo)
                $esSalidaForzada = false;
                if ($mov->tipo === self::TIPO_PRESTAMO_SALIDA) {
                    $prestamo = \App\Models\Prestamo::find($mov->transaccion_id);
                    if ($prestamo && $prestamo->prestamo_referencia_id) {
                        $esSalidaForzada = true;
                    }
                }

                $costoMov = $esSalidaForzada ? (float) $mov->costo_unitario : $costoActual;
                $costoTotal = $cantidadKg * $costoMov;

                $stockNuevo = $stockActual - $cantidadKg;
                $valorNuevo = $valorAnterior - $costoTotal;
                $costoNuevo = ($esSalidaForzada && $stockNuevo > 0) ? round($valorNuevo / $stockNuevo, 4) : $costoActual;
            }

            // Actualizar el movimiento con saldos recalculados
            $mov->update([
                'stock_anterior' => round($stockActual, 4),
                'costo_actual' => round($costoActual, 4),
                'valor_anterior' => round($valorAnterior, 4),
                'costo_unitario' => round($costoMov, 4),
                'costo_total' => round($costoTotal, 4),
                'stock_nuevo' => round($stockNuevo, 4),
                'costo_nuevo' => round($costoNuevo, 4),
                'valor_nuevo' => round($valorNuevo, 4),
            ]);

            // Si es VENTA, actualizar rentabilidad en venta_detalles
            if ($mov->tipo === self::TIPO_VENTA && $mov->detalle_id) {
                $this->actualizarRentabilidadVenta($mov);
            }

            // Si es insumo de PREPARADA, actualizar costos del insumo y del producto final (en cascada)
            if ($mov->tipo === self::TIPO_PREPARADA_SALIDA && $mov->detalle_id) {
                if ($mov->transaccion_tipo === 'preparadas') {
                    $this->actualizarCostoPreparada($mov);
                } elseif ($mov->transaccion_tipo === 'nucleo_preparadas') {
                    $this->actualizarCostoNucleoPreparada($mov);
                }
            }

            // Avanzar saldos para el siguiente movimiento
            $stockActual = $stockNuevo;
            $costoActual = $costoNuevo;
        }

        // Actualizar producto con saldos del último movimiento
        if ($actualizarStock) {
            Producto::where('id', $productoId)->update([
                'stock_almacen' => round($stockActual, 4),
                'costo_unitario' => round($costoActual, 4),
            ]);
        } else {
            Producto::where('id', $productoId)->update([
                'costo_unitario' => round($costoActual, 4),
            ]);
        }
    }

    /**
     * Recalcula el kardex de un producto excluyendo movimientos de transacciones anuladas.
     *
     * A diferencia de recalcularKardexProducto(), este método:
     * 1. Toma stock_anterior y costo_actual del PRIMER movimiento excluido como punto de partida
     * 2. Recorre TODOS los movimientos desde ese punto por ID asc
     * 3. Los movimientos excluidos se NEUTRALIZAN (entrada/salida → 0) para que no impacten el cálculo
     * 4. Los demás movimientos se recalculan normalmente con el CPP correcto
     * 5. Al final, actualiza el producto con los saldos del último movimiento
     *
     * @param  int  $productoId  ID del producto a recalcular
     * @param  array  $excluirMovIds  IDs de movimientos a excluir (neutralizar) del cálculo
     * @param  bool  $actualizarStock  Si true (default), actualiza productos.stock_almacen al final.
     */
    public function recalcularKardexExcluyendo(int $productoId, array $excluirMovIds, bool $actualizarStock = true): void
    {
        if (empty($excluirMovIds)) {
            return;
        }

        DB::transaction(function () use ($productoId, $excluirMovIds, $actualizarStock): void {
            Producto::whereKey($productoId)->lockForUpdate()->firstOrFail();

            $movimientosExcluidos = Movimiento::where('producto_id', $productoId)
                ->whereIn('id', $excluirMovIds)
                ->orderBy('id', 'asc')
                ->get();

            if ($movimientosExcluidos->isEmpty()) {
                return;
            }

            $minMovId = (int) $movimientosExcluidos->min('id');

            // Neutralizar sin borrar registros. Después se reconstruye toda la
            // cadena por ID desde el primer movimiento afectado.
            foreach ($movimientosExcluidos as $movimiento) {
                $movimiento->update([
                    'entrada' => 0,
                    'salida' => 0,
                    'cantidad' => 0,
                    'cantidad_kg' => 0,
                    'costo_total' => 0,
                    'costo_unitario' => 0,
                    'comentario' => str_starts_with((string) $movimiento->comentario, '[ANULADO]')
                        ? $movimiento->comentario
                        : '[ANULADO] '.($movimiento->comentario ?? $movimiento->tipo),
                ]);
            }

            $this->recalcularKardexProductoBloqueado(
                $productoId,
                $minMovId,
                $actualizarStock
            );
        }, 3);
    }

    /**
     * Propaga SOLO cambios de costo en la cadena de movimientos de un producto.
     *
     * A diferencia de recalcularKardexProducto(), este método:
     * - LEE los stocks existentes de cada movimiento (stock_anterior, stock_nuevo,
     *   entrada, salida) SIN MODIFICARLOS.
     * - Solo CALCULA y ACTUALIZA campos de costo (costo_actual, costo_nuevo,
     *   costo_unitario, costo_total, valor_anterior, valor_nuevo).
     * - Al final, solo actualiza productos.costo_unitario (nunca stock_almacen).
     *
     * ¿Por qué existe este método?
     * Cuando actualizarCostoPreparada() detecta que el costo de un insumo cambió,
     * necesita propagar ese cambio de CPP hacia el producto final y sus movimientos
     * posteriores. Pero NO debe tocar stocks porque:
     *   1. Las cantidades (entrada/salida) no cambiaron.
     *   2. Si hay movimientos retroactivos (ID alto pero fecha anterior), los métodos
     *      de recálculo completo (que ordenan por ID) corrompen la cadena de stocks
     *      que fue construida en orden cronológico.
     *
     * Algoritmo:
     * 1. Toma el costo_nuevo del movimiento anterior como semilla.
     * 2. Recorre los movimientos desde $desdeMovimientoId en orden cronológico.
     * 3. Para cada movimiento, LEE stock_anterior/stock_nuevo existentes.
     * 4. Calcula el nuevo CPP usando esos stocks + el nuevo costo propagado.
     * 5. Actualiza SOLO los campos de costo en cada movimiento.
     * 6. Si encuentra VENTA, actualiza rentabilidad.
     * 7. Si encuentra PREPARADA_SALIDA, cascadea a actualizarCostoPreparada.
     * 8. Al final, actualiza solo productos.costo_unitario.
     *
     * @param  int  $productoId  ID del producto a propagar costos
     * @param  int  $desdeMovimientoId  ID del movimiento desde donde propagar
     */
    private function propagarSoloCostosProducto(int $productoId, int $desdeMovimientoId): void
    {
        Producto::whereKey($productoId)->lockForUpdate()->firstOrFail();

        // ────────────────────────────────────────────────────────────
        // Obtener el costo del movimiento anterior como punto de partida.
        // Solo necesitamos el costo (costo_nuevo), no el stock.
        // ────────────────────────────────────────────────────────────
        $movimientoInicial = Movimiento::where('producto_id', $productoId)
            ->findOrFail($desdeMovimientoId);

        $movimientoAnterior = Movimiento::where('producto_id', $productoId)
            ->where('id', '<', $desdeMovimientoId)
            ->orderBy('id', 'desc')
            ->first();

        $costoActual = $movimientoAnterior
            ? (float) $movimientoAnterior->costo_nuevo
            : (float) $movimientoInicial->costo_actual;

        // Propagar en estricto orden secuencial por ID
        $movimientos = Movimiento::where('producto_id', $productoId)
            ->where('id', '>=', $desdeMovimientoId)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($movimientos as $mov) {
            // ────────────────────────────────────────────────────────
            // LEER stocks existentes del movimiento (NO se modifican)
            // ────────────────────────────────────────────────────────
            if ($this->esAperturaLegado($mov)) {
                [, $costoActual] = $this->aplicarAperturaLegado(
                    $mov,
                    (float) $mov->stock_anterior,
                    $costoActual
                );

                continue;
            }

            $stockAnterior = (float) $mov->stock_anterior;
            $stockNuevo = (float) $mov->stock_nuevo;

            $valorAnterior = $stockAnterior * $costoActual;

            if ($mov->entrada > 0) {
                // === ENTRADA (Compra / Preparada Ingreso) ===
                // El costo_unitario del ingreso ya fue actualizado por actualizarCostoPreparada
                // para el movimiento semilla; para otros ingresos, se mantiene el existente.
                $costoMov = (float) $mov->costo_unitario;
                $costoTotal = (float) $mov->entrada * $costoMov;
                $valorNuevo = $valorAnterior + $costoTotal;

                if ($stockAnterior <= 0) {
                    $costoNuevo = $costoMov;
                    $valorNuevo = $stockNuevo * $costoMov;
                } else {
                    $costoNuevo = $stockNuevo > 0
                        ? round($valorNuevo / $stockNuevo, 4)
                        : $costoMov;
                }
            } else {
                // === SALIDA (Venta / Preparada Salida / Préstamo) ===
                // En salidas, el costo unitario es el CPP vigente (costoActual)
                $costoMov = $costoActual;
                $costoTotal = (float) $mov->salida * $costoMov;
                $valorNuevo = $valorAnterior - $costoTotal;
                $costoNuevo = $costoActual; // CPP no cambia en salidas normales
            }

            // ────────────────────────────────────────────────────────
            // Actualizar SOLO campos de costo (stock NO se toca)
            // ────────────────────────────────────────────────────────
            $mov->update([
                'costo_actual' => round($costoActual, 4),
                'valor_anterior' => round($valorAnterior, 4),
                'costo_unitario' => round($costoMov, 4),
                'costo_total' => round($costoTotal, 4),
                'costo_nuevo' => round($costoNuevo, 4),
                'valor_nuevo' => round($valorNuevo, 4),
                // NO se actualizan: stock_anterior, stock_nuevo, entrada, salida
            ]);

            // Si es VENTA, actualizar rentabilidad en venta_detalles
            if ($mov->tipo === self::TIPO_VENTA && $mov->detalle_id) {
                $this->actualizarRentabilidadVenta($mov);
            }

            // Si es insumo de PREPARADA, propagar costo en cascada al producto final
            if ($mov->tipo === self::TIPO_PREPARADA_SALIDA && $mov->detalle_id) {
                if ($mov->transaccion_tipo === 'preparadas') {
                    $this->actualizarCostoPreparada($mov);
                } elseif ($mov->transaccion_tipo === 'nucleo_preparadas') {
                    $this->actualizarCostoNucleoPreparada($mov);
                }
            }

            // Avanzar costo para el siguiente movimiento
            $costoActual = $costoNuevo;
        }

        // ────────────────────────────────────────────────────────────
        // Solo actualizar costo_unitario del producto (NUNCA stock_almacen)
        // ────────────────────────────────────────────────────────────
        Producto::where('id', $productoId)->update([
            'costo_unitario' => round($costoActual, 4),
        ]);
    }

    // ─── Métodos privados ───────────────────────────────────

    /**
     * Registra aperturas absolutas y reconstruye el kardex posterior en una
     * sola secuencia global fecha/id.
     *
     * @param  array<int, array{producto_id:int, stock_apertura:float, costo_apertura:float, fuente_costo?:string}>  $aperturas
     * @return array{aperturas:int, movimientos:int, productos:int}
     */
    public function reconciliarAperturasLegacy(array $aperturas, string $fechaApertura): array
    {
        $fecha = Carbon::parse($fechaApertura)->format('Y-m-d H:i:s');
        $transaccionId = (int) Carbon::parse($fecha)->format('Ymd');

        return DB::transaction(function () use ($aperturas, $fecha, $transaccionId): array {
            $idsApertura = collect($aperturas)
                ->pluck('producto_id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values();

            $idsConMovimientos = Movimiento::where('fecha', '>=', $fecha)
                ->where('producto_id', '!=', 77)
                ->distinct()
                ->pluck('producto_id');

            $productoIds = $idsApertura
                ->merge($idsConMovimientos)
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();

            $this->bloquearProductos($productoIds->all());
            $productos = Producto::whereIn('id', $productoIds)->get()->keyBy('id');

            foreach ($aperturas as $apertura) {
                $productoId = (int) $apertura['producto_id'];
                $producto = $productos->get($productoId);

                if (! $producto) {
                    throw new \RuntimeException("Producto {$productoId} no existe durante la reconciliacion.");
                }

                $existentes = Movimiento::where('producto_id', $productoId)
                    ->where('tipo', self::TIPO_APERTURA_LEGADO)
                    ->where('transaccion_tipo', self::TRANSACCION_APERTURA_LEGADO)
                    ->where('transaccion_id', $transaccionId)
                    ->lockForUpdate()
                    ->get();

                if ($existentes->count() > 1) {
                    throw new \RuntimeException("Existen aperturas legacy duplicadas para el producto {$productoId}.");
                }

                $stockObjetivo = round((float) $apertura['stock_apertura'], 4);
                $costoObjetivo = round((float) $apertura['costo_apertura'], 4);
                $comentario = '[APERTURA LEGADO] Saldo autoritativo al cierre del sistema anterior. Fuente CPP: '
                    .($apertura['fuente_costo'] ?? 'CSV');

                $datosObjetivo = [
                    'fecha' => $fecha,
                    'tipo' => self::TIPO_APERTURA_LEGADO,
                    'transaccion_tipo' => self::TRANSACCION_APERTURA_LEGADO,
                    'transaccion_id' => $transaccionId,
                    'detalle_id' => null,
                    'producto_id' => $productoId,
                    'producto_nombre' => $producto->nombre,
                    'empaque' => $producto->empaque,
                    'unidad_codigo' => $producto->unidad_codigo,
                    // En este tipo especial cantidad guarda el stock absoluto objetivo.
                    'cantidad' => $stockObjetivo,
                    'cantidad_kg' => 0,
                    'costo_unitario' => $costoObjetivo,
                    'user_id' => auth()->id(),
                    'comentario' => $comentario,
                ];

                if ($existentes->isNotEmpty()) {
                    $existentes->first()->update($datosObjetivo);
                } else {
                    Movimiento::create($datosObjetivo + [
                        'entrada' => 0,
                        'salida' => 0,
                        'costo_total' => 0,
                        'stock_anterior' => 0,
                        'costo_actual' => 0,
                        'valor_anterior' => 0,
                        'stock_nuevo' => $stockObjetivo,
                        'costo_nuevo' => $costoObjetivo,
                        'valor_nuevo' => round($stockObjetivo * $costoObjetivo, 4),
                    ]);
                }
            }

            $movimientos = $this->recalcularKardexGlobalDesdeFechaBloqueado(
                $productoIds->all(),
                $fecha
            );

            // Una rectificacion puede reutilizar IDs y dejar el ingreso de una
            // preparada antes que alguno de sus insumos en la misma fecha. La
            // segunda pasada parte de los costos de detalle ya estabilizados y
            // garantiza el CPP correcto del producto final y sus consumidores.
            $this->recalcularKardexGlobalDesdeFechaBloqueado(
                $productoIds->all(),
                $fecha
            );

            return [
                'aperturas' => count($aperturas),
                'movimientos' => $movimientos,
                'productos' => $productoIds->count(),
            ];
        }, 3);
    }

    /**
     * Reconstruye productos interdependientes en el mismo orden contable.
     * Debe ejecutarse dentro de una transaccion con productos bloqueados.
     */
    private function recalcularKardexGlobalDesdeFechaBloqueado(array $productoIds, string $fechaDesde): int
    {
        $estados = [];

        foreach ($productoIds as $productoId) {
            $anterior = Movimiento::where('producto_id', $productoId)
                ->where('fecha', '<', $fechaDesde)
                ->orderBy('fecha', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            $primero = Movimiento::where('producto_id', $productoId)
                ->where('fecha', '>=', $fechaDesde)
                ->orderBy('fecha')
                ->orderBy('id')
                ->first();

            if ($anterior) {
                $estados[$productoId] = [(float) $anterior->stock_nuevo, (float) $anterior->costo_nuevo];
            } elseif ($primero) {
                $estados[$productoId] = [(float) $primero->stock_anterior, (float) $primero->costo_actual];
            }
        }

        $movimientos = Movimiento::whereIn('producto_id', $productoIds)
            ->where('fecha', '>=', $fechaDesde)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        foreach ($movimientos as $movimiento) {
            $productoId = (int) $movimiento->producto_id;
            [$stockActual, $costoActual] = $estados[$productoId] ?? [0.0, 0.0];

            if ($this->esAperturaLegado($movimiento)) {
                $estados[$productoId] = $this->aplicarAperturaLegado(
                    $movimiento,
                    $stockActual,
                    $costoActual
                );

                continue;
            }

            // El costo de ingreso pudo cambiar al procesar los insumos de la
            // misma produccion unos IDs antes.
            if ($movimiento->tipo === self::TIPO_PREPARADA_INGRESO) {
                $movimiento->refresh();
            }

            $valorAnterior = $stockActual * $costoActual;

            if ((float) $movimiento->entrada > 0) {
                $cantidad = (float) $movimiento->entrada;
                $costoMovimiento = (float) $movimiento->costo_unitario;
                $costoTotal = $cantidad * $costoMovimiento;
                $stockNuevo = $stockActual + $cantidad;
                $valorNuevo = $valorAnterior + $costoTotal;

                if ($stockActual <= 0) {
                    $costoNuevo = $costoMovimiento;
                    $valorNuevo = $stockNuevo * $costoMovimiento;
                } elseif ($stockNuevo != 0.0) {
                    $costoNuevo = round($valorNuevo / $stockNuevo, 4);
                } else {
                    $costoNuevo = $costoMovimiento;
                    $valorNuevo = 0.0;
                }
            } else {
                $cantidad = (float) $movimiento->salida;
                $esSalidaForzada = false;

                if ($movimiento->tipo === self::TIPO_PRESTAMO_SALIDA) {
                    $prestamo = \App\Models\Prestamo::find($movimiento->transaccion_id);
                    $esSalidaForzada = $prestamo && $prestamo->prestamo_referencia_id;
                }

                $costoMovimiento = $esSalidaForzada
                    ? (float) $movimiento->costo_unitario
                    : $costoActual;
                $costoTotal = $cantidad * $costoMovimiento;
                $stockNuevo = $stockActual - $cantidad;
                $valorNuevo = $valorAnterior - $costoTotal;
                $costoNuevo = ($esSalidaForzada && $stockNuevo > 0)
                    ? round($valorNuevo / $stockNuevo, 4)
                    : $costoActual;
            }

            $movimiento->update([
                'stock_anterior' => round($stockActual, 4),
                'costo_actual' => round($costoActual, 4),
                'valor_anterior' => round($valorAnterior, 4),
                'costo_unitario' => round($costoMovimiento, 4),
                'costo_total' => round($costoTotal, 4),
                'stock_nuevo' => round($stockNuevo, 4),
                'costo_nuevo' => round($costoNuevo, 4),
                'valor_nuevo' => round($valorNuevo, 4),
            ]);

            if ($movimiento->tipo === self::TIPO_VENTA && $movimiento->detalle_id) {
                $this->actualizarRentabilidadVenta($movimiento);
            }

            if ($movimiento->tipo === self::TIPO_PREPARADA_SALIDA && $movimiento->detalle_id) {
                if ($movimiento->transaccion_tipo === 'preparadas') {
                    $this->actualizarCostoPreparada($movimiento, false);
                } elseif ($movimiento->transaccion_tipo === 'nucleo_preparadas') {
                    $this->actualizarCostoNucleoPreparada($movimiento, false);
                }
            }

            $estados[$productoId] = [$stockNuevo, $costoNuevo];
        }

        foreach ($estados as $productoId => [$stock, $costo]) {
            Producto::whereKey($productoId)->update([
                'stock_almacen' => round($stock, 4),
                'costo_unitario' => round($costo, 4),
            ]);
        }

        return $movimientos->count();
    }

    private function esAperturaLegado(Movimiento $movimiento): bool
    {
        return $movimiento->tipo === self::TIPO_APERTURA_LEGADO
            && $movimiento->transaccion_tipo === self::TRANSACCION_APERTURA_LEGADO;
    }

    /**
     * Aplica un punto de apertura absoluto sin borrar el historial anterior.
     * `cantidad` conserva el stock objetivo y `costo_unitario` el CPP objetivo.
     * Entrada/salida se recalculan para mantener la formula contable del kardex.
     *
     * @return array{0: float, 1: float}
     */
    private function aplicarAperturaLegado(
        Movimiento $movimiento,
        float $stockAnterior,
        float $costoAnterior
    ): array {
        $stockObjetivo = round((float) $movimiento->cantidad, 4);
        $costoObjetivo = round((float) $movimiento->costo_unitario, 4);
        $diferencia = round($stockObjetivo - $stockAnterior, 4);
        $entrada = max($diferencia, 0);
        $salida = max(-$diferencia, 0);
        $valorAnterior = round($stockAnterior * $costoAnterior, 4);
        $valorNuevo = round($stockObjetivo * $costoObjetivo, 4);

        $movimiento->update([
            'cantidad_kg' => 0,
            'entrada' => $entrada,
            'salida' => $salida,
            'costo_total' => round($valorNuevo - $valorAnterior, 4),
            'stock_anterior' => round($stockAnterior, 4),
            'costo_actual' => round($costoAnterior, 4),
            'valor_anterior' => $valorAnterior,
            'stock_nuevo' => $stockObjetivo,
            'costo_nuevo' => $costoObjetivo,
            'valor_nuevo' => $valorNuevo,
        ]);

        return [$stockObjetivo, $costoObjetivo];
    }

    private function validarParametrosMovimiento(array $params, bool $esIngreso): void
    {
        foreach (['fecha', 'tipo', 'transaccion_tipo', 'transaccion_id', 'producto_id'] as $campo) {
            if (! array_key_exists($campo, $params) || $params[$campo] === '' || $params[$campo] === null) {
                throw new \InvalidArgumentException("El campo {$campo} es obligatorio para registrar un movimiento.");
            }
        }

        try {
            Carbon::parse($params['fecha']);
        } catch (\Throwable) {
            throw new \InvalidArgumentException('La fecha del movimiento no es válida.');
        }

        $cantidad = (float) ($params['cantidad'] ?? 0);
        $cantidadKg = (float) ($params['cantidad_kg'] ?? 0);

        if ($cantidad < 0 || $cantidadKg < 0) {
            throw new \InvalidArgumentException('Las cantidades del movimiento no pueden ser negativas.');
        }

        if ($esIngreso) {
            if (! array_key_exists('costo_unitario', $params)) {
                throw new \InvalidArgumentException('El costo unitario es obligatorio para un ingreso.');
            }

            if ((float) $params['costo_unitario'] < 0) {
                throw new \InvalidArgumentException('El costo unitario no puede ser negativo.');
            }
        }
    }

    /**
     * Actualiza el costo y rentabilidad de un detalle de venta
     * cuando se recalcula el kardex.
     *
     * @param  Movimiento  $mov  Movimiento de tipo VENTA con detalle_id
     */
    private function actualizarRentabilidadVenta(Movimiento $mov): void
    {
        if ($mov->transaccion_tipo !== 'ventas') {
            return;
        }

        $detalle = DB::table('venta_detalles')->where('id', $mov->detalle_id)->first();

        if (! $detalle) {
            return;
        }

        // Obtener empaque del producto para convertir CPP (por saco) al empaque vendido
        $producto = Producto::find($mov->producto_id);
        $empaqueProducto = (float) ($producto->empaque ?? 1);
        $empaqueDetalle = (float) ($detalle->producto_empaque ?? 1);

        // Evitar división entre cero
        if ($empaqueProducto <= 0) {
            $empaqueProducto = 1;
        }

        // Convertir CPP (por saco/empaque base) al empaque con el que se vendió
        // Fórmula: (CPP / empaque_producto) * empaque_detalle_venta
        // Misma lógica que VentaService::calculateDetail() línea 354
        $costoUnitarioNuevo = round(((float) $mov->costo_unitario / $empaqueProducto) * $empaqueDetalle, 4);
        $costoTotalNuevo = round($costoUnitarioNuevo * $detalle->cantidad, 4);
        $rentabilidadNueva = round($detalle->total - $costoTotalNuevo, 4);

        // Actualizar detalle de venta con costo convertido
        DB::table('venta_detalles')->where('id', $mov->detalle_id)->update([
            'costo_unitario' => round($costoUnitarioNuevo, 4),
            'costo_total' => $costoTotalNuevo,
            'rentabilidad' => $rentabilidadNueva,
        ]);

        // Recalcular rentabilidad total de la venta
        $ventaId = $detalle->venta_id;
        $totalRentabilidad = DB::table('venta_detalles')
            ->where('venta_id', $ventaId)
            ->sum('rentabilidad');

        DB::table('ventas')->where('id', $ventaId)->update([
            'rentabilidad' => round($totalRentabilidad, 2),
        ]);
    }

    /**
     * Actualiza el costo de los insumos y del producto final de una preparada
     * cuando se recalcula el kardex (ej: por la anulación de una compra de insumos).
     *
     * Permite que los cambios de Costo Promedio Ponderado (CPP) viajen en cascada
     * desde la compra de insumos hacia los productos finales elaborados.
     *
     * @param  Movimiento  $mov  Movimiento de tipo PREPARADA_SALIDA con detalle_id
     */
    private function actualizarCostoPreparada(Movimiento $mov, bool $propagar = true): void
    {
        if ($mov->transaccion_tipo !== 'preparadas') {
            return;
        }

        // Fix 1: Filtrar por preparada_id para evitar que un detalle_id apunte a un detalle
        // de OTRA preparada por coincidencia de auto-increment. Esto detiene la cascada
        // cruzada que afecta a productos no relacionados al recalcular el kardex.
        $detalle = DB::table('preparada_detalles')
            ->where('id', $mov->detalle_id)
            ->where('preparada_id', $mov->transaccion_id)
            ->first();

        if (! $detalle) {
            return;
        }

        $costoUnitarioInsumo = (float) $mov->costo_unitario;
        $costoTotalInsumo = (float) $mov->costo_total;

        // 1) Actualizar detalle del insumo en la preparada con el nuevo costo
        DB::table('preparada_detalles')
            ->where('id', $mov->detalle_id)
            ->where('preparada_id', $mov->transaccion_id)
            ->update([
                'precio_unitario' => round($costoUnitarioInsumo, 4),
                'salida_soles' => round($costoTotalInsumo, 4),
            ]);

        // 2) Recalcular el costo total invertido en la preparada de forma sumatoria
        $preparadaId = $detalle->preparada_id;

        $nuevoTotalSoles = DB::table('preparada_detalles')
            ->where('preparada_id', $preparadaId)
            ->sum('salida_soles');

        $preparada = DB::table('preparadas')->where('id', $preparadaId)->first();
        if (! $preparada) {
            return;
        }

        $cantidadProductoFinal = (float) $preparada->ingreso_saco;
        $nuevoCostoUnitarioFinal = $cantidadProductoFinal > 0
            ? round($nuevoTotalSoles / $cantidadProductoFinal, 4)
            : round($nuevoTotalSoles, 4);

        // 3) Actualizar la cabecera de la preparada con el nuevo costo total
        DB::table('preparadas')->where('id', $preparadaId)->update([
            'ingreso_soles' => round($nuevoTotalSoles, 4),
            'costo_unitario' => $nuevoCostoUnitarioFinal,
        ]);

        // 4) Encontrar el movimiento de INGRESO del producto final recién recalculado
        $movIngresoFinal = Movimiento::where('transaccion_tipo', 'preparadas')
            ->where('transaccion_id', $preparadaId)
            ->where('tipo', self::TIPO_PREPARADA_INGRESO)
            ->first();

        // Si existe el ingreso del producto final, comprobamos si varió para no causar loops en vano
        if ($movIngresoFinal) {
            $cantidadStock = (float) $movIngresoFinal->entrada;
            $costoUnitarioBaseKardex = $cantidadStock > 0
                ? $nuevoTotalSoles / $cantidadStock
                : $nuevoCostoUnitarioFinal;

            // Verificamos matemáticamente si cambió el costo base
            if (abs((float) $movIngresoFinal->costo_total - $nuevoTotalSoles) > 0.001 ||
                abs((float) $movIngresoFinal->costo_unitario - $costoUnitarioBaseKardex) > 0.0001) {

                // Actualizamos costo EN ESTE MOVIMIENTO
                $movIngresoFinal->update([
                    'costo_unitario' => round($costoUnitarioBaseKardex, 4),
                    'costo_total' => round($nuevoTotalSoles, 4),
                ]);

                // 5) Propagar SOLO costos en la cadena del Kardex del Producto Final.
                //
                // Se usa propagarSoloCostosProducto() en lugar de recalcularKardexProducto()
                // porque aquí solo cambiaron COSTOS, nunca cantidades. El método:
                //   - LEE los stocks existentes de cada movimiento sin modificarlos.
                //   - Solo actualiza campos de costo (costo_unitario, costo_total, etc.).
                //   - Nunca toca stock_almacen ni stock_anterior/stock_nuevo.
                //
                // Esto evita dos bugs:
                //   a) Que rectificar una venta cambie el stock de productos no relacionados.
                //   b) Que la cadena de stocks se corrompa cuando hay movimientos retroactivos
                //      (ID alto pero fecha anterior), ya que recalcularKardexProducto ordena
                //      por ID pero la cadena original pudo construirse en orden cronológico.
                if ($propagar) {
                    $this->propagarSoloCostosProducto($movIngresoFinal->producto_id, $movIngresoFinal->id);
                }
            }
        }
    }

    /**
     * Actualiza costos de una preparacion de nucleo durante el replay global.
     * No propaga por separado: el ingreso y sus movimientos posteriores seran
     * procesados despues en la misma secuencia fecha/id.
     */
    private function actualizarCostoNucleoPreparada(Movimiento $movimiento, bool $propagar = true): void
    {
        $detalle = DB::table('nucleo_preparada_detalles')
            ->where('id', $movimiento->detalle_id)
            ->where('nucleo_preparada_id', $movimiento->transaccion_id)
            ->first();

        if (! $detalle) {
            return;
        }

        $empaque = (float) ($detalle->producto_empaque ?: 1);
        $costoKg = $empaque > 0
            ? (float) $movimiento->costo_unitario / $empaque
            : 0;

        DB::table('nucleo_preparada_detalles')
            ->where('id', $movimiento->detalle_id)
            ->where('nucleo_preparada_id', $movimiento->transaccion_id)
            ->update([
                'costo_unitario' => round($costoKg, 4),
                'salida_soles' => round((float) $movimiento->costo_total, 4),
            ]);

        $totalSoles = (float) DB::table('nucleo_preparada_detalles')
            ->where('nucleo_preparada_id', $movimiento->transaccion_id)
            ->sum('salida_soles');
        $preparada = DB::table('nucleo_preparadas')
            ->where('id', $movimiento->transaccion_id)
            ->first();

        if (! $preparada) {
            return;
        }

        $ingresoSaco = (float) $preparada->ingreso_saco;
        $costoFinal = $ingresoSaco > 0 ? $totalSoles / $ingresoSaco : 0;

        DB::table('nucleo_preparadas')
            ->where('id', $movimiento->transaccion_id)
            ->update([
                'ingreso_soles' => round($totalSoles, 4),
                'costo_unitario' => round($costoFinal, 4),
            ]);

        $ingreso = Movimiento::where('transaccion_tipo', 'nucleo_preparadas')
            ->where('transaccion_id', $movimiento->transaccion_id)
            ->where('tipo', self::TIPO_PREPARADA_INGRESO)
            ->first();

        if (! $ingreso) {
            return;
        }

        $cantidadStock = (float) $ingreso->entrada;
        $costoBase = $cantidadStock > 0 ? $totalSoles / $cantidadStock : $costoFinal;
        if (abs((float) $ingreso->costo_unitario - $costoBase) > 0.0001
            || abs((float) $ingreso->costo_total - $totalSoles) > 0.001) {
            $ingreso->update([
                'costo_unitario' => round($costoBase, 4),
                'costo_total' => round($totalSoles, 4),
            ]);

            if ($propagar) {
                $this->propagarSoloCostosProducto($ingreso->producto_id, $ingreso->id);
            }
        }
    }
}
