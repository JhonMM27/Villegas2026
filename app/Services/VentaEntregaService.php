<?php

/**
 * Servicio de Entregas de Venta (Entregas Parciales).
 *
 * Concentra la lógica de negocio para la creación, actualización y eliminación
 * de entregas parciales de productos vendidos, incluyendo:
 * - Generación de número de recibo correlativo
 * - Validación de cantidades pendientes por entregar
 * - Actualización de entregado/saldo en los detalles de venta vinculados
 */

namespace App\Services;

use App\Models\Movimiento;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaEntrega;
use Illuminate\Support\Facades\DB;

class VentaEntregaService
{
    /**
     * Inyección de servicios.
     */
    public function __construct(
        protected MovimientoService $movimientoService
    ) {}

    /**
     * Crea una entrega parcial dentro de una transacción:
     * 1. Genera el siguiente número de recibo correlativo
     * 2. Procesa datos (bloquea venta + detalles, valida pendientes)
     * 3. Crea el registro VentaEntrega + detalles
     * 4. Aplica impacto en venta_detalles (suma entregado, resta saldo)
     *
     * @param  array  $data  Datos validados del request
     * @return VentaEntrega La entrega recién creada
     *
     * @throws \Exception Si la cantidad excede el pendiente
     */
    public function createEntrega(array $data): VentaEntrega
    {
        return DB::transaction(function () use ($data) {

            // 1) Generar número_recibo correlativo numérico
            $ultimoRecibo = VentaEntrega::whereRaw("numero_recibo REGEXP '^[0-9]+$'")
                ->selectRaw('MAX(CAST(numero_recibo AS UNSIGNED)) as max_recibo')
                ->value('max_recibo');
            $data['numero_recibo'] = $ultimoRecibo ? ((int) $ultimoRecibo + 1) : 1;

            // 2) Procesar data (bloquea venta, valida pendientes)
            $entregaData = $this->processEntregaData($data, true);

            // 3) Persistir cabecera y detalles
            $entrega = VentaEntrega::create($entregaData['entrega']);
            if (! empty($entregaData['detalles'])) {
                // ────────────────────────────────────────────────────────
                // BLOQUE: Crear detalles y registrar movimientos
                // ────────────────────────────────────────────────────────
                // ¿Por qué $detallesCreados?: createMany() devuelve la
                //   colección de modelos recién creados (con sus IDs
                //   asignados por la BD). Si iteráramos sobre
                //   $entrega->detalles (cargado al inicio con
                //   with('detalles') en updateEntrega/rectificarEntrega),
                //   podríamos estar usando la versión cached de la
                //   relación, que NO contiene los detalles nuevos.
                //
                //   Los movimientos de kardex DEBEN referenciar el
                //   detalle_id correcto; usar la versión cached causaría
                //   movimientos huérfanos o con detalle_id incorrecto.
                // ────────────────────────────────────────────────────────
                $detallesCreados = $entrega->detalles()->createMany($entregaData['detalles']);
                $this->movimientoService->bloquearProductos($detallesCreados->pluck('producto_id')->all());
                $this->aplicarDetalleEnVentas($entrega->id);

                // 4) Registrar movimientos de SALIDA en el kardex por cada detalle entregado
                foreach ($detallesCreados as $detalle) {
                    $this->movimientoService->registrarSalida([
                        'tipo' => MovimientoService::TIPO_VENTA,
                        'fecha' => $entrega->fecha_entrega,
                        'transaccion_tipo' => 'venta_entregas',
                        'transaccion_id' => $entrega->id,
                        'detalle_id' => $detalle->id,
                        'producto_id' => $detalle->producto_id,
                        'producto_nombre' => $detalle->producto_nombre,
                        'empaque' => $detalle->producto_empaque,
                        'unidad_codigo' => $detalle->ventaDetalle->unidad_codigo ?? 'NIU',
                        'cantidad' => $detalle->cantidad,
                        'cantidad_kg' => $detalle->salida_kg,
                    ]);
                }
            }

            return $entrega;
        });
    }

    /**
     * Actualiza una entrega parcial dentro de una transacción:
     * 1. Revierte el impacto anterior en venta_detalles
     * 2. Recalcula y reemplaza detalles
     * 3. Aplica nuevo impacto
     *
     * @param  int  $id  ID de la entrega a actualizar
     * @param  array  $data  Datos validados del request
     * @return VentaEntrega La entrega actualizada
     *
     * @throws \Exception Si la cantidad excede el pendiente
     */
    public function updateEntrega(int $id, array $data): VentaEntrega
    {
        return DB::transaction(function () use ($id, $data) {
            $entrega = VentaEntrega::with('detalles')->findOrFail($id);

            // 1) Revertir impacto anterior en venta_detalles
            $this->revertirDetallesEnVentas($entrega->id);

            // 2) Neutralizar movimientos de stock anteriores
            $this->neutralizarMovimientosEntrega($entrega->id);

            // 3) Procesar nueva data
            $entregaData = $this->processEntregaData($data, false, $entrega);

            // 4) Actualizar cabecera y recrear detalles
            $entrega->update($entregaData['entrega']);
            $entrega->detalles()->delete();
            if (! empty($entregaData['detalles'])) {
                $detallesCreados = $entrega->detalles()->createMany($entregaData['detalles']);
                $this->movimientoService->bloquearProductos($detallesCreados->pluck('producto_id')->all());
                $this->aplicarDetalleEnVentas($entrega->id);

                // 5) Registrar nuevos movimientos de SALIDA
                foreach ($detallesCreados as $detalle) {
                    $this->movimientoService->registrarSalida([
                        'tipo' => MovimientoService::TIPO_VENTA,
                        'fecha' => $entrega->fecha_entrega,
                        'transaccion_tipo' => 'venta_entregas',
                        'transaccion_id' => $entrega->id,
                        'detalle_id' => $detalle->id,
                        'producto_id' => $detalle->producto_id,
                        'producto_nombre' => $detalle->producto_nombre,
                        'empaque' => $detalle->producto_empaque,
                        'unidad_codigo' => $detalle->ventaDetalle->unidad_codigo ?? 'NIU',
                        'cantidad' => $detalle->cantidad,
                        'cantidad_kg' => $detalle->salida_kg,
                    ]);
                }
            }

            return $entrega;
        });
    }

    /**
     * Elimina una entrega parcial dentro de una transacción:
     * 1. Revierte el impacto en venta_detalles (resta entregado, suma saldo)
     * 2. Elimina detalles y cabecera
     *
     * @param  int  $id  ID de la entrega a eliminar
     *
     * @throws \Exception Si ocurre cualquier error
     */
    public function deleteEntrega(int $id): void
    {
        DB::transaction(function () use ($id) {
            $entrega = VentaEntrega::with('detalles')->findOrFail($id);

            // Revertir impacto en venta_detalles
            if ($entrega->detalles->isNotEmpty()) {
                $this->revertirDetallesEnVentas($entrega->id);
            }

            // Neutralizar movimientos de stock
            $this->neutralizarMovimientosEntrega($entrega->id);

            // Eliminar detalles y cabecera
            $entrega->detalles()->delete();
            $entrega->delete();
        });
    }

    /**
     * Procesa y construye los datos de la entrega (cabecera + detalles).
     *
     * Bloquea la venta padre y sus detalles involucrados, valida que la cantidad
     * a entregar no exceda el pendiente de cada línea, y construye los arrays
     * para VentaEntrega::create() y detalles()->createMany().
     *
     * @param  array  $data  Datos validados del request
     * @param  bool  $isNew  true=creación, false=edición
     * @param  VentaEntrega|null  $entregaExistente  Entrega existente (solo en edición)
     * @return array ['entrega' => [...], 'detalles' => [...]]
     *
     * @throws \Exception Si la cantidad a entregar excede el pendiente
     */
    private function processEntregaData(array $data, bool $isNew = true, ?VentaEntrega $entregaExistente = null): array
    {
        // Bloquear venta principal
        $venta = Venta::where('id', (int) $data['venta_id'])
            ->lockForUpdate()
            ->firstOrFail();

        $detallesInput = $data['detalles'] ?? [];

        // Traer detalles de venta involucrados con lockForUpdate
        $ventaDetalleIds = collect($detallesInput)->pluck('venta_detalle_id')->filter()->unique()->values();
        $ventaDetalles = VentaDetalle::where('venta_id', $venta->id)
            ->whereIn('id', $ventaDetalleIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $detallesFinal = [];

        foreach ($detallesInput as $d) {
            $ventaDetalleId = (int) ($d['venta_detalle_id'] ?? 0);
            if (! $ventaDetalleId || ! isset($ventaDetalles[$ventaDetalleId])) {
                continue;
            }

            $cantidadEntrega = (float) ($d['cantidad'] ?? 0);
            if ($cantidadEntrega <= 0) {
                continue;
            }

            $vd = $ventaDetalles[$ventaDetalleId];

            // Validar pendiente real
            $cantidadVendida = (float) $vd->cantidad;
            $entregadoActual = (float) $vd->entregado;
            $pendienteActual = max(0, $cantidadVendida - $entregadoActual);

            if ($cantidadEntrega > $pendienteActual) {
                throw new \Exception("Entrega inválida: {$vd->producto_nombre} excede el pendiente. Pendiente: {$pendienteActual}");
            }

            $empaque = (float) ($vd->producto_empaque ?? 0);
            $salidaKg = $empaque * $cantidadEntrega;

            $detallesFinal[] = [
                'venta_detalle_id' => $vd->id,
                'producto_id' => $vd->producto_id,
                'producto_nombre' => $vd->producto_nombre,
                'producto_empaque' => $vd->producto_empaque,
                'cantidad' => $cantidadEntrega,
                'salida_kg' => $salidaKg,
            ];
        }

        // Construir cabecera
        $entregaCabecera = [
            'venta_id' => $venta->id,
            'fecha_entrega' => $data['fecha_entrega'] ?? now(),
            'comentario' => $data['comentario'] ?? null,
            'estado' => $data['estado'] ?? 'ENTREGADO',
        ];

        if ($isNew) {
            $entregaCabecera['numero_recibo'] = (string) ($data['numero_recibo'] ?? null);
            $entregaCabecera['user_id'] = auth()->id();
            $entregaCabecera['user_nombre'] = auth()->user()->name;
        }

        return [
            'entrega' => $entregaCabecera,
            'detalles' => $detallesFinal,
        ];
    }

    /**
     * Aplica las cantidades entregadas a los detalles de venta vinculados.
     * Suma entregado y recalcula saldo en cada venta_detalle con lockForUpdate.
     *
     * @param  int  $ventaEntregaId  ID de la entrega cuyos detalles se aplican
     */
    private function aplicarDetalleEnVentas(int $ventaEntregaId): void
    {
        $rows = DB::table('venta_entrega_detalles')
            ->selectRaw('venta_detalle_id, SUM(cantidad) as qty, SUM(salida_kg) as kg')
            ->where('venta_entrega_id', $ventaEntregaId)
            ->groupBy('venta_detalle_id')
            ->get();

        foreach ($rows as $r) {
            $vd = VentaDetalle::where('id', $r->venta_detalle_id)
                ->lockForUpdate()
                ->first();

            if (! $vd) {
                continue;
            }

            $qty = (float) $r->qty;

            $vd->entregado = (float) ($vd->entregado ?? 0) + $qty;
            $vd->saldo = max(0, (float) $vd->cantidad - (float) $vd->entregado);

            $vd->save();
        }
    }

    /**
     * Revierte las cantidades entregadas en los detalles de venta vinculados.
     * Resta entregado y recalcula saldo en cada venta_detalle con lockForUpdate.
     *
     * @param  int  $ventaEntregaId  ID de la entrega cuyos detalles se revierten
     */
    private function revertirDetallesEnVentas(int $ventaEntregaId): void
    {
        $rows = DB::table('venta_entrega_detalles')
            ->selectRaw('venta_detalle_id, SUM(cantidad) as qty, SUM(salida_kg) as kg')
            ->where('venta_entrega_id', $ventaEntregaId)
            ->groupBy('venta_detalle_id')
            ->get();

        foreach ($rows as $r) {
            $vd = VentaDetalle::where('id', $r->venta_detalle_id)
                ->lockForUpdate()
                ->first();

            if (! $vd) {
                continue;
            }

            $qty = (float) $r->qty;

            $vd->entregado = (float) ($vd->entregado ?? 0) - $qty;
            if ($vd->entregado < 0) {
                $vd->entregado = 0;
            }

            $vd->saldo = max(0, (float) $vd->cantidad - (float) $vd->entregado);

            $vd->save();
        }
    }

    /**
     * Neutraliza los movimientos de stock de una entrega usando recalcularKardexExcluyendo.
     *
     * @param  int  $entregaId  ID de la entrega
     */
    private function neutralizarMovimientosEntrega(int $entregaId): void
    {
        $movimientos = Movimiento::where('transaccion_tipo', 'venta_entregas')
            ->where('transaccion_id', $entregaId)
            ->get();

        $productosAfectados = $movimientos->pluck('producto_id')->unique()->toArray();
        $this->movimientoService->bloquearProductos($productosAfectados);

        foreach ($productosAfectados as $productoId) {
            $movIds = $movimientos
                ->where('producto_id', $productoId)
                ->pluck('id')
                ->toArray();

            if (! empty($movIds)) {
                $this->movimientoService->recalcularKardexExcluyendo($productoId, $movIds);
            }
        }
    }

    /**
     * Anula una entrega parcial:
     * 1. Cambia estado a 'anulada'
     * 2. Neutraliza movimientos de stock
     * 3. Revierte impacto en venta_detalles
     *
     * @param  int  $id  ID de la entrega a anular
     * @return VentaEntrega La entrega anulada
     *
     * @throws \Exception Si la entrega ya está anulada
     */
    public function anularEntrega(int $id, ?string $motivo): VentaEntrega
    {
        return DB::transaction(function () use ($id, $motivo) {
            $entrega = VentaEntrega::query()->with('detalles')->lockForUpdate()->findOrFail($id);

            if ($entrega->estado === 'ANULADO') {
                throw new \Exception('Esta entrega ya fue anulada.');
            }

            $auditoria = app(AuditoriaService::class);
            $datosAnteriores = $auditoria->capturar('venta_entregas', $entrega);

            // 1) Cambiar estado a 'ANULADO'
            $entrega->update(['estado' => 'ANULADO']);

            // 2) Neutralizar movimientos de stock
            $this->neutralizarMovimientosEntrega($entrega->id);

            // 3) Revertir impacto en venta_detalles
            if ($entrega->detalles->isNotEmpty()) {
                $this->revertirDetallesEnVentas($entrega->id);
            }

            $entrega->refresh()->load('detalles');
            $auditoria->registrarAnulacion('venta_entregas', $entrega, $datosAnteriores,
                $auditoria->capturar('venta_entregas', $entrega), $motivo);

            return $entrega;
        });
    }

    /**
     * Rectifica una entrega previamente anulada.
     * Neutraliza los movimientos anteriores y crea nuevos con los datos actualizados.
     *
     * @param  int  $id  ID de la entrega anulada
     * @param  array  $data  Nuevos datos de la entrega
     * @return VentaEntrega La entrega rectificada
     *
     * @throws \Exception Si la entrega no está anulada
     */
    public function rectificarEntrega(int $id, array $data): VentaEntrega
    {
        return DB::transaction(function () use ($id, $data) {
            $entrega = VentaEntrega::query()->with('detalles')->lockForUpdate()->findOrFail($id);

            if ($entrega->estado !== 'ANULADO') {
                throw new \Exception('Solo se pueden rectificar entregas anuladas.');
            }

            if ($entrega->rectificacion_count >= 3) {
                throw new \Exception('Esta entrega ya no puede ser rectificada. Máximo 3 rectificaciones permitidas.');
            }

            $auditoria = app(AuditoriaService::class);
            $datosAnteriores = $auditoria->capturar('venta_entregas', $entrega);
            $numeroRectificacion = (int) $entrega->rectificacion_count + 1;

            // 1) Neutralizar movimientos anteriores
            $this->neutralizarMovimientosEntrega($entrega->id);

            // 2) Revertir impacto en venta_detalles
            if ($entrega->detalles->isNotEmpty()) {
                $this->revertirDetallesEnVentas($entrega->id);
            }

            // 3) Procesar nueva data
            $entregaData = $this->processEntregaData($data, false, $entrega);

            // 4) Actualizar cabecera y recrear detalles
            $entrega->update(array_merge($entregaData['entrega'], [
                'estado' => 'ENTREGADO',
                'rectificacion_count' => $numeroRectificacion,
            ]));
            $entrega->detalles()->delete();
            if (! empty($entregaData['detalles'])) {
                $detallesCreados = $entrega->detalles()->createMany($entregaData['detalles']);
                $this->movimientoService->bloquearProductos($detallesCreados->pluck('producto_id')->all());
                $this->aplicarDetalleEnVentas($entrega->id);

                // 5) Registrar nuevos movimientos de SALIDA
                foreach ($detallesCreados as $detalle) {
                    $this->movimientoService->registrarSalida([
                        'tipo' => MovimientoService::TIPO_VENTA,
                        'fecha' => $entrega->fecha_entrega,
                        'transaccion_tipo' => 'venta_entregas',
                        'transaccion_id' => $entrega->id,
                        'detalle_id' => $detalle->id,
                        'producto_id' => $detalle->producto_id,
                        'producto_nombre' => $detalle->producto_nombre,
                        'empaque' => $detalle->producto_empaque,
                        'unidad_codigo' => $detalle->ventaDetalle->unidad_codigo ?? 'NIU',
                        'cantidad' => $detalle->cantidad,
                        'cantidad_kg' => $detalle->salida_kg,
                    ]);
                }
            }

            $entrega->refresh()->load('detalles');
            $auditoria->registrarRectificacion('venta_entregas', $entrega, $numeroRectificacion, $datosAnteriores,
                $auditoria->capturar('venta_entregas', $entrega), $data['rectificacion_motivo'] ?? null);

            return $entrega;
        });
    }
}
