<?php

/**
 * Servicio de NucleoPreparada (Producción de Núcleos).
 *
 * Concentra toda la lógica de negocio asociada a la creación
 * de nucleo_preparadas (registros de producción de núcleos):
 * - Cálculo de costos de insumos y totales de producción
 * - Registro de movimientos de SALIDA (insumos) e INGRESO (producto final)
 *   en el kardex valorizado mediante MovimientoService
 * - Vinculación con núcleos (recetas)
 *
 * NOTA: Los métodos update() y delete() han sido COMENTADOS
 * porque ahora el sistema usa kardex valorizado.
 */

namespace App\Services;

use App\Models\Movimiento;
use App\Models\Nucleo;
use App\Models\NucleoPreparada;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class NucleoPreparadaService
{
    /**
     * Inyección del servicio de movimientos para registrar
     * salidas e ingresos en el kardex valorizado.
     */
    public function __construct(
        protected MovimientoService $movimientoService
    ) {}

    /**
     * Crea una nucleo_preparada (registro de producción) dentro de una transacción:
     * 1. Procesa cabecera y detalles calculados desde el núcleo
     * 2. Crea el registro NucleoPreparada + NucleoPreparadaDetalles
     * 3. Registra movimientos de SALIDA por cada insumo en el kardex
     * 4. Registra movimiento de INGRESO del producto final en el kardex
     *
     * @param  array  $data  Datos validados del request
     * @return NucleoPreparada La nucleo_preparada recién creada (con detalles cargados)
     *
     * @throws \Exception Si ocurre cualquier error
     */
    public function createNucleoPreparada(array $data): NucleoPreparada
    {
        return DB::transaction(function () use ($data) {

            // 1) Procesar datos (cabecera + detalles calculados)
            $preparadaData = $this->processNucleoPreparadaData($data, true);

            // 2) Persistir nucleo_preparada y detalles
            $preparada = NucleoPreparada::create($preparadaData['preparada']);
            $detallesCreados = $preparada->detalles()->createMany($preparadaData['detalles']);
            $preparada->load('detalles');

            $this->movimientoService->bloquearProductos(array_merge(
                $detallesCreados->pluck('producto_id')->all(),
                [$preparada->nucleo_id]
            ));

            // 3) Registrar SALIDA de insumos en el kardex (excluir producto ID 77)
            foreach ($detallesCreados as $detalle) {
                // Excluir producto ID 77 (servicio mezclado)
                if ((float) $detalle->salida_kg <= 0 || (int) $detalle->producto_id === 77) {
                    continue;
                }

                $cantidadUnidades = (float) $detalle->salida_kg / (float) ($detalle->producto_empaque ?: 1);

                $this->movimientoService->registrarSalida([
                    'tipo' => MovimientoService::TIPO_PREPARADA_SALIDA,
                    'fecha' => $preparada->fecha,
                    'transaccion_tipo' => 'nucleo_preparadas',
                    'transaccion_id' => $preparada->id,
                    'detalle_id' => $detalle->id,
                    'producto_id' => $detalle->producto_id,
                    'producto_nombre' => $detalle->producto_nombre,
                    'empaque' => $detalle->producto_empaque,
                    'unidad_codigo' => $detalle->unidad_codigo,
                    'cantidad' => round($cantidadUnidades, 4),
                    'cantidad_kg' => $detalle->salida_kg,
                ]);
            }

            // 4) Registrar INGRESO del producto final (núcleo) en el kardex
            $this->movimientoService->registrarIngreso([
                'tipo' => MovimientoService::TIPO_PREPARADA_INGRESO,
                'fecha' => $preparada->fecha,
                'transaccion_tipo' => 'nucleo_preparadas',
                'transaccion_id' => $preparada->id,
                'detalle_id' => null,
                'producto_id' => $preparada->nucleo_id,
                'producto_nombre' => $preparada->nucleo_nombre,
                'empaque' => $preparada->producto_empaque,
                'unidad_codigo' => null,
                'cantidad' => $preparada->ingreso_saco,
                'cantidad_kg' => $preparada->ingreso_kg,
                'costo_unitario' => $preparada->costo_unitario,
            ]);

            return $preparada;
        });
    }

    /**
     * Anula una nucleo_preparada existente dentro de una transacción:
     * 1. Valida que no esté ya anulada
     * 2. Cambia el estado a 'anulada'
     * 3. Recopila productos afectados y recalcula kardex excluyendo esta preparación
     *
     * @param  int  $id  ID de la nucleo_preparada a anular
     * @return NucleoPreparada La nucleo_preparada anulada
     *
     * @throws \Exception Si ya está anulada o si ocurre error
     */
    public function anularNucleoPreparada(int $id, ?string $motivo): NucleoPreparada
    {
        return DB::transaction(function () use ($id, $motivo) {
            $preparada = NucleoPreparada::query()->with('detalles')->lockForUpdate()->findOrFail($id);

            if ($preparada->estado === 'anulada') {
                throw new \Exception('Esta preparación de núcleo ya fue anulada.');
            }

            // Si está rectificada, permitir anular para volver a rectificar (si count < 3)
            if ($preparada->estado === 'rectificada') {
                if ($preparada->rectificacion_count >= 3) {
                    throw new \Exception('Esta preparación de núcleo ya no puede ser rectificada. Máximo 3 rectificaciones permitidas.');
                }
                // Permitir: se volverá a 'anulada' para poder rectificar de nuevo
            }

            $auditoria = app(AuditoriaService::class);
            $datosAnteriores = $auditoria->capturar('nucleo_preparadas', $preparada);

            // 1) Cambiar estado a 'anulada'
            $preparada->update(['estado' => 'anulada']);

            // 2) Recopilar productos afectados (insumos + producto final)
            $productosAfectados = [];

            // Insumos consumidos (excluir producto ID 77)
            foreach ($preparada->detalles as $detalle) {
                if ((float) $detalle->salida_kg <= 0 || (int) $detalle->producto_id === 77) {
                    continue;
                }
                if (! in_array($detalle->producto_id, $productosAfectados)) {
                    $productosAfectados[] = $detalle->producto_id;
                }
            }

            // Producto final producido
            if (! in_array($preparada->nucleo_id, $productosAfectados)) {
                $productosAfectados[] = $preparada->nucleo_id;
            }

            $this->movimientoService->bloquearProductos($productosAfectados);

            // 3) Recalcular kardex para productos afectados
            foreach ($productosAfectados as $productoId) {
                $movIds = Movimiento::where('transaccion_tipo', 'nucleo_preparadas')
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

            $preparada->refresh()->load('detalles');
            $auditoria->registrarAnulacion('nucleo_preparadas', $preparada, $datosAnteriores,
                $auditoria->capturar('nucleo_preparadas', $preparada), $motivo);

            return $preparada;
        });
    }

    /**
     * Rectifica una nucleo_preparada previamente anulada.
     *
     * @param  int  $preparadaId  ID de la nucleo_preparada anulada
     * @param  array  $data  Datos validados del request
     */
    public function rectificarNucleoPreparada(int $preparadaId, array $data): array
    {
        return DB::transaction(function () use ($preparadaId, $data) {
            $preparada = NucleoPreparada::query()->with('detalles')->lockForUpdate()->findOrFail($preparadaId);
            $productoIdsAnteriores = $preparada->detalles()->pluck('producto_id')->all();
            $productoIdsAnteriores[] = $preparada->nucleo_id;

            if ($preparada->estado !== 'anulada') {
                throw new \Exception('Solo se pueden rectificar Preparaciones de Núcleo en estado anulada.');
            }

            if ($preparada->rectificacion_count >= 3) {
                throw new \Exception('Esta preparación de núcleo ya no puede ser rectificada. Máximo 3 rectificaciones permitidas.');
            }

            $auditoria = app(AuditoriaService::class);
            $datosAnteriores = $auditoria->capturar('nucleo_preparadas', $preparada);
            $numeroRectificacion = (int) $preparada->rectificacion_count + 1;

            // 1) Procesar datos
            $preparadaDataRaw = $this->processNucleoPreparadaData($data, false);
            $preparadaData = $preparadaDataRaw['preparada'];

            // Forzamos estado y trazabilidad
            $preparadaData['estado'] = 'rectificada';
            $preparadaData['nota'] = trim(($data['nota'] ?? $preparada->nota ?? '').' | Rectificada el '.now()->format('d/m/Y H:i'));

            // 2) Actualizar cabecera
            $preparada->update($preparadaData);

            // Incrementar contador de rectificaciones
            $preparada->update(['rectificacion_count' => $numeroRectificacion]);

            // 3) Reemplazar detalles
            $preparada->detalles()->delete();
            $detallesNuevos = $preparada->detalles()->createMany($preparadaDataRaw['detalles']);
            $preparada->load('detalles');

            $this->movimientoService->bloquearProductos(array_merge(
                $productoIdsAnteriores,
                $detallesNuevos->pluck('producto_id')->all(),
                [$preparada->nucleo_id]
            ));

            // Los movimientos neutralizados se conservan como trazabilidad.
            // La rectificación agrega movimientos activos nuevos y nunca borra
            // registros históricos del kardex.

            // 5) Registrar SALIDA de insumos en el kardex (siempre movimientos nuevos)
            $productosAfectados = [];
            foreach ($detallesNuevos as $detalle) {
                if ((float) $detalle->salida_kg <= 0 || (int) $detalle->producto_id === 77) {
                    continue;
                }

                $cantidadUnidades = (float) $detalle->salida_kg / (float) ($detalle->producto_empaque ?: 1);

                $this->movimientoService->registrarSalida([
                    'tipo' => MovimientoService::TIPO_PREPARADA_SALIDA,
                    'fecha' => $preparada->fecha,
                    'transaccion_tipo' => 'nucleo_preparadas',
                    'transaccion_id' => $preparada->id,
                    'detalle_id' => $detalle->id,
                    'producto_id' => $detalle->producto_id,
                    'producto_nombre' => $detalle->producto_nombre,
                    'empaque' => $detalle->producto_empaque,
                    'unidad_codigo' => $detalle->unidad_codigo,
                    'cantidad' => round($cantidadUnidades, 4),
                    'cantidad_kg' => $detalle->salida_kg,
                    'comentario' => 'Rectificación de nucleo_preparada',
                ]);
                $productosAfectados[] = $detalle->producto_id;
            }

            // 6) Registrar INGRESO del producto final en el kardex (siempre movimiento nuevo)
            $this->movimientoService->registrarIngreso([
                'tipo' => MovimientoService::TIPO_PREPARADA_INGRESO,
                'fecha' => $preparada->fecha,
                'transaccion_tipo' => 'nucleo_preparadas',
                'transaccion_id' => $preparada->id,
                'detalle_id' => null,
                'producto_id' => $preparada->nucleo_id,
                'producto_nombre' => $preparada->nucleo_nombre,
                'empaque' => $preparada->producto_empaque,
                'unidad_codigo' => null,
                'cantidad' => $preparada->ingreso_saco,
                'cantidad_kg' => $preparada->ingreso_kg,
                'costo_unitario' => $preparada->costo_unitario,
                'comentario' => 'Rectificación de nucleo_preparada',
            ]);
            $productosAfectados[] = $preparada->nucleo_id;

            // 7) Recalcular Kardex para productos afectados por ID de movimiento
            foreach (array_unique($productosAfectados) as $pId) {
                $minMovId = Movimiento::where('producto_id', $pId)
                    ->where('transaccion_tipo', 'nucleo_preparadas')
                    ->where('transaccion_id', $preparada->id)
                    ->min('id');

                if ($minMovId) {
                    $this->movimientoService->recalcularKardexProducto($pId, (int) $minMovId);
                } else {
                    $fechaPreparada = $preparada->fecha instanceof \Carbon\Carbon
                        ? $preparada->fecha
                        : \Carbon\Carbon::parse($preparada->fecha);

                    $this->movimientoService->recalcularKardexProductoDesdeFecha(
                        $pId,
                        $fechaPreparada->format('Y-m-d H:i:s')
                    );
                }
            }

            $preparada->refresh()->load('detalles');
            $auditoria->registrarRectificacion('nucleo_preparadas', $preparada, $numeroRectificacion, $datosAnteriores,
                $auditoria->capturar('nucleo_preparadas', $preparada), $data['rectificacion_motivo'] ?? null);

            return [
                'preparada' => $preparada,
                'detalles' => $detallesNuevos,
            ];
        });
    }

    /**
     * Procesa y construye los datos de la nucleo_preparada.
     *
     * @param  array  $data  Datos validados del request
     * @param  bool  $isNew  true=nuevo registro, false=edición
     * @return array ['preparada' => [...], 'detalles' => [...]]
     */
    private function processNucleoPreparadaData(array $data, bool $isNew = true): array
    {
        $productos = Producto::whereIn('id', collect($data['detalles'])->pluck('producto_id'))
            ->get()
            ->keyBy('id');

        // Obtener núcleo
        $nucleo = Nucleo::with('producto')->findOrFail($data['nucleo_id']);

        $totales = ['total' => 0];
        $detallesCalculados = [];

        foreach ($data['detalles'] as $detalle) {
            $producto = $productos[$detalle['producto_id']];
            // Usar el empaque real del producto
            $empaqueProducto = (float) ($producto->empaque ?? 1);
            $detallesCalculados[] = $this->calculateDetail(
                $producto,
                $empaqueProducto,
                $detalle['unidad_codigo'],
                $detalle['costo_unitario'],
                $detalle['cantidad_porcentaje'],
                $detalle['salida_kg'],
                $detalle['salida_soles'],
                $totales
            );
        }

        // Calcular costo_unitario desde la suma de insumos
        $totalSoles = round($totales['total'], 4);
        $ingresoSaco = (float) ($data['ingreso_saco'] ?? 1);
        $costoUnitarioCalculado = $ingresoSaco > 0 ? round($totalSoles / $ingresoSaco, 4) : 0;

        $preparadaData = [
            'fecha' => $data['fecha'] ?? now(),
            'nucleo_id' => $nucleo->id,
            'nucleo_nombre' => $nucleo->nombre,
            'unidad_nombre' => $nucleo->unidad_nombre ?? '',
            'producto_empaque' => $nucleo->empaque ?? 0,
            'cantidad_porcentaje' => $data['proporcion'] ?? 0,
            'ingreso_kg' => $data['proporcion'] ?? 0,
            'ingreso_saco' => $data['ingreso_saco'] ?? 0,
            'ingreso_soles' => $totalSoles,
            'costo_unitario' => $costoUnitarioCalculado,
            'numero_interno' => $data['numero_interno'] ?? '',
            'items' => count($detallesCalculados),
            'estado' => 'registrada',
        ];

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
     * Calcula los valores de un detalle.
     */
    private function calculateDetail($producto, $producto_empaque, $unidad_codigo, $costo_unitario, $cantidad_porcentaje, $salida_kg, $salida_soles, array &$totales): array
    {
        $cantidad_porcentaje = $cantidad_porcentaje ?? 0;
        $costo_unitario = $costo_unitario ?? 0;

        $totales['total'] += $costo_unitario * $salida_kg;

        return [
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'producto_empaque' => $producto_empaque ?? 0,
            'unidad_codigo' => $unidad_codigo ?? '',
            'cantidad_porcentaje' => $cantidad_porcentaje,
            'salida_kg' => $salida_kg,
            'costo_unitario' => $costo_unitario,
            'salida_soles' => $salida_soles,
        ];
    }
}
