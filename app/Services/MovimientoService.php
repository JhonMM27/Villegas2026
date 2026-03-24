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

    // ─── Métodos públicos ───────────────────────────────────

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
     * @param  array $params Datos del ingreso
     * @return Movimiento El movimiento registrado
     */
    public function registrarIngreso(array $params): Movimiento
    {
        $producto = Producto::findOrFail($params['producto_id']);

        // Saldos ANTES del movimiento (en unidades base del producto)
        $stockAnterior = (float) $producto->stock_almacen;
        $costoActual   = (float) $producto->costo_unitario;
        $valorAnterior = $stockAnterior * $costoActual;

        // Cantidades del movimiento
        $cantidad       = (float) ($params['cantidad'] ?? 0);
        $cantidadKg     = (float) ($params['cantidad_kg'] ?? 0);
        $costoUnitario  = (float) $params['costo_unitario'];

        // Conversión de empaque: convertir cantidad a unidades base del producto
        // Replica la lógica del viejo Producto::updateStock()
        $empaqueDetalle  = (float) ($params['empaque'] ?? $producto->empaque);
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
        $costoNuevo = $stockNuevo > 0 ? round($valorNuevo / $stockNuevo, 4) : $costoUnitario;

        // El costo_unitario que guardamos en el Kardex debe ser siempre por UNIDAD BASE (ej: saco)
        // para que recalcularKardexProducto() funcione correctamente.
        // costo_unitario_base = costo_total / cantidad_en_unidades_base
        $costoUnitarioBase = $cantidadStock > 0 ? $costoTotal / $cantidadStock : $costoUnitario;

        // Crear registro de movimiento
        $movimiento = Movimiento::create([
            'fecha'             => $params['fecha'],
            'tipo'              => $params['tipo'],
            'transaccion_tipo'  => $params['transaccion_tipo'],
            'transaccion_id'    => $params['transaccion_id'],
            'detalle_id'        => $params['detalle_id'] ?? null,
            'producto_id'       => $params['producto_id'],
            'producto_nombre'   => $params['producto_nombre'] ?? $producto->nombre,
            'empaque'           => $empaqueDetalle,
            'unidad_codigo'     => $params['unidad_codigo'] ?? $producto->unidad_codigo,
            'cantidad'          => $cantidad,
            'cantidad_kg'       => $cantidadKg,
            'entrada'           => $cantidadStock,
            'salida'            => 0,
            'costo_unitario'    => round($costoUnitarioBase, 4),
            'costo_total'       => round($costoTotal, 4),
            'stock_anterior'    => round($stockAnterior, 4),
            'costo_actual'      => round($costoActual, 4),
            'valor_anterior'    => round($valorAnterior, 4),
            'stock_nuevo'       => round($stockNuevo, 4),
            'costo_nuevo'       => round($costoNuevo, 4),
            'valor_nuevo'       => round($valorNuevo, 4),
            'user_id'           => $params['user_id'] ?? auth()->id(),
            'comentario'        => $params['comentario'] ?? null,
        ]);

        // Actualizar producto con saldos nuevos
        $producto->update([
            'stock_almacen'  => round($stockNuevo, 4),
            'stock_almacen'   => round($stockNuevo, 4),
            'costo_unitario' => round($costoNuevo, 4),
        ]);

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
     * @param  array $params Datos de la salida
     * @return Movimiento El movimiento registrado
     */
    public function registrarSalida(array $params): Movimiento
    {
        $producto = Producto::findOrFail($params['producto_id']);

        // Saldos ANTES del movimiento (en unidades base del producto)
        $stockAnterior = (float) $producto->stock_almacen;
        $costoActual   = (float) $producto->costo_unitario;
        $valorAnterior = $stockAnterior * $costoActual;

        // Cantidades del movimiento
        $cantidad   = (float) ($params['cantidad'] ?? 0);
        $cantidadKg = (float) ($params['cantidad_kg'] ?? 0);

        // Conversión de empaque: convertir cantidad a unidades base del producto
        $empaqueDetalle  = (float) ($params['empaque'] ?? $producto->empaque);
        $empaqueProducto = (float) $producto->empaque;

        if ($empaqueDetalle <= 0 || $empaqueProducto <= 0) {
            throw new \Exception("Empaque inválido para el producto {$producto->nombre}");
        }

        // Stock en unidades base: si empaque coincide, usar cantidad directa
        $cantidadStock = ($empaqueDetalle == $empaqueProducto)
            ? $cantidad
            : round($cantidad * ($empaqueDetalle / $empaqueProducto), 4);

        // En salidas: costo = CPP vigente (no cambia, salvo excepciones)
        $usarCostoExacto = !empty($params['usar_costo_exacto']);
        $costoMovimiento = $usarCostoExacto ? (float) ($params['costo_unitario'] ?? $costoActual) : $costoActual;

        $costoTotal = $cantidadStock * $costoMovimiento;

        // Saldos DESPUÉS del movimiento
        $stockNuevo = round($stockAnterior - $cantidadStock, 4);
        $valorNuevo = $valorAnterior - $costoTotal;
        $costoNuevo = ($usarCostoExacto && $stockNuevo > 0) ? round($valorNuevo / $stockNuevo, 4) : $costoActual;

        // Crear registro de movimiento
        $movimiento = Movimiento::create([
            'fecha'             => $params['fecha'],
            'tipo'              => $params['tipo'],
            'transaccion_tipo'  => $params['transaccion_tipo'],
            'transaccion_id'    => $params['transaccion_id'],
            'detalle_id'        => $params['detalle_id'] ?? null,
            'producto_id'       => $params['producto_id'],
            'producto_nombre'   => $params['producto_nombre'] ?? $producto->nombre,
            'empaque'           => $empaqueDetalle,
            'unidad_codigo'     => $params['unidad_codigo'] ?? $producto->unidad_codigo,
            'cantidad'          => $cantidad,
            'cantidad_kg'       => $cantidadKg,
            'entrada'           => 0,
            'salida'            => $cantidadStock,
            'costo_unitario'    => round($costoMovimiento, 4),
            'costo_total'       => round($costoTotal, 4),
            'stock_anterior'    => round($stockAnterior, 4),
            'costo_actual'      => round($costoActual, 4),
            'valor_anterior'    => round($valorAnterior, 4),
            'stock_nuevo'       => round($stockNuevo, 4),
            'costo_nuevo'       => round($costoNuevo, 4),
            'valor_nuevo'       => round($valorNuevo, 4),
            'user_id'           => $params['user_id'] ?? auth()->id(),
            'comentario'        => $params['comentario'] ?? null,
        ]);

        // Actualizar producto: solo stock cambia, CPP se mantiene
        $producto->update([
            'stock_almacen'  => round($stockNuevo, 4),
            'stock_almacen'   => round($stockNuevo, 4),
            'costo_unitario' => round($costoNuevo, 4),
        ]);

        return $movimiento;
    }

    /**
     * Recalcula el kardex completo de un producto desde un movimiento específico.
     *
     * Se usa cuando se corrige un dato histórico (ej: precio de compra).
     * Recalcula TODOS los movimientos posteriores aplicando CPP en orden.
     *
     * Algoritmo:
     * 1. Obtiene saldos del movimiento ANTERIOR al punto de corrección
     * 2. Recorre todos los movimientos posteriores en orden ASC
     * 3. Recalcula cada uno con las fórmulas CPP correctas
     * 4. Si es VENTA, actualiza rentabilidad en venta_detalles
     * 5. Al final, actualiza el producto con los saldos del último movimiento
     *
     * @param  int $productoId         ID del producto a recalcular
     * @param  int $desdeMovimientoId  ID del movimiento desde donde recalcular
     * @return void
     */
    public function recalcularKardexProducto(int $productoId, int $desdeMovimientoId): void
    {
        // Obtener saldos del movimiento anterior al punto de corrección
        $movimientoAnterior = Movimiento::where('producto_id', $productoId)
            ->where('id', '<', $desdeMovimientoId)
            ->orderBy('id', 'desc')
            ->first();

        // Si no hay movimiento anterior, partimos de cero
        $stockActual = $movimientoAnterior ? (float) $movimientoAnterior->stock_nuevo : 0;
        $costoActual = $movimientoAnterior ? (float) $movimientoAnterior->costo_nuevo : 0;

        // Traer TODOS los movimientos desde el punto de corrección hacia adelante
        $movimientos = Movimiento::where('producto_id', $productoId)
            ->where('id', '>=', $desdeMovimientoId)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($movimientos as $mov) {
            $valorAnterior = $stockActual * $costoActual;

            if ($mov->entrada > 0) {
                // === ENTRADA (Compra / Preparada Ingreso) ===
                $cantidadKg = (float) $mov->entrada;
                $costoMov   = (float) $mov->costo_unitario;
                $costoTotal = $cantidadKg * $costoMov;

                $stockNuevo = $stockActual + $cantidadKg;
                $valorNuevo = $valorAnterior + $costoTotal;
                $costoNuevo = $stockNuevo > 0 ? $valorNuevo / $stockNuevo : $costoMov;
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

                $costoMov   = $esSalidaForzada ? (float) $mov->costo_unitario : $costoActual;
                $costoTotal = $cantidadKg * $costoMov;

                $stockNuevo = $stockActual - $cantidadKg;
                $valorNuevo = $valorAnterior - $costoTotal;
                $costoNuevo = ($esSalidaForzada && $stockNuevo > 0) ? ($valorNuevo / $stockNuevo) : $costoActual;
            }

            // Actualizar el movimiento con saldos recalculados
            $mov->update([
                'stock_anterior' => round($stockActual, 4),
                'costo_actual'   => round($costoActual, 4),
                'valor_anterior' => round($valorAnterior, 4),
                'costo_unitario' => round($costoMov, 4),
                'costo_total'    => round($costoTotal, 4),
                'stock_nuevo'    => round($stockNuevo, 4),
                'costo_nuevo'    => round($costoNuevo, 4),
                'valor_nuevo'    => round($valorNuevo, 4),
            ]);

            // Si es VENTA, actualizar rentabilidad en venta_detalles
            if ($mov->tipo === self::TIPO_VENTA && $mov->detalle_id) {
                $this->actualizarRentabilidadVenta($mov);
            }

            // Si es insumo de PREPARADA, actualizar costos del insumo y del producto final (en cascada)
            if ($mov->tipo === self::TIPO_PREPARADA_SALIDA && $mov->detalle_id) {
                $this->actualizarCostoPreparada($mov);
            }

            // Avanzar saldos para el siguiente movimiento
            $stockActual = $stockNuevo;
            $costoActual = $costoNuevo;
        }

        // Actualizar producto con saldos del último movimiento
        Producto::where('id', $productoId)->update([
            'stock_almacen'  => round($stockActual, 4),
            'stock_almacen'   => round($stockActual, 4),
            'costo_unitario' => round($costoActual, 4),
        ]);
    }

    /**
     * Recalcula el kardex de un producto excluyendo movimientos de transacciones anuladas.
     *
     * A diferencia de recalcularKardexProducto(), este método:
     * 1. Toma stock_anterior y costo_actual del PRIMER movimiento excluido como punto de partida
     *    (estos campos guardan el estado del inventario ANTES de la transacción original)
     * 2. Recorre TODOS los movimientos desde ese punto en orden de ID ASC
     * 3. Los movimientos excluidos se NEUTRALIZAN (entrada/salida → 0) para que no impacten el cálculo
     * 4. Los demás movimientos se recalculan normalmente con el CPP correcto
     * 5. Si es VENTA, actualiza rentabilidad en venta_detalles
     * 6. Al final, actualiza el producto con los saldos del último movimiento
     *
     * Caso de uso: Al anular una compra, los movimientos originales COMPRA se excluyen
     * y todas las ventas/preparadas posteriores se recalculan con el CPP pre-compra.
     *
     * @param  int   $productoId    ID del producto a recalcular
     * @param  array $excluirMovIds IDs de movimientos a excluir (neutralizar) del cálculo
     * @return void
     */
    public function recalcularKardexExcluyendo(int $productoId, array $excluirMovIds): void
    {
        if (empty($excluirMovIds)) {
            return;
        }

        // Obtener el primer movimiento excluido para tomar el estado PRE-transacción
        $primerExcluido = Movimiento::where('producto_id', $productoId)
            ->whereIn('id', $excluirMovIds)
            ->orderBy('id', 'asc')
            ->first();

        if (!$primerExcluido) {
            return;
        }

        // Punto de partida: estado del inventario ANTES de la transacción anulada
        $stockActual = (float) $primerExcluido->stock_anterior;
        $costoActual = (float) $primerExcluido->costo_actual;

        // Traer TODOS los movimientos desde el primer excluido hacia adelante
        $movimientos = Movimiento::where('producto_id', $productoId)
            ->where('id', '>=', $primerExcluido->id)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($movimientos as $mov) {
            // Si el movimiento está en la lista de exclusión, NEUTRALIZARLO
            if (in_array($mov->id, $excluirMovIds)) {
                $valorActual = $stockActual * $costoActual;
                $mov->update([
                    'entrada'        => 0,
                    'salida'         => 0,
                    'cantidad'       => 0,
                    'cantidad_kg'    => 0,
                    'costo_total'    => 0,
                    'stock_anterior' => round($stockActual, 4),
                    'costo_actual'   => round($costoActual, 4),
                    'valor_anterior' => round($valorActual, 4),
                    'stock_nuevo'    => round($stockActual, 4),
                    'costo_nuevo'    => round($costoActual, 4),
                    'valor_nuevo'    => round($valorActual, 4),
                    'costo_unitario' => 0,
                    'comentario'     => '[ANULADO] ' . ($mov->comentario ?? $mov->tipo),
                ]);
                // No avanzar saldos: el movimiento no tiene impacto
                continue;
            }

            // Recálculo normal para movimientos NO excluidos
            $valorAnterior = $stockActual * $costoActual;

            if ($mov->entrada > 0) {
                // === ENTRADA (Compra / Preparada Ingreso / Anulación Venta) ===
                $cantidadKg = (float) $mov->entrada;
                $costoMov   = (float) $mov->costo_unitario;
                $costoTotal = $cantidadKg * $costoMov;

                $stockNuevo = $stockActual + $cantidadKg;
                $valorNuevo = $valorAnterior + $costoTotal;
                $costoNuevo = $stockNuevo > 0 ? $valorNuevo / $stockNuevo : $costoMov;
            } else {
                // === SALIDA (Venta / Preparada Salida / Anulación Compra) ===
                $cantidadKg = (float) $mov->salida;
                $costoMov   = $costoActual; // CPP vigente
                $costoTotal = $cantidadKg * $costoMov;

                $stockNuevo = $stockActual - $cantidadKg;
                $valorNuevo = $valorAnterior - $costoTotal;
                $costoNuevo = $costoActual; // No cambia en salidas
            }

            // Actualizar el movimiento con saldos recalculados
            $mov->update([
                'stock_anterior' => round($stockActual, 4),
                'costo_actual'   => round($costoActual, 4),
                'valor_anterior' => round($valorAnterior, 4),
                'costo_unitario' => round($costoMov, 4),
                'costo_total'    => round($costoTotal, 4),
                'stock_nuevo'    => round($stockNuevo, 4),
                'costo_nuevo'    => round($costoNuevo, 4),
                'valor_nuevo'    => round($valorNuevo, 4),
            ]);

            // Si es VENTA, actualizar rentabilidad en venta_detalles
            if ($mov->tipo === self::TIPO_VENTA && $mov->detalle_id) {
                $this->actualizarRentabilidadVenta($mov);
            }

            // Si es insumo de PREPARADA, actualizar costos del insumo y del producto final (en cascada)
            if ($mov->tipo === self::TIPO_PREPARADA_SALIDA && $mov->detalle_id) {
                $this->actualizarCostoPreparada($mov);
            }

            // Avanzar saldos para el siguiente movimiento
            $stockActual = $stockNuevo;
            $costoActual = $costoNuevo;
        }

        // Actualizar producto con saldos del último movimiento procesado
        Producto::where('id', $productoId)->update([
            'stock_almacen'  => round($stockActual, 4),
            'stock_almacen'   => round($stockActual, 4),
            'costo_unitario' => round($costoActual, 4),
        ]);
    }

    // ─── Métodos privados ───────────────────────────────────

    /**
     * Actualiza el costo y rentabilidad de un detalle de venta
     * cuando se recalcula el kardex.
     *
     * @param Movimiento $mov Movimiento de tipo VENTA con detalle_id
     */
    private function actualizarRentabilidadVenta(Movimiento $mov): void
    {
        $detalle = DB::table('venta_detalles')->where('id', $mov->detalle_id)->first();

        if (!$detalle) {
            return;
        }

        // Obtener empaque del producto para convertir CPP (por saco) al empaque vendido
        $producto = Producto::find($mov->producto_id);
        $empaqueProducto = (float) ($producto->empaque ?? 1);
        $empaqueDetalle  = (float) ($detalle->producto_empaque ?? 1);

        // Evitar división entre cero
        if ($empaqueProducto <= 0) {
            $empaqueProducto = 1;
        }

        // Convertir CPP (por saco/empaque base) al empaque con el que se vendió
        // Fórmula: (CPP / empaque_producto) * empaque_detalle_venta
        // Misma lógica que VentaService::calculateDetail() línea 354
        $costoUnitarioNuevo = round(((float) $mov->costo_unitario / $empaqueProducto) * $empaqueDetalle, 4);
        $costoTotalNuevo    = round($costoUnitarioNuevo * $detalle->cantidad, 4);
        $rentabilidadNueva  = round($detalle->total - $costoTotalNuevo, 4);

        // Actualizar detalle de venta con costo convertido
        DB::table('venta_detalles')->where('id', $mov->detalle_id)->update([
            'costo_unitario' => round($costoUnitarioNuevo, 4),
            'costo_total'    => $costoTotalNuevo,
            'rentabilidad'   => $rentabilidadNueva,
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
     * @param Movimiento $mov Movimiento de tipo PREPARADA_SALIDA con detalle_id
     */
    private function actualizarCostoPreparada(Movimiento $mov): void
    {
        $detalle = DB::table('preparada_detalles')->where('id', $mov->detalle_id)->first();

        if (!$detalle) {
            return;
        }

        $costoUnitarioInsumo = (float) $mov->costo_unitario;
        $costoTotalInsumo    = (float) $mov->costo_total;

        // 1) Actualizar detalle del insumo en la preparada con el nuevo costo
        DB::table('preparada_detalles')->where('id', $mov->detalle_id)->update([
            'precio_unitario' => round($costoUnitarioInsumo, 4),
            'salida_soles'    => round($costoTotalInsumo, 4),
        ]);

        // 2) Recalcular el costo total invertido en la preparada de forma sumatoria
        $preparadaId = $detalle->preparada_id;
        
        $nuevoTotalSoles = DB::table('preparada_detalles')
            ->where('preparada_id', $preparadaId)
            ->sum('salida_soles');

        $preparada = DB::table('preparadas')->where('id', $preparadaId)->first();
        if (!$preparada) {
            return;
        }

        $cantidadProductoFinal   = (float) $preparada->ingreso_saco;
        $nuevoCostoUnitarioFinal = $cantidadProductoFinal > 0 
            ? round($nuevoTotalSoles / $cantidadProductoFinal, 4) 
            : round($nuevoTotalSoles, 4);

        // 3) Actualizar la cabecera de la preparada con el nuevo costo total
        DB::table('preparadas')->where('id', $preparadaId)->update([
            'ingreso_soles'  => round($nuevoTotalSoles, 4),
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
            if (abs((float)$movIngresoFinal->costo_total - $nuevoTotalSoles) > 0.001 || 
                abs((float)$movIngresoFinal->costo_unitario - $costoUnitarioBaseKardex) > 0.0001) {
                
                // Actualizamos costo EN ESTE MOVIMIENTO
                $movIngresoFinal->update([
                    'costo_unitario' => round($costoUnitarioBaseKardex, 4),
                    'costo_total'    => round($nuevoTotalSoles, 4),
                ]);

                // 5) Llamada RECURSIVA para recalcular la cadena del Kardex del Producto Final
                // Permite propagar los costos si el alimento se usa, o calibrar la rentabilidad si se ha vendido
                $this->recalcularKardexProducto($movIngresoFinal->producto_id, $movIngresoFinal->id);
            }
        }
    }
}
