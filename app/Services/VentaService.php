<?php

/**
 * Servicio de Ventas.
 *
 * Concentra toda la lógica de negocio asociada a la creación de ventas:
 * - Reserva de correlativo con bloqueo pessimista
 * - Cálculo de totales, impuestos y rentabilidad por detalle
 * - Registro de movimientos de SALIDA en el kardex (MovimientoService)
 * - Vinculación con cotizaciones
 *
 * NOTA: Los métodos updateVenta() y deleteVenta() han sido COMENTADOS
 * porque ahora el sistema usa kardex valorizado.
 */

namespace App\Services;

use App\Models\Cliente;
use App\Models\ComprobanteSerie;
use App\Models\ComprobanteTipo;
use App\Models\Cotizacion;
use App\Models\Movimiento;
use App\Models\PagoForma;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class VentaService
{
    /**
     * Inyección del servicio de movimientos para registrar
     * salidas en el kardex valorizado.
     */
    public function __construct(
        protected MovimientoService $movimientoService
    ) {}

    /**
     * Crea una venta completa dentro de una transacción:
     * 1. Bloquea la serie para reservar correlativo (lockForUpdate)
     * 2. Procesa cabecera y detalles calculados
     * 3. Crea el registro Venta + VentaDetalles
     * 4. Registra movimientos de SALIDA en el kardex por cada detalle
     * 5. Incrementa el correlativo de la serie
     * 6. Actualiza la cotización asociada si aplica
     *
     * @param  array  $data  Datos validados del request
     * @param  int|null  $cotizacionRefId  ID de cotización a vincular (opcional)
     * @return Venta La venta recién creada
     *
     * @throws \Illuminate\Database\QueryException Si hay conflicto de correlativo
     * @throws \Exception Si ocurre cualquier otro error
     */
    public function createVenta(array $data, ?int $cotizacionRefId = null): Venta
    {
        return DB::transaction(function () use ($data, $cotizacionRefId) {

            // 1) Bloquear la serie para evitar que otro proceso lea el mismo correlativo
            $serieConfig = ComprobanteSerie::where('comprobante_tipo_codigo', $data['comprobante_tipo_codigo'])
                ->where('serie', $data['serie'])
                ->lockForUpdate()
                ->firstOrFail();

            // 2) Reservar correlativo (backend manda)
            $correlativo = (int) $serieConfig->correlativo;
            $data['correlativo'] = $correlativo;

            // 3) Construir data de venta (cabecera + detalles calculados)
            $ventaData = $this->processVentaData($data, true);

            // 4) Persistir venta y detalles
            $venta = Venta::create($ventaData['venta']);
            $detallesCreados = $venta->detalles()->createMany($ventaData['detalles']);

            // 5) Registrar movimientos de SALIDA en el kardex SOLO por la cantidad entregada
            //    Si entregado < cantidad, queda saldo pendiente que se gestiona en VentaEntrega
            foreach ($detallesCreados as $detalle) {
                $entregado = (float) $detalle->entregado;
                if ($entregado <= 0) {
                    continue;
                }
                $cantidadKg = $entregado * (float) $detalle->producto_empaque;
                $this->movimientoService->registrarSalida([
                    'tipo' => MovimientoService::TIPO_VENTA,
                    'fecha' => $venta->fecha_venta,
                    'transaccion_tipo' => 'ventas',
                    'transaccion_id' => $venta->id,
                    'detalle_id' => $detalle->id,
                    'producto_id' => $detalle->producto_id,
                    'producto_nombre' => $detalle->producto_nombre,
                    'empaque' => $detalle->producto_empaque,
                    'unidad_codigo' => $detalle->unidad_codigo,
                    'cantidad' => $entregado,
                    'cantidad_kg' => $cantidadKg,
                ]);
            }

            // ============================================================
            // CÓDIGO ANTERIOR (descontaba toda la cantidad, no solo entregado)
            // ============================================================
            // foreach ($detallesCreados as $detalle) {
            //     $this->movimientoService->registrarSalida([
            //         'tipo' => MovimientoService::TIPO_VENTA,
            //         'fecha' => $venta->fecha_venta,
            //         'transaccion_tipo' => 'ventas',
            //         'transaccion_id' => $venta->id,
            //         'detalle_id' => $detalle->id,
            //         'producto_id' => $detalle->producto_id,
            //         'producto_nombre' => $detalle->producto_nombre,
            //         'empaque' => $detalle->producto_empaque,
            //         'unidad_codigo' => $detalle->unidad_codigo,
            //         'cantidad' => $detalle->cantidad,
            //         'cantidad_kg' => $detalle->salida_kg,
            //     ]);
            // }
            // ============================================================

            // 6) Incrementar correlativo en comprobante_series
            $serieConfig->update([
                'correlativo' => $correlativo + 1,
            ]);

            // 7) Actualizar cotización si viene de una cotización
            if ($cotizacionRefId) {
                Cotizacion::where('id', $cotizacionRefId)
                    ->update([
                        'venta_id' => $venta->id,
                        'estado' => 'PROCESADO',
                    ]);
            }

            return $venta;
        });
    }

    /**
     * Anula una venta existente dentro de una transacción:
     * 1. Valida que la venta no esté ya anulada
     * 2. Cambia el estado a 'anulada'
     * 3. Registra un movimiento de INGRESO (reversión de la salida)
     *    por cada detalle vendido, devolviendo el stock al almacén
     * 4. Recalcula el kardex en cascada desde el movimiento original
     *    de la venta para actualizar CPP en todas las transacciones
     *    posteriores (incluyendo compras nuevas que dependen del stock restaurado)
     *
     * @param  int  $id  ID de la venta a anular
     * @return Venta La venta anulada
     *
     * @throws \Exception Si la venta ya está anulada o si ocurre error
     */
    public function anularVenta(int $id): Venta
    {
        return DB::transaction(function () use ($id) {
            $venta = Venta::with('detalles')->findOrFail($id);

            // Validar que no esté ya anulada
            if ($venta->estado === 'anulada') {
                throw new \Exception('Esta venta ya fue anulada.');
            }

            // Si está rectificada, permitir anular para volver a rectificar (si count < 3)
            if ($venta->estado === 'rectificada') {
                if ($venta->rectificacion_count >= 3) {
                    throw new \Exception('Esta venta ya no puede ser rectificada. Máximo 3 rectificaciones permitidas.');
                }
                // Permitir: se volverá a 'anulada' para poder rectificar de nuevo
            }

            // 1) Cambiar estado a 'anulada'
            $venta->update(['estado' => 'anulada']);

            // 2) Recopilar IDs de productos afectados
            $productosAfectados = [];
            foreach ($venta->detalles as $detalle) {
                if (! in_array($detalle->producto_id, $productosAfectados)) {
                    $productosAfectados[] = $detalle->producto_id;
                }
            }

            // 3) Proceso de recálculo (neutralización lógica)
            foreach ($productosAfectados as $productoId) {
                $movIds = Movimiento::where('transaccion_tipo', 'ventas')
                    ->where('transaccion_id', $venta->id)
                    ->where('producto_id', $productoId)
                    ->pluck('id')
                    ->toArray();

                if (! empty($movIds)) {
                    $this->movimientoService->recalcularKardexExcluyendo(
                        $productoId,
                        $movIds
                    );
                }
            }

            return $venta;
        });
    }

    /**
     * Rectifica una venta previamente anulada (Actualización in-situ).
     * Modifica el registro existente en lugar de crear uno nuevo.
     */
    public function rectificarVenta(int $ventaId, array $data): Venta
    {
        return DB::transaction(function () use ($ventaId, $data) {
            $venta = Venta::findOrFail($ventaId);

            if ($venta->estado !== 'anulada') {
                throw new \Exception('Solo se pueden rectificar ventas en estado anulada.');
            }

            if ($venta->rectificacion_count >= 3) {
                throw new \Exception('Esta venta ya no puede ser rectificada. Máximo 3 rectificaciones permitidas.');
            }

            // 1) Procesar los datos de la venta
            $ventaDataRaw = $this->processVentaData($data, false);
            $ventaData = $ventaDataRaw['venta'];

            // Forzar serie y correlativo originales de la venta (se preservan en rectificación)
            $ventaData['serie'] = $venta->serie;
            $ventaData['correlativo'] = $venta->correlativo;
            $ventaData['estado'] = 'rectificada';
            $ventaData['nota'] = trim(($data['nota'] ?? $venta->nota).' | Rectificada el '.now()->format('d/m/Y H:i'));

            // 2) Actualizar la cabecera del registro existente
            $venta->update($ventaData);

            // Incrementar contador de rectificaciones
            $venta->update(['rectificacion_count' => $venta->rectificacion_count + 1]);

            // 3) Gestionar detalles y movimientos
            // Eliminamos detalles antiguos para reemplazarlos (más limpio que update individual)
            $venta->detalles()->delete();
            $detallesNuevos = $venta->detalles()->createMany($ventaDataRaw['detalles']);

            // ────────────────────────────────────────────────────────────
            // BLOQUE: Reconstrucción de movimientos del kardex
            // ────────────────────────────────────────────────────────────
            // ¿Qué hace?: Tras reemplazar los detalles de la venta, cada
            //              línea debe quedar representada en el kardex.
            //              Para cada detalle:
            //                1. Busca un movimiento neutralizado previo
            //                   (de cuando se anuló la venta) para el
            //                   mismo producto y reutilizarlo.
            //                2. Si no existe, crea uno nuevo.
            //
            // ¿Por qué $movimientosUsados?:  Una venta puede tener dos
            //   líneas con el mismo producto. Sin este tracking, ambas
            //   líneas encontrarían el mismo movimiento neutralizado y
            //   lo sobrescribirían, dejando una línea sin restaurar.
            //
            // ¿Por qué criterio cantidad=0 / cantidad_kg=0?:  Es el
            //   estado que deja `recalcularKardexExcluyendo()` al
            //   neutralizar movimientos de una venta anulada. Coincide
            //   con `entrada=0 / salida=0`, pero es más directo.
            // ────────────────────────────────────────────────────────────
            $productosAfectados = [];
            $movimientosUsados = [];

            foreach ($detallesNuevos as $detalle) {
                $cantidades = $this->calcularCantidadMovimientoVenta($detalle);

                $movNeutralizado = Movimiento::where('transaccion_tipo', 'ventas')
                    ->where('transaccion_id', $ventaId)
                    ->where('producto_id', $detalle->producto_id)
                    ->where('cantidad', 0)
                    ->where('cantidad_kg', 0)
                    ->whereNotIn('id', $movimientosUsados)
                    ->orderBy('id', 'asc')
                    ->first();

                if ($movNeutralizado) {
                    // Restauramos el movimiento original neutralizado.
                    // Nota: aunque entregado=0, igualmente restauramos
                    // para mantener trazabilidad de la línea.
                    $movNeutralizado->update([
                        'detalle_id' => $detalle->id,
                        'fecha' => $venta->fecha_venta,
                        'empaque' => $cantidades['empaque'],
                        'unidad_codigo' => $detalle->unidad_codigo,
                        'cantidad' => $cantidades['cantidad'],
                        'cantidad_kg' => $cantidades['cantidad_kg'],
                        'entrada' => 0,
                        'salida' => $cantidades['salida'],
                        'costo_unitario' => $detalle->costo_unitario,
                        'costo_total' => $detalle->costo_total,
                        'comentario' => 'Rectificación de venta (Actualizado)',
                    ]);
                    $movimientosUsados[] = $movNeutralizado->id;
                } else {
                    // Sin mov neutralizado previo: registrar una nueva
                    // salida. Si entregado=0, MovimientoService
                    // generará un movimiento neutral (entrada=0,
                    // salida=0) sin afectar stock pero conservando
                    // trazabilidad de la línea.
                    $this->movimientoService->registrarSalida([
                        'tipo' => MovimientoService::TIPO_VENTA,
                        'fecha' => $venta->fecha_venta,
                        'transaccion_tipo' => 'ventas',
                        'transaccion_id' => $venta->id,
                        'detalle_id' => $detalle->id,
                        'producto_id' => $detalle->producto_id,
                        'producto_nombre' => $detalle->producto_nombre,
                        'empaque' => $cantidades['empaque'],
                        'unidad_codigo' => $detalle->unidad_codigo,
                        'cantidad' => $cantidades['cantidad'],
                        'cantidad_kg' => $cantidades['cantidad_kg'],
                    ]);
                }
                $productosAfectados[] = $detalle->producto_id;
            }

            // 4) Recalcular Kardex para productos afectados
            //
            // IMPORTANTE: se pasa el datetime completo (Y-m-d H:i:s) a recalcularKardexProductoDesdeFecha
            // para que el punto de partida del recálculo sea el último movimiento ANTES de la hora
            // exacta de la venta, no simplemente antes del día. Sin esto, todos los movimientos del
            // mismo día (pero anteriores en hora) también se recalculan con saldo inicial incorrecto.
            $fechaVenta = $venta->fecha_venta instanceof \Carbon\Carbon
                ? $venta->fecha_venta
                : \Carbon\Carbon::parse($venta->fecha_venta);

            foreach (array_unique($productosAfectados) as $productoId) {
                if ($fechaVenta->lt(today())) {
                    $this->movimientoService->recalcularKardexProductoDesdeFecha(
                        $productoId,
                        $fechaVenta->format('Y-m-d H:i:s')
                    );
                } else {
                    $primerMov = Movimiento::where('transaccion_id', $venta->id)
                        ->where('producto_id', $productoId)
                        ->orderBy('id', 'asc')
                        ->first();

                    if ($primerMov) {
                        $this->movimientoService->recalcularKardexProducto($productoId, $primerMov->id);
                    }
                }
            }

            return $venta;
        });
    }

    /* ============================================================
     * COMENTADO: Migrado a sistema de Kardex Valorizado.
     *
     * Las ventas ya NO se pueden editar ni eliminar directamente.
     * Las correcciones se manejan mediante recálculo en cascada
     * usando MovimientoService::recalcularKardexProducto().
     * ============================================================ */

    // /**
    //  * Actualiza una venta existente dentro de una transacción:
    //  * 1. Revierte el stock anterior (devuelve productos al almacén)
    //  * 2. Recalcula cabecera y detalles con los nuevos datos
    //  * 3. Reemplaza los detalles (delete + createMany)
    //  * 4. Aplica el nuevo stock (salida de productos)
    //  *
    //  * @param  int   $id   ID de la venta a actualizar
    //  * @param  array $data Datos validados del request
    //  * @return Venta La venta actualizada
    //  *
    //  * @throws \Exception Si ocurre cualquier error
    //  */
    // public function updateVenta(int $id, array $data): Venta
    // {
    //     return DB::transaction(function () use ($id, $data) {
    //         $venta = Venta::findOrFail($id);
    //
    //         // 1) Revertir stock anterior (devolver productos)
    //         Producto::updateStock(true, $venta->detalles->toArray());
    //
    //         // 2) Procesar nuevos datos
    //         $ventaData = $this->processVentaData($data, false);
    //
    //         // 3) Actualizar cabecera y recrear detalles
    //         $venta->update($ventaData['venta']);
    //         $venta->detalles()->delete();
    //         $venta->detalles()->createMany($ventaData['detalles']);
    //
    //         // 4) Aplicar nuevo stock (salida)
    //         Producto::updateStock(false, $ventaData['detalles']);
    //
    //         return $venta;
    //     });
    // }

    // /**
    //  * Elimina una venta dentro de una transacción:
    //  * 1. Revierte el stock (devuelve productos al almacén)
    //  * 2. Elimina el registro de venta (cascade elimina detalles)
    //  *
    //  * @param  int  $id ID de la venta a eliminar
    //  * @return void
    //  *
    //  * @throws \Exception Si ocurre cualquier error
    //  */
    // public function deleteVenta(int $id): void
    // {
    //     DB::transaction(function () use ($id) {
    //         $registro = Venta::with('detalles')->findOrFail($id);
    //
    //         // Revertir stock (devolver productos)
    //         Producto::updateStock(true, $registro->detalles->toArray());
    //
    //         $registro->delete();
    //     });
    // }

    /**
     * Procesa y construye los datos de la venta (cabecera + detalles calculados).
     *
     * Consulta las entidades relacionadas (cliente, comprobante, pago, productos)
     * y genera los arrays listos para Venta::create() y detalles()->createMany().
     *
     * @param  array  $data  Datos validados del request
     * @param  bool  $isNew  true=nuevo registro (asigna user_id y estado), false=edición
     * @return array ['venta' => [...], 'detalles' => [...]]
     */
    private function processVentaData(array $data, bool $isNew = true): array
    {
        $moneda = $data['moneda'] ?? 'PEN';

        // Obtener entidades relacionadas
        $cliente = Cliente::find($data['cliente_id']);
        $comprobanteTipo = ComprobanteTipo::where('codigo', $data['comprobante_tipo_codigo'])->first();
        $pagoForma = PagoForma::where('codigo', $data['pago_forma_codigo'])->first();

        // Cargar productos involucrados con sus relaciones
        $productos = Producto::with('afectacionTipo', 'unidad')
            ->whereIn('id', collect($data['detalles'])->pluck('producto_id'))
            ->get()
            ->keyBy('id');

        // Totales recibidos del frontend (se confía en el cálculo del frontend)
        $totales = [
            'op_gravada' => $data['op_gravada'],
            'op_exonerada' => $data['op_exonerada'],
            'op_inafecta' => $data['op_inafecta'],
            'impuesto' => $data['impuesto'],
            'total' => $data['total'],
            'rentabilidad' => 0.00,
        ];

        // Calcular cada línea de detalle
        $detallesCalculados = [];
        foreach ($data['detalles'] as $detalle) {
            $detallesCalculados[] = $this->calculateDetail(
                $productos[$detalle['producto_id']],
                $detalle,
                $totales
            );
        }
        $totalItems = count($data['detalles']);

        // Construir array de la cabecera
        $ventaData = [
            'cliente_id' => $data['cliente_id'],
            'cliente_nombre' => $cliente->razon_social ?? '',
            'items' => $totalItems,
            'comprobante_tipo_codigo' => $data['comprobante_tipo_codigo'],
            'comprobante_tipo_nombre' => $comprobanteTipo->codigo ?? '',
            'serie' => $data['serie'],
            'correlativo' => $data['correlativo'],
            'docpagoi' => $data['docpagoi'],
            'fecha_venta' => $data['fecha_venta'] ?? now(),
            'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,
            'pago_forma_codigo' => $data['pago_forma_codigo'],
            'pago_forma_nombre' => $pagoForma->descripcion ?? '',
            'moneda' => $moneda,
            'op_gravada' => round($totales['op_gravada'], 2),
            'op_exonerada' => round($totales['op_exonerada'], 2),
            'op_inafecta' => round($totales['op_inafecta'], 2),
            'impuesto' => round($totales['impuesto'], 2),
            'total' => round($totales['total'], 2),
            'importe_p' => $data['principal'] ?? 0,
            'importe_d' => $data['deposito'] ?? 0,
            'importe_c' => $data['consorcio'] ?? 0,
            'acuenta' => $data['total_cobranza'] ?? '',
            'saldo' => round($totales['total'], 2) - ($data['total_cobranza'] ?? 0),
            'abonos' => 0.00,
            'rentabilidad' => round($totales['rentabilidad'], 4),
        ];

        // Campos exclusivos de creación
        if ($isNew) {
            $ventaData['estado'] = 'registrada';
            $ventaData['user_id'] = auth()->id();
            $ventaData['user_nombre'] = auth()->user()->name;
        }

        return [
            'venta' => $ventaData,
            'detalles' => $detallesCalculados,
        ];
    }

    /**
     * Calcula los valores de un detalle de venta individual.
     *
     * Determina: subtotal, impuesto, rentabilidad, conversión de empaque,
     * salida en sacos y kg, y costo unitario/total.
     *
     * @param  Producto  $producto  Producto con su afectacionTipo cargada
     * @param  array  $detalle  Datos del detalle desde el request
     * @param  array  &$totales  Array de totales acumulados (se modifica por referencia)
     * @return array Detalle listo para createMany()
     */
    private function calculateDetail($producto, $detalle, array &$totales): array
    {
        $unidad_codigo = $detalle['unidad_codigo'];
        $precio_unitario = $detalle['precio_unitario'];
        $entregado = $detalle['entrega'] ?? $detalle['cantidad'];
        $cantidad = $detalle['cantidad'];
        $empaque = $detalle['empaque'];
        $detalleTotal = $detalle['total'];

        // Cálculo de costo unitario considerando conversión de empaque
        $costoBase = (float) ($producto->costo_unitario ?? 0);
        $empaqueProducto = (float) ($producto->empaque ?? 1);
        $empaqueDetalle = (float) ($empaque ?? 1);

        // Evitar división entre cero
        if ($empaqueProducto <= 0) {
            $empaqueProducto = 1;
        }

        $costo_unitario = round(($costoBase / $empaqueProducto) * $empaqueDetalle, 4);
        $costo_total = round($costo_unitario * $cantidad, 4);

        $detalleTotalRound = round($detalleTotal, 4);
        $rentabilidad = $detalleTotalRound - $costo_total;
        $rentabilidadRed = round($rentabilidad, 4);

        // Cálculo de impuesto según tipo de afectación
        $porcentajeImpuesto = optional($producto->afectacionTipo)->porcentaje ?? 0;
        $subtotal = $porcentajeImpuesto > 0 ? $detalleTotal / (1 + $porcentajeImpuesto) : $detalleTotal;
        $detalleImpuesto = $detalleTotal - $subtotal;

        // Acumular rentabilidad en totales
        $totales['rentabilidad'] += $rentabilidadRed;

        return [
            'detalle' => 1,
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'producto_empaque' => $empaque ?? 0,
            'unidad_codigo' => $unidad_codigo ?? '',
            'salida_saco' => ($cantidad * $empaque) / $producto->empaque,
            'salida_kg' => $cantidad * $empaque,
            'cantidad' => $cantidad,
            'entregado' => $entregado,
            'saldo' => $cantidad - $entregado,
            'precio_unitario' => round($precio_unitario, 4),
            'subtotal' => round($subtotal, 2),
            'porcentaje_impuesto' => $porcentajeImpuesto,
            'impuesto' => round($detalleImpuesto, 2),
            'total' => round($detalleTotal, 2),
            'costo_unitario' => round($costo_unitario, 4),
            'costo_total' => round($costo_total, 4),
            'rentabilidad' => round($rentabilidadRed, 4),
        ];
    }

    /**
     * Calcula las cantidades (sacos, kg y stock base) de un detalle de
     * venta para registrar o restaurar un movimiento del kardex.
     *
     * Composición:
     *  - entregado    → cantidad real entregada al cliente (puede ser
     *                   menor a la cantidad vendida cuando hay entregas
     *                   parciales; es lo que DEBE mover el kardex).
     *  - empaque      → empaque del detalle (puede no coincidir con el
     *                   empaque base del producto).
     *  - salida (stock) → cantidad convertida a la unidad base del
     *                     producto (sacos) que descuenta del stock_almacen.
     *  - cantidad_kg  → total en kilogramos, derivado del empaque.
     *
     * Diferencia con calculateDetail(): este helper opera sobre un
     * VentaDetalle ya persistido y enfocado en el KARDEX, no en
     * precios/rentabilidad. Usa el campo `entregado` (no `cantidad`)
     * para mantener coherencia con createVenta().
     *
     * @param  \App\Models\VentaDetalle  $detalle  Detalle persistido
     * @return array{cantidad: float, cantidad_kg: float, salida: float, empaque: float}
     */
    private function calcularCantidadMovimientoVenta($detalle): array
    {
        $entregado = (float) $detalle->entregado;
        $empaqueDetalle = (float) $detalle->producto_empaque;
        $producto = Producto::find($detalle->producto_id);
        $empaqueProducto = (float) $producto->empaque;

        $cantidadStock = ($empaqueDetalle == $empaqueProducto)
            ? $entregado
            : round($entregado * ($empaqueDetalle / $empaqueProducto), 4);
        $cantidadKg = round($entregado * $empaqueDetalle, 4);

        return [
            'cantidad' => $entregado,
            'cantidad_kg' => $cantidadKg,
            'salida' => $cantidadStock,
            'empaque' => $empaqueDetalle,
        ];
    }
}
