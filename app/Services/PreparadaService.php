<?php

/**
 * Servicio de Preparadas (Producción).
 *
 * Concentra toda la lógica de negocio asociada a la creación
 * de preparadas (registros de producción):
 * - Cálculo de costos de insumos y totales de producción
 * - Registro de movimientos de SALIDA (insumos) e INGRESO (producto final)
 *   en el kardex valorizado mediante MovimientoService
 * - Vinculación con formulaciones (recetas)
 *
 * NOTA: Los métodos updatePreparada() y deletePreparada() han sido COMENTADOS
 * porque ahora el sistema usa kardex valorizado.
 */

namespace App\Services;

use App\Models\Formulacion;
use App\Models\Movimiento;
use App\Models\Preparada;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class PreparadaService
{
    /**
     * Inyección del servicio de movimientos para registrar
     * salidas e ingresos en el kardex valorizado.
     */
    public function __construct(
        protected MovimientoService $movimientoService
    ) {}

    /**
     * Crea una preparada (registro de producción) dentro de una transacción:
     * 1. Procesa cabecera y detalles calculados desde la formulación
     * 2. Crea el registro Preparada + PreparadaDetalles
     * 3. Registra movimientos de SALIDA por cada insumo en el kardex
     * 4. Registra movimiento de INGRESO del producto final en el kardex
     *
     * @param  array  $data  Datos validados del request
     * @return Preparada La preparada recién creada (con detalles cargados)
     *
     * @throws \Exception Si ocurre cualquier error
     */
    public function createPreparada(array $data): Preparada
    {
        return DB::transaction(function () use ($data) {

            // 1) Procesar datos (cabecera + detalles calculados)
            $preparadaData = $this->proccessPreparadaData($data, true);

            // 2) Persistir preparada y detalles
            $preparada = Preparada::create($preparadaData['preparada']);
            $detallesCreados = $preparada->detalles()->createMany($preparadaData['detalles']);
            $preparada->load('detalles');

            // 3) Registrar SALIDA de insumos en el kardex
            //    Se excluyen productos con salida_kg = 0 (como el producto final en la receta)
            //    y el producto ID 77 (servicio de mezclado, no es producto físico)
            foreach ($detallesCreados as $detalle) {
                // Saltar si no hay salida real de kg o es servicio de mezclado (ID 77)
                if ((float) $detalle->salida_kg <= 0 || (int) $detalle->producto_id === 77) {
                    continue;
                }

                $this->movimientoService->registrarSalida([
                    'tipo' => MovimientoService::TIPO_PREPARADA_SALIDA,
                    'fecha' => $preparada->fecha,
                    'transaccion_tipo' => 'preparadas',
                    'transaccion_id' => $preparada->id,
                    'detalle_id' => $detalle->id,
                    'producto_id' => $detalle->producto_id,
                    'producto_nombre' => $detalle->producto_nombre,
                    'empaque' => $detalle->producto_empaque,
                    'unidad_codigo' => null,
                    'cantidad' => $detalle->salida_saco,
                    'cantidad_kg' => $detalle->salida_kg,
                ]);
            }

            // 4) Registrar INGRESO del producto final en el kardex
            $this->movimientoService->registrarIngreso([
                'tipo' => MovimientoService::TIPO_PREPARADA_INGRESO,
                'fecha' => $preparada->fecha,
                'transaccion_tipo' => 'preparadas',
                'transaccion_id' => $preparada->id,
                'detalle_id' => null,
                'producto_id' => $preparada->producto_id,
                'producto_nombre' => $preparada->producto_nombre,
                'empaque' => $preparada->producto_empaque,
                'unidad_codigo' => null,
                'cantidad' => $preparada->ingreso_saco,
                'cantidad_kg' => $preparada->ingreso_kg,
                'costo_unitario' => $preparada->costo_unitario,
                'costo_saco' => $preparada->costo_unitario,
            ]);

            return $preparada;
        });
    }

    /**
     * Anula una preparada (producción) existente dentro de una transacción:
     * 1. Valida que no esté ya anulada
     * 2. Cambia el estado a 'anulada'
     * 3. Por cada insumo consumido: registra INGRESO (devuelve insumo al almacén)
     * 4. Por el producto final producido: registra SALIDA (descuenta del almacén)
     * 5. Recalcula el kardex en cascada para todos los productos afectados
     *
     * @param  int  $id  ID de la preparada a anular
     * @return Preparada La preparada anulada
     *
     * @throws \Exception Si la preparada ya está anulada o si ocurre error
     */
    public function anularPreparada(int $id): Preparada
    {
        return DB::transaction(function () use ($id) {
            $preparada = Preparada::with('detalles')->findOrFail($id);

            // Validar estado
            if ($preparada->estado === 'anulada') {
                throw new \Exception('Esta preparada ya fue anulada.');
            }

            // Si está rectificada, permitir anular para volver a rectificar (si count < 3)
            if ($preparada->estado === 'rectificada') {
                if ($preparada->rectificacion_count >= 3) {
                    throw new \Exception('Esta preparada ya no puede ser rectificada. Máximo 3 rectificaciones permitidas.');
                }
                // Permitir: se volverá a 'anulada' para poder rectificar de nuevo
            }

            // 1) Cambiar estado a 'anulada'
            $preparada->update(['estado' => 'anulada']);

            // 2) Recopilar productos afectados (insumos + producto final)
            $productosAfectados = [];

            // Insumos consumidos
            foreach ($preparada->detalles as $detalle) {
                if ((float) $detalle->salida_kg <= 0 || (int) $detalle->producto_id === 77) {
                    continue;
                }
                if (! in_array($detalle->producto_id, $productosAfectados)) {
                    $productosAfectados[] = $detalle->producto_id;
                }
            }

            // Producto final producido
            if (! in_array($preparada->producto_id, $productosAfectados)) {
                $productosAfectados[] = $preparada->producto_id;
            }

            // 3) Para cada producto, obtener los IDs de movimientos originales
            //    de esta preparada y recalcular excluyéndolos.
            //    Esto toma stock_anterior y costo_actual del primer movimiento
            //    (estado PRE-preparada) y recalcula todo desde ahí sin la preparada.
            foreach ($productosAfectados as $productoId) {
                $movIds = Movimiento::where('transaccion_tipo', 'preparadas')
                    ->where('transaccion_id', $preparada->id)
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

            return $preparada;
        });
    }

    /**
     * Rectifica una preparada previamente anulada (Actualización in-situ).
     * Modifica el registro existente en lugar de crear uno nuevo.
     *
     * @param  int  $preparadaId  ID de la preparada anulada
     * @param  array  $data  Datos validados del request
     */
    public function rectificarPreparada(int $preparadaId, array $data): array
    {
        return DB::transaction(function () use ($preparadaId, $data) {
            $preparada = Preparada::findOrFail($preparadaId);

            if ($preparada->estado !== 'anulada') {
                throw new \Exception('Solo se pueden rectificar preparadas en estado anulada.');
            }

            if ($preparada->rectificacion_count >= 3) {
                throw new \Exception('Esta preparada ya no puede ser rectificada. Máximo 3 rectificaciones permitidas.');
            }

            // 1) Procesar datos (cabecera + detalles calculados)
            $preparadaDataRaw = $this->proccessPreparadaData($data, false);
            $preparadaData = $preparadaDataRaw['preparada'];

            // Forzamos estado y trazabilidad
            $preparadaData['estado'] = 'rectificada';
            $preparadaData['nota'] = trim(($data['nota'] ?? $preparada->nota).' | Rectificada el '.now()->format('d/m/Y H:i'));

            // 2) Actualizar la cabecera del registro existente
            $preparada->update($preparadaData);

            // Incrementar contador de rectificaciones
            $preparada->update(['rectificacion_count' => $preparada->rectificacion_count + 1]);

            // 3) Reemplazar detalles
            $preparada->detalles()->delete();
            $detallesNuevos = $preparada->detalles()->createMany($preparadaDataRaw['detalles']);
            $preparada->load('detalles');

            $productosAfectados = [];
            // ────────────────────────────────────────────────────────────
            // BLOQUE: Reconstrucción de movimientos del kardex
            // ────────────────────────────────────────────────────────────
            // ¿Qué hace?: Tras reemplazar los detalles de la preparada,
            //   restaura los movimientos neutralizados de SALIDA de
            //   insumos y del INGRESO del producto final.
            //
            // ¿Por qué $movimientosUsados?:  Una preparada puede tener
            //   dos insumos del mismo producto (ej: CALCIO de 2 lotes
            //   distintos). Sin este tracking, ambas líneas encontrarían
            //   el mismo movimiento neutralizado y lo sobrescribirían,
            //   dejando una línea sin restaurar. El mismo array protege
            //   también el producto final si su id coincide con un
            //   insumo.
            // ────────────────────────────────────────────────────────────
            $movimientosUsados = [];

            // 4) Reemplazar movimientos de SALIDA de insumos en el kardex
            foreach ($detallesNuevos as $detalle) {
                if ((float) $detalle->salida_kg <= 0 || (int) $detalle->producto_id === 77) {
                    continue;
                }

                $movNeutralizado = Movimiento::where('transaccion_tipo', 'preparadas')
                    ->where('transaccion_id', $preparadaId)
                    ->where('producto_id', $detalle->producto_id)
                    ->where('entrada', 0)
                    ->where('salida', 0)
                    ->where('cantidad_kg', 0)
                    ->whereNotIn('id', $movimientosUsados)
                    ->orderBy('id', 'asc')
                    ->first();

                if ($movNeutralizado) {
                    $producto = Producto::find($detalle->producto_id);
                    $empaqueBase = (float) ($producto->empaque ?? 1);
                    $empaqueFinal = (float) ($detalle->producto_empaque ?? 1);
                    $cantidad = (float) $detalle->salida_saco;
                    $cantidadStock = ($empaqueFinal == $empaqueBase)
                        ? $cantidad
                        : round($cantidad * ($empaqueFinal / $empaqueBase), 4);

                    $movNeutralizado->update([
                        'detalle_id' => $detalle->id,
                        'fecha' => $preparada->fecha,
                        'producto_nombre' => $detalle->producto_nombre,
                        'empaque' => $empaqueFinal,
                        'unidad_codigo' => null,
                        'cantidad' => $cantidad,
                        'cantidad_kg' => $detalle->salida_kg,
                        'entrada' => 0,
                        'salida' => $cantidadStock,
                        'comentario' => 'Rectificación de preparada (Actualizado)',
                    ]);
                    $movimientosUsados[] = $movNeutralizado->id;
                    $productosAfectados[] = $detalle->producto_id;
                } else {
                    $this->movimientoService->registrarSalida([
                        'tipo' => MovimientoService::TIPO_PREPARADA_SALIDA,
                        'fecha' => $preparada->fecha,
                        'transaccion_tipo' => 'preparadas',
                        'transaccion_id' => $preparada->id,
                        'detalle_id' => $detalle->id,
                        'producto_id' => $detalle->producto_id,
                        'producto_nombre' => $detalle->producto_nombre,
                        'empaque' => $detalle->producto_empaque,
                        'unidad_codigo' => null,
                        'cantidad' => $detalle->salida_saco,
                        'cantidad_kg' => $detalle->salida_kg,
                        'comentario' => 'Rectificación de preparada',
                    ]);
                    $productosAfectados[] = $detalle->producto_id;
                }
            }

            // 5) Reemplazar movimiento INGRESO del producto final en el kardex
            $movNeutralizadoProd = Movimiento::where('transaccion_tipo', 'preparadas')
                ->where('transaccion_id', $preparadaId)
                ->where('producto_id', $preparada->producto_id)
                ->where('entrada', 0)
                ->where('salida', 0)
                ->where('cantidad_kg', 0)
                ->whereNotIn('id', $movimientosUsados)
                ->orderBy('id', 'asc')
                ->first();

            if ($movNeutralizadoProd) {
                $productoFinal = Producto::find($preparada->producto_id);
                $stockAnteriorProd = (float) $productoFinal->stock_almacen;
                $empaqueBase = (float) ($productoFinal->empaque ?? 1);
                $empaqueFinal = (float) ($preparada->producto_empaque ?? 1);
                $cantidad = (float) $preparada->ingreso_saco;

                $cantidadStock = ($empaqueFinal == $empaqueBase)
                    ? $cantidad
                    : round($cantidad * ($empaqueFinal / $empaqueBase), 4);

                $costoTotalPrep = (float) ($cantidad * $preparada->costo_unitario);
                $costoUnitarioBase = $cantidadStock > 0 ? $costoTotalPrep / $cantidadStock : (float) $preparada->costo_unitario;

                $stockNuevoProd = round($stockAnteriorProd + $cantidadStock, 4);
                $costoSacoPrep = (float) $preparada->costo_unitario;

                if ($stockAnteriorProd <= 0 && $costoSacoPrep > 0) {
                    $costoUnitarioBase = $costoSacoPrep;
                    $costoTotalPrep = $stockNuevoProd * $costoSacoPrep;
                }

                $movNeutralizadoProd->update([
                    'fecha' => $preparada->fecha,
                    'producto_nombre' => $preparada->producto_nombre,
                    'empaque' => $empaqueFinal,
                    'unidad_codigo' => $productoFinal->unidad_codigo ?? null,
                    'cantidad' => $cantidad,
                    'cantidad_kg' => $preparada->ingreso_kg,
                    'entrada' => $cantidadStock,
                    'salida' => 0,
                    'costo_unitario' => round($costoUnitarioBase, 4),
                    'costo_total' => round($costoTotalPrep, 4),
                    'comentario' => 'Rectificación de preparada (Actualizado)',
                ]);
                $productosAfectados[] = $preparada->producto_id;
            } else {
                $this->movimientoService->registrarIngreso([
                    'tipo' => MovimientoService::TIPO_PREPARADA_INGRESO,
                    'fecha' => $preparada->fecha,
                    'transaccion_tipo' => 'preparadas',
                    'transaccion_id' => $preparada->id,
                    'detalle_id' => null,
                    'producto_id' => $preparada->producto_id,
                    'producto_nombre' => $preparada->producto_nombre,
                    'empaque' => $preparada->producto_empaque,
                    'unidad_codigo' => null,
                    'cantidad' => $preparada->ingreso_saco,
                    'cantidad_kg' => $preparada->ingreso_kg,
                    'costo_unitario' => $preparada->costo_unitario,
                    'costo_saco' => $preparada->costo_unitario,
                    'comentario' => 'Rectificación de preparada',
                ]);
                $productosAfectados[] = $preparada->producto_id;
            }

            // 6) Recalcular Kardex para productos afectados
            $fechaPreparada = $preparada->fecha instanceof \Carbon\Carbon
                ? $preparada->fecha
                : \Carbon\Carbon::parse($preparada->fecha);

            foreach (array_unique($productosAfectados) as $pId) {
                if ($fechaPreparada->lt(today())) {
                    // IMPORTANTE: datetime completo para punto de partida exacto (por hora, no solo fecha)
                    $this->movimientoService->recalcularKardexProductoDesdeFecha(
                        $pId,
                        $fechaPreparada->format('Y-m-d H:i:s')
                    );
                } else {
                    $primerMov = Movimiento::where('transaccion_id', $preparada->id)
                        ->where('producto_id', $pId)
                        ->orderBy('id', 'asc')
                        ->first();
                    if ($primerMov) {
                        $this->movimientoService->recalcularKardexProducto($pId, $primerMov->id);
                    }
                }
            }

            return [
                'preparada' => $preparada,
                'detalles' => $detallesNuevos,
            ];
        });
    }

    /* ============================================================
     * COMENTADO: Migrado a sistema de Kardex Valorizado.
     *
     * Las preparadas ya NO se pueden editar ni eliminar directamente.
     * Las correcciones se manejan mediante recálculo en cascada
     * usando MovimientoService::recalcularKardexProducto().
     * ============================================================ */

    // /**
    //  * Actualiza una preparada existente dentro de una transacción:
    //  * 1. Revierte el stock anterior (devuelve insumos, quita producto final)
    //  * 2. Recalcula cabecera y detalles con los nuevos datos
    //  * 3. Reemplaza los detalles (delete + createMany)
    //  * 4. Aplica el nuevo stock (consume insumos, genera producto final)
    //  *
    //  * @param  int   $id   ID de la preparada a actualizar
    //  * @param  array $data Datos validados del request
    //  * @return Preparada La preparada actualizada
    //  *
    //  * @throws \Exception Si ocurre cualquier error
    //  */
    // public function updatePreparada(int $id, array $data): Preparada
    // {
    //     return DB::transaction(function () use ($id, $data) {
    //         $preparada = Preparada::with('detalles')->findOrFail($id);
    //
    //         // 1) Revertir stock anterior
    //         Producto::updateStockPreparada(false, $preparada);
    //
    //         // 2) Procesar nuevos datos
    //         $preparadaData = $this->proccessPreparadaData($data, false);
    //
    //         // 3) Actualizar cabecera y recrear detalles
    //         $preparada->update($preparadaData['preparada']);
    //         $preparada->detalles()->delete();
    //         $preparada->detalles()->createMany($preparadaData['detalles']);
    //         $preparada->load('detalles');
    //
    //         // 4) Aplicar nuevo stock
    //         Producto::updateStockPreparada(true, $preparada);
    //
    //         return $preparada;
    //     });
    // }

    // /**
    //  * Elimina una preparada dentro de una transacción:
    //  * 1. Revierte el stock (devuelve insumos, quita producto final)
    //  * 2. Elimina el registro de preparada (cascade elimina detalles)
    //  *
    //  * @param  int  $id ID de la preparada a eliminar
    //  * @return void
    //  *
    //  * @throws \Exception Si ocurre cualquier error
    //  */
    // public function deletePreparada(int $id): void
    // {
    //     DB::transaction(function () use ($id) {
    //         $registro = Preparada::with('detalles')->findOrFail($id);
    //
    //         // Revertir stock (devuelve insumos, quita producto final)
    //         Producto::updateStockPreparada(false, $registro);
    //
    //         $registro->delete();
    //     });
    // }

    /**
     * Procesa y construye los datos de la preparada (cabecera + detalles calculados).
     *
     * Consulta la formulación asociada y su producto/cliente,
     * calcula los costos de cada insumo y genera los arrays listos
     * para Preparada::create() y detalles()->createMany().
     *
     * @param  array  $data  Datos validados del request
     * @param  bool  $isNew  true=nuevo registro (asigna user_id), false=edición
     * @return array ['preparada' => [...], 'detalles' => [...]]
     */
    private function proccessPreparadaData(array $data, bool $isNew = true): array
    {
        // Cargar productos involucrados en los detalles
        $productos = Producto::whereIn('id', collect($data['detalles'])->pluck('producto_id'))
            ->get()
            ->keyBy('id');

        // Obtener formulación con su producto y cliente
        $formulacion = Formulacion::with('producto')
            ->findOrFail($data['formulacion_id']);

        // Producto final relacionado con la formulación
        $producto = $formulacion->producto;
        $data['producto_id'] = $producto->id ?? null;
        $data['producto_nombre'] = $producto->nombre ?? '';
        $data['producto_empaque'] = $producto->empaque ?? '';

        // Cliente relacionado con la formulación
        $formulacion = Formulacion::with('cliente')
            ->findOrFail($data['formulacion_id']);

        $cliente = $formulacion->cliente;
        $data['cliente_id'] = $cliente?->id;
        $data['cliente_nombre'] = $cliente?->razon_social ?? '';

        // Inicializar totales acumulados
        $totales = [
            'ingreso_saco' => 0,
            'ingreso_kg' => 0,
            'ingreso_soles' => 0,
        ];

        // Calcular cada línea de detalle (insumos)
        $detallesCalculados = [];
        foreach ($data['detalles'] as $detalle) {
            $productoInsumo = $productos[$detalle['producto_id']];
            $detallesCalculados[] = $this->calculateDetail(
                $productoInsumo,
                $detalle['salida_saco'],
                $detalle['salida_kg'],
                $detalle['salida_soles'],
                $detalle['precio_unitario'],
                $totales
            );
        }

        // Construir array de la cabecera
        $preparadaData = [
            'fecha' => $data['fecha'] ?? now(),
            'numero_interno' => $data['numero_interno'] ?? '',
            'formulacion_id' => $data['formulacion_id'] ?? null,
            'producto_id' => $data['producto_id'] ?? null,
            'producto_nombre' => $data['producto_nombre'] ?? '',
            'producto_empaque' => $data['producto_empaque'] ?? 0,
            'items' => count($detallesCalculados),
            'cliente_id' => $data['cliente_id'],
            'cliente_nombre' => $data['cliente_nombre'] ?? '',
            'costo_unitario' => round($totales['ingreso_soles'] / $data['ingreso_saco'], 4) ?? 0,

            // Valores de ingreso (input del usuario)
            'ingreso_saco' => $data['ingreso_saco'] ?? 0,
            'ingreso_kg' => $data['ingreso_kg'] ?? 0,
            'ingreso_soles' => $data['ingreso_soles'] ?? 0,
        ];

        // Campos exclusivos de creación
        if ($isNew) {
            $preparadaData['user_id'] = auth()->id();
            $preparadaData['user_nombre'] = auth()->user()->name;
        }

        return [
            'preparada' => $preparadaData,
            'detalles' => $detallesCalculados,
        ];
    }

    /**
     * Calcula los valores de un detalle de preparada individual (insumo).
     *
     * Calcula el costo de salida del insumo y acumula los totales
     * de sacos, kg y soles para la cabecera.
     *
     * Nota: El producto con ID 77 tiene un cálculo especial donde
     * salida_soles = precio_unitario directamente.
     *
     * @param  Producto  $producto  Producto insumo
     * @param  float  $salida_saco  Cantidad en sacos
     * @param  float  $salida_kg  Cantidad en kilogramos
     * @param  float  $salida_soles  Costo en soles
     * @param  float  $precio_unitario_input  Precio unitario del insumo
     * @param  array  &$totales  Array de totales acumulados (referencia)
     * @return array Detalle listo para createMany()
     */
    private function calculateDetail($producto, $salida_saco, $salida_kg, $salida_soles, $precio_unitario_input, array &$totales): array
    {
        $totales['ingreso_saco'] += $salida_saco;
        $totales['ingreso_kg'] += $salida_kg;

        // Producto con ID 77 tiene cálculo especial
        if ((int) $producto->id === 77) {
            $salida_soles = round($precio_unitario_input, 4);
        }
        // Productos normales
        else {
            $salida_soles = round($salida_saco * $precio_unitario_input, 4);
        }

        $totales['ingreso_soles'] += $salida_soles;

        return [
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'producto_empaque' => $producto->empaque ?? 0,
            'salida_saco' => $salida_saco,
            'salida_kg' => $salida_kg,
            'salida_soles' => $salida_soles,
            'precio_unitario' => $precio_unitario_input,
        ];
    }
}
