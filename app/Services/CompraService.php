<?php

/**
 * Servicio de Compras.
 *
 * Concentra toda la lógica de negocio asociada a la creación de compras:
 * - Cálculo de totales e impuestos por detalle
 * - Registro de movimientos en el kardex (MovimientoService)
 * - Manejo de correlativo para Notas de Compra (NC)
 *
 * NOTA: Los métodos updateCompra() y deleteCompra() han sido COMENTADOS
 * porque ahora el sistema usa kardex valorizado. Las correcciones se
 * manejan mediante recálculo en cascada en MovimientoService.
 */

namespace App\Services;

use App\Models\Compra;
use App\Models\CompraProvisionalDetalle;
use App\Models\ComprobanteSerie;
use App\Models\ComprobanteTipo;
use App\Models\Movimiento;
use App\Models\PagoForma;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Unidad;
use Illuminate\Support\Facades\DB;

class CompraService
{
    /**
     * Inyección de servicios.
     */
    public function __construct(
        protected MovimientoService $movimientoService,
        protected CompraProvisionalService $provisionalService
    ) {}

    /**
     * Crea una compra completa dentro de una transacción:
     * 1. Procesa cabecera y detalles calculados
     * 2. Crea el registro Compra + CompraDetalles
     * 3. Registra movimientos de INGRESO en el kardex (CPP)
     * 4. Incrementa correlativo si es Nota de Compra (NC)
     *
     * @param  array  $data  Datos validados del request
     * @param  string  $comprobanteTipoCodigo  Código del tipo de comprobante
     * @param  string  $serie  Serie del comprobante
     * @param  int  $correlativo  Correlativo actual
     * @param  string  $estado  Estado inicial (registrada o rectificada)
     * @return Compra La compra recién creada
     *
     * @throws \Exception Si ocurre cualquier error
     */
    public function createCompra(array $data, string $comprobanteTipoCodigo, string $serie, int $correlativo, string $estado = 'registrada'): Compra
    {
        return DB::transaction(function () use ($data, $comprobanteTipoCodigo, $serie, $correlativo, $estado) {

            // 1) Procesar datos (cabecera + detalles calculados)
            $compraData = $this->processCompraData($data, true, $estado);

            // 2) Persistir compra y detalles
            $compra = Compra::create($compraData['compra']);
            $detallesCreados = $compra->detalles()->createMany($compraData['detalles']);

            // 3) Registrar movimientos de INGRESO en el kardex por cada detalle
            foreach ($detallesCreados as $detalle) {
                // CPP usa costo total por unidad = costo producto + costo servicio
                $costoCompletoPorUnidad = (float) $detalle->costo_unitario
                                        + (float) $detalle->costo_unitario_servicio;

                $this->movimientoService->registrarIngreso([
                    'tipo' => MovimientoService::TIPO_COMPRA,
                    'fecha' => $compra->fecha_compra,
                    'transaccion_tipo' => 'compras',
                    'transaccion_id' => $compra->id,
                    'detalle_id' => $detalle->id,
                    'producto_id' => $detalle->producto_id,
                    'producto_nombre' => $detalle->producto_nombre,
                    'empaque' => $detalle->producto_empaque,
                    'unidad_codigo' => $detalle->unidad_codigo,
                    'cantidad' => $detalle->cantidad,
                    'cantidad_kg' => $detalle->cantidad_kgm,
                    'costo_unitario' => $costoCompletoPorUnidad,
                ]);
            }

            // ────────────────────────────────────────────────────────────
            // NOTA: Recálculo retroactivo en cascada
            // ────────────────────────────────────────────────────────────
            // El recálculo del kardex para movimientos con fecha pasada
            // (ej: registrar una compra de la mañana después de ventas
            // de la tarde) se hace automáticamente dentro de
            // MovimientoService::registrarIngreso() cuando detecta que
            // la fecha del movimiento es anterior al último movimiento
            // del producto.
            //
            // Antes este bloque hacía un recálculo extra cuando la
            // fecha era < today(). Era redundante: el recalc ya se
            // ejecutó al insertar el movimiento. Eliminado para:
            //   1. Evitar doble recálculo (costo en performance).
            //   2. Tener un solo punto de recálculo retroactivo.
            //   3. No depender de la fecha actual: la detección ya
            //      usa el último movimiento del producto.
            // ────────────────────────────────────────────────────────────
            // 3.1) Recalcular en cascada si la fecha de compra es anterior a hoy
            // IMPORTANTE: se pasa datetime completo (Y-m-d H:i:s) para que el punto de partida
            // sea el último movimiento ANTES de la hora exacta, no antes del día completo.
            $fechaCompra = $compra->fecha_compra instanceof \Carbon\Carbon
                ? $compra->fecha_compra
                : \Carbon\Carbon::parse($compra->fecha_compra);

            if ($fechaCompra->lt(today())) {
                foreach ($detallesCreados as $detalle) {
                    $this->movimientoService->recalcularKardexProductoDesdeFecha(
                        $detalle->producto_id,
                        $fechaCompra->format('Y-m-d H:i:s')
                    );
                }
            }
            // 4) Incrementar correlativo solo para Notas de Compra (NC)
            if ($comprobanteTipoCodigo === 'NC') {
                ComprobanteSerie::where('comprobante_tipo_codigo', $comprobanteTipoCodigo)
                    ->where('serie', $serie)
                    ->update([
                        'correlativo' => $correlativo + 1,
                    ]);
            }

            return $compra;
        });
    }

    /**
     * Rectifica una compra previamente anulada.
     * RESTAURA los movimientos originales neutralizados para mantener la
     * posición cronológica y calcular con stock previo exacto.
     *
     * @param  int  $compraAnuladaId  ID de la compra anulada original
     * @param  array  $data  Datos de la nueva compra
     */
    public function rectificarCompra(
        int $compraAnuladaId,
        array $data,
        string $comprobanteTipoCodigo,
        string $serie,
        int $correlativo
    ): Compra {
        return DB::transaction(function () use (
            $compraAnuladaId, $data
        ) {
            $compraAnulada = Compra::with('detalles')->findOrFail($compraAnuladaId);

            // Validar que esté anulada
            if ($compraAnulada->estado !== 'anulada') {
                throw new \Exception('Solo se pueden rectificar compras anuladas.');
            }

            if ($compraAnulada->rectificacion_count >= 3) {
                throw new \Exception('Esta compra ya no puede ser rectificada. Máximo 3 rectificaciones permitidas.');
            }

            // Fuente de verdad: Sumar los abonos reales desde los detalles provisionales
            $abonosTotales = (float) CompraProvisionalDetalle::where('compra_id', $compraAnuladaId)->sum('monto');

            // Obtener datos procesados (pasamos false a isNew porque actualizamos)
            // Incluimos los abonos calculados para el cálculo correcto del saldo
            $compraData = $this->processCompraData($data, false, 'rectificada', $abonosTotales);

            // 1) Actualizar la compra original IN-PLACE
            // Mantenemos el ID original, forzamos serie 01 y estado rectificada
            $compraAnulada->update(array_merge($compraData['compra'], [
                'serie' => '01',
                'estado' => 'rectificada',
                'nota' => trim($compraAnulada->nota.' | Rectificada en fecha: '.now()->format('Y-m-d H:i:s')),
            ]));

            // Incrementar contador de rectificaciones
            $compraAnulada->update(['rectificacion_count' => $compraAnulada->rectificacion_count + 1]);

            // 2) Reemplazar detalles: eliminamos los anteriores y creamos los nuevos
            $compraAnulada->detalles()->delete();
            $detallesCreados = $compraAnulada->detalles()->createMany($compraData['detalles']);

            // ────────────────────────────────────────────────────────────
            // BLOQUE: Reconstrucción de movimientos del kardex
            // ────────────────────────────────────────────────────────────
            // ¿Qué hace?: Tras reemplazar los detalles de la compra, cada
            //              línea debe quedar representada en el kardex.
            //              Para cada detalle:
            //                1. Busca un movimiento neutralizado previo
            //                   (de cuando se anuló la compra) para el
            //                   mismo producto y lo restaura con los
            //                   nuevos valores.
            //                2. Si no existe, crea uno nuevo desde cero.
            //
            // ¿Por qué $movimientosUsados?:  Una compra puede tener
            //   dos líneas con el mismo producto (lotes distintos en
            //   la misma factura). Sin este tracking, ambas líneas
            //   encontrarían el mismo movimiento neutralizado y lo
            //   sobrescribirían, dejando una línea sin restaurar.
            //
            // ¿Por qué salida=0 explícito al restaurar?:  Aunque el
            //   movimiento ya quedó con salida=0 tras la neutralización,
            //   dejarlo explícito en el update protege contra datos
            //   históricos inconsistentes y deja la intención clara.
            // ────────────────────────────────────────────────────────────
            $productosAfectados = [];
            $movimientosUsados = [];

            foreach ($detallesCreados as $detalle) {
                $movNeutralizado = Movimiento::where('transaccion_tipo', 'compras')
                    ->where('transaccion_id', $compraAnuladaId)
                    ->where('producto_id', $detalle->producto_id)
                    ->where('cantidad', 0)
                    ->where('cantidad_kg', 0)
                    ->whereNotIn('id', $movimientosUsados)
                    ->orderBy('id', 'asc')
                    ->first();

                $costos = $this->calcularCostoMovimientoCompra($detalle);

                if ($movNeutralizado) {
                    // Restauramos el movimiento original neutralizado.
                    $movNeutralizado->update([
                        'detalle_id' => $detalle->id,
                        'fecha' => $compraAnulada->fecha_compra,
                        'empaque' => $costos['empaque'],
                        'unidad_codigo' => $detalle->unidad_codigo,
                        'cantidad' => $detalle->cantidad,
                        'cantidad_kg' => $detalle->cantidad_kgm,
                        'entrada' => $costos['cantidad_stock'],
                        'salida' => 0,
                        'costo_unitario' => $costos['costo_unitario_base'],
                        'costo_total' => $costos['costo_total'],
                        'comentario' => 'Rectificación de compra (restaurado)',
                    ]);
                    $movimientosUsados[] = $movNeutralizado->id;
                } else {
                    // Sin mov neutralizado previo (ej: producto nuevo
                    // en la rectificación). registrarIngreso() calcula
                    // el CPP real automáticamente.
                    $this->movimientoService->registrarIngreso([
                        'tipo' => MovimientoService::TIPO_COMPRA,
                        'fecha' => $compraAnulada->fecha_compra,
                        'transaccion_tipo' => 'compras',
                        'transaccion_id' => $compraAnulada->id,
                        'detalle_id' => $detalle->id,
                        'producto_id' => $detalle->producto_id,
                        'producto_nombre' => $detalle->producto_nombre,
                        'empaque' => $detalle->producto_empaque,
                        'unidad_codigo' => $detalle->unidad_codigo,
                        'cantidad' => $detalle->cantidad,
                        'cantidad_kg' => $detalle->cantidad_kgm,
                        'costo_unitario' => $costos['costo_completo_por_unidad'],
                    ]);
                }
                $productosAfectados[] = $detalle->producto_id;
            }

            // Recalcular Kardex para productos afectados
            $fechaCompra = $compraAnulada->fecha_compra instanceof \Carbon\Carbon
                ? $compraAnulada->fecha_compra
                : \Carbon\Carbon::parse($compraAnulada->fecha_compra);

            foreach (array_unique($productosAfectados) as $productoId) {
                if ($fechaCompra->lt(today())) {
                    // Fecha pasada: recalcular en cascada desde la fecha+hora de la compra
                    // IMPORTANTE: se pasa datetime completo (Y-m-d H:i:s) para que el punto de
                    // partida sea el último movimiento ANTES de la hora exacta de la compra.
                    $this->movimientoService->recalcularKardexProductoDesdeFecha(
                        $productoId,
                        $fechaCompra->format('Y-m-d H:i:s')
                    );
                } else {
                    // Fecha actual o futura: recalcular desde el primer movimiento
                    $primerMov = Movimiento::where('transaccion_id', $compraAnulada->id)
                        ->where('producto_id', $productoId)
                        ->orderBy('id', 'asc')
                        ->first();

                    if ($primerMov) {
                        $this->movimientoService->recalcularKardexProducto(
                            $productoId,
                            $primerMov->id
                        );
                    }
                }
            }

            return $compraAnulada;
        });
    }

    /**
     * Anula una compra existente dentro de una transacción:
     * 1. Valida que la compra no esté ya anulada
     * 2. Cambia el estado a 'anulada'
     * 3. Registra movimientos de reversión (SALIDA) por cada detalle
     * 4. Recalcula el kardex en cascada para cada producto afectado
     *
     * @param  int  $id  ID de la compra a anular
     * @return Compra La compra anulada
     *
     * @throws \Exception Si la compra ya está anulada o si ocurre error
     */
    public function anularCompra(int $id): Compra
    {
        return DB::transaction(function () use ($id) {
            $compra = Compra::with('detalles')->findOrFail($id);

            // Validar que no esté ya anulada
            if ($compra->estado === 'anulada') {
                throw new \Exception('Esta compra ya fue anulada.');
            }

            // Si está rectificada, permitir anular para volver a rectificar (si count < 3)
            if ($compra->estado === 'rectificada') {
                if ($compra->rectificacion_count >= 3) {
                    throw new \Exception('Esta compra ya no puede ser rectificada. Máximo 3 rectificaciones permitidas.');
                }
                // Permitir: se volverá a 'anulada' para poder rectificar de nuevo
            }

            // 1) Cambiar estado a 'anulada'
            $compra->update(['estado' => 'anulada']);

            // 2) Recopilar IDs de productos afectados
            $productosAfectados = [];
            foreach ($compra->detalles as $detalle) {
                if (! in_array($detalle->producto_id, $productosAfectados)) {
                    $productosAfectados[] = $detalle->producto_id;
                }
            }

            // 3) Para cada producto, obtener los IDs de movimientos originales
            //    de esta compra y recalcular excluyéndolos.
            //    Esto toma stock_anterior y costo_actual del primer movimiento
            //    (estado PRE-compra) y recalcula todo desde ahí sin la compra.
            foreach ($productosAfectados as $productoId) {
                $movIds = Movimiento::where('transaccion_tipo', 'compras')
                    ->where('transaccion_id', $compra->id)
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

            return $compra;
        });
    }

    /* ============================================================
     * COMENTADO: Migrado a sistema de Kardex Valorizado.
     *
     * Las compras ya NO se pueden editar ni eliminar directamente.
     * Las correcciones se manejan mediante recálculo en cascada
     * usando MovimientoService::recalcularKardexProducto().
     * ============================================================ */

    // /**
    //  * Actualiza una compra existente dentro de una transacción:
    //  * 1. Revierte el stock anterior (quita productos del almacén)
    //  * 2. Recalcula cabecera y detalles con los nuevos datos
    //  * 3. Reemplaza los detalles (delete + createMany)
    //  * 4. Aplica el nuevo stock (ingreso de productos)
    //  *
    //  * @param  int   $id   ID de la compra a actualizar
    //  * @param  array $data Datos validados del request
    //  * @return Compra La compra actualizada
    //  *
    //  * @throws \Exception Si ocurre cualquier error
    //  */
    // public function updateCompra(int $id, array $data): Compra
    // {
    //     return DB::transaction(function () use ($id, $data) {
    //         $compra = Compra::findOrFail($id);
    //
    //         // 1) Revertir stock anterior (quitar productos ingresados)
    //         Producto::updateStock(false, $compra->detalles->toArray());
    //
    //         // 2) Procesar nuevos datos
    //         $compraData = $this->processCompraData($data, false);
    //
    //         // 3) Actualizar cabecera y recrear detalles
    //         $compra->update($compraData['compra']);
    //         $compra->detalles()->delete();
    //         $compra->detalles()->createMany($compraData['detalles']);
    //
    //         // 4) Aplicar nuevo stock (ingreso)
    //         Producto::updateStock(true, $compraData['detalles']);
    //
    //         return $compra;
    //     });
    // }

    // /**
    //  * Elimina una compra dentro de una transacción:
    //  * 1. Revierte el stock (quita productos ingresados)
    //  * 2. Elimina el registro de compra (cascade elimina detalles)
    //  *
    //  * @param  int  $id ID de la compra a eliminar
    //  * @return void
    //  *
    //  * @throws \Exception Si ocurre cualquier error
    //  */
    // public function deleteCompra(int $id): void
    // {
    //     DB::transaction(function () use ($id) {
    //         $registro = Compra::with('detalles')->findOrFail($id);
    //
    //         // Revertir stock (quitar productos ingresados)
    //         Producto::updateStock(false, $registro->detalles->toArray());
    //
    //         $registro->delete();
    //     });
    // }

    /**
     * Procesa y construye los datos de la compra (cabecera + detalles calculados).
     *
     * Consulta las entidades relacionadas (proveedor, comprobante, pago, productos)
     * y genera los arrays listos para Compra::create() y detalles()->createMany().
     *
     * @param  array  $data  Datos validados del request
     * @param  bool  $isNew  true=nuevo registro (asigna user_id y estado), false=edición
     * @param  string  $estado  Estado a asignar (por defecto 'registrada')
     * @param  float  $abonos  Abonos previos acumulados (útil en rectificaciones)
     * @return array ['compra' => [...], 'detalles' => [...]]
     */
    private function processCompraData(array $data, bool $isNew = true, string $estado = 'registrada', float $abonos = 0): array
    {
        $moneda = $data['moneda'] ?? 'PEN';

        // Obtener entidades relacionadas
        $proveedor = Proveedor::find($data['proveedor_id']);
        $comprobanteTipo = ComprobanteTipo::where('codigo', $data['comprobante_tipo_codigo'])->first();
        $pagoForma = PagoForma::where('codigo', $data['pago_forma_codigo'])->first();

        // Cargar productos involucrados con sus relaciones
        $productos = Producto::with('afectacionTipo', 'unidad')
            ->whereIn('id', collect($data['detalles'])->pluck('producto_id'))
            ->get()
            ->keyBy('id');

        // Totales recibidos del frontend
        $totales = [
            'op_gravada' => $data['op_gravada'],
            'op_exonerada' => $data['op_exonerada'],
            'op_inafecta' => $data['op_inafecta'],
            'impuesto' => $data['impuesto'],
            'total' => $data['total'],
        ];

        // Calcular cada línea de detalle
        $detallesCalculados = [];
        foreach ($data['detalles'] as $detalle) {
            $detallesCalculados[] = $this->calculateDetail(
                $productos[$detalle['producto_id']],
                $detalle['cantidad'],
                $detalle['precio_unitario'],
                $detalle['precio_unitario_servicio'],
                $totales,
                $detalle
            );
        }

        // Construir array de la cabecera
        $compraData = [
            'proveedor_id' => $data['proveedor_id'],
            'proveedor_nombre' => $proveedor->razon_social ?? '',
            'comprobante_tipo_codigo' => $data['comprobante_tipo_codigo'],
            'comprobante_tipo_nombre' => $comprobanteTipo->codigo ?? '',
            'serie' => $data['serie'],
            'correlativo' => $data['correlativo'],
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
            'acuenta' => (float) ($data['total_cobranza'] ?? 0),
            'abonos' => $abonos,
            'saldo' => round($totales['total'], 2) - (float) ($data['total_cobranza'] ?? 0) - $abonos,
            'fecha_compra' => $data['fecha_compra'] ?? now(),
            'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,
        ];

        // Campos exclusivos de creación
        if ($isNew) {
            $compraData['estado'] = $estado;
            $compraData['user_id'] = auth()->id();
            $compraData['user_nombre'] = auth()->user()->name;
        }

        return [
            'compra' => $compraData,
            'detalles' => $detallesCalculados,
        ];
    }

    /**
     * Calcula los valores de un detalle de compra individual.
     *
     * Determina: subtotal, impuesto, empaque y conversión de unidades.
     *
     * @param  Producto  $producto  Producto con su afectacionTipo cargada
     * @param  float  $cantidad  Cantidad comprada
     * @param  float  $precio_unitario_input  Precio unitario del producto
     * @param  float  $precio_servicio_input  Precio de servicio adicional
     * @param  array  &$totales  Array de totales acumulados (referencia)
     * @param  array  $detalle  Datos del detalle desde el request
     * @return array Detalle listo para createMany()
     */
    private function calculateDetail($producto, $cantidad, $precio_unitario_input, $precio_servicio_input, array &$totales, $detalle): array
    {
        $precio_unitario = $precio_unitario_input;
        $precio_unitario_servicio = $precio_servicio_input;
        $detalleTotal = $detalle['total'];

        // Cálculo de impuesto según tipo de afectación
        $porcentajeImpuesto = optional($producto->afectacionTipo)->porcentaje ?? 0;
        $subtotal = $porcentajeImpuesto > 0 ? $detalleTotal / (1 + $porcentajeImpuesto) : $detalleTotal;
        $detalleImpuesto = $detalleTotal - $subtotal;

        $unidad_codigo = $detalle['unidad_codigo'];
        $unidad_descripcion = Unidad::find($unidad_codigo)?->descripcion;
        $empaque = $detalle['empaque'];

        return [
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'unidad_codigo' => $unidad_codigo ?? '',
            'producto_empaque' => $empaque ?? 0,
            'cantidad' => $cantidad,
            'cantidad_kgm' => $cantidad * $empaque,
            'costo_unitario' => round($precio_unitario, 4),
            'costo_unitario_servicio' => round($precio_unitario_servicio, 2),
            'subtotal' => round($subtotal, 2),
            'porcentaje_impuesto' => $porcentajeImpuesto,
            'impuesto' => round($detalleImpuesto, 2),
            'total' => round($detalleTotal, 2),
        ];
    }

    /**
     * Calcula las cantidades y costos de un detalle de compra para
     * registrar o restaurar un movimiento del kardex.
     *
     * Composición:
     *  - empaque                  → empaque del detalle (puede no
     *                               coincidir con el empaque base del
     *                               producto; ej: 25kg vs 50kg).
     *  - cantidad_stock           → unidades en BASE del producto
     *                               (sacos) que se suman al stock_almacen.
     *  - costo_completo_por_unidad → costo_unitario + costo_servicio
     *                               (precio real de compra + flete).
     *  - costo_total              → costo_completo × cantidad comprada.
     *  - costo_unitario_base      → costo prorrateado a la unidad base
     *                               del producto (se usa en kardex).
     *
     * Diferencia con calculateDetail(): este helper opera sobre un
     * CompraDetalle ya persistido y enfocado en IMPACTO KARDEX
     * (stock + CPP), no en subtotales/impuestos de la factura.
     *
     * @param  \App\Models\CompraDetalle  $detalle  Detalle persistido
     * @return array{
     *   empaque: float,
     *   cantidad_stock: float,
     *   costo_completo_por_unidad: float,
     *   costo_total: float,
     *   costo_unitario_base: float
     * }
     */
    private function calcularCostoMovimientoCompra($detalle): array
    {
        $empaqueDetalle = (float) $detalle->producto_empaque;
        $producto = Producto::find($detalle->producto_id);
        $empaqueProducto = (float) $producto->empaque;

        $cantidadStock = ($empaqueDetalle == $empaqueProducto)
            ? (float) $detalle->cantidad
            : round((float) $detalle->cantidad * ($empaqueDetalle / $empaqueProducto), 4);

        $costoCompletoPorUnidad = (float) $detalle->costo_unitario
            + (float) $detalle->costo_unitario_servicio;

        $costoTotalCompra = (float) $detalle->cantidad * $costoCompletoPorUnidad;

        $costoUnitarioBase = $cantidadStock > 0
            ? round($costoTotalCompra / $cantidadStock, 4)
            : round($costoCompletoPorUnidad, 4);

        return [
            'empaque' => $empaqueDetalle,
            'cantidad_stock' => $cantidadStock,
            'costo_completo_por_unidad' => $costoCompletoPorUnidad,
            'costo_total' => $costoTotalCompra,
            'costo_unitario_base' => $costoUnitarioBase,
        ];
    }
}
