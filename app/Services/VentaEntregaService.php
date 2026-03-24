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

use App\Models\VentaEntrega;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Support\Facades\DB;

class VentaEntregaService
{
    /**
     * Crea una entrega parcial dentro de una transacción:
     * 1. Genera el siguiente número de recibo correlativo
     * 2. Procesa datos (bloquea venta + detalles, valida pendientes)
     * 3. Crea el registro VentaEntrega + detalles
     * 4. Aplica impacto en venta_detalles (suma entregado, resta saldo)
     *
     * @param  array $data Datos validados del request
     * @return VentaEntrega La entrega recién creada
     *
     * @throws \Exception Si la cantidad excede el pendiente
     */
    public function createEntrega(array $data): VentaEntrega
    {
        return DB::transaction(function () use ($data) {

            // 1) Generar número_recibo correlativo numérico
            $ultimoRecibo = VentaEntrega::whereRaw("numero_recibo REGEXP '^[0-9]+$'")
                ->selectRaw("MAX(CAST(numero_recibo AS UNSIGNED)) as max_recibo")
                ->value('max_recibo');
            $data['numero_recibo'] = $ultimoRecibo ? ((int)$ultimoRecibo + 1) : 1;

            // 2) Procesar data (bloquea venta, valida pendientes)
            $entregaData = $this->processEntregaData($data, true);

            // 3) Persistir cabecera y detalles
            $entrega = VentaEntrega::create($entregaData['entrega']);
            if (!empty($entregaData['detalles'])) {
                $entrega->detalles()->createMany($entregaData['detalles']);
                $this->aplicarDetalleEnVentas($entrega->id);
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
     * @param  int   $id   ID de la entrega a actualizar
     * @param  array $data Datos validados del request
     * @return VentaEntrega La entrega actualizada
     *
     * @throws \Exception Si la cantidad excede el pendiente
     */
    public function updateEntrega(int $id, array $data): VentaEntrega
    {
        return DB::transaction(function () use ($id, $data) {
            $entrega = VentaEntrega::with('detalles')->findOrFail($id);

            // 1) Revertir impacto anterior
            $this->revertirDetallesEnVentas($entrega->id);

            // 2) Procesar nueva data
            $entregaData = $this->processEntregaData($data, false, $entrega);

            // 3) Actualizar cabecera y recrear detalles
            $entrega->update($entregaData['entrega']);
            $entrega->detalles()->delete();
            if (!empty($entregaData['detalles'])) {
                $entrega->detalles()->createMany($entregaData['detalles']);
                $this->aplicarDetalleEnVentas($entrega->id);
            }

            return $entrega;
        });
    }

    /**
     * Elimina una entrega parcial dentro de una transacción:
     * 1. Revierte el impacto en venta_detalles (resta entregado, suma saldo)
     * 2. Elimina detalles y cabecera
     *
     * @param  int  $id ID de la entrega a eliminar
     * @return void
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
     * @param  array             $data              Datos validados del request
     * @param  bool              $isNew             true=creación, false=edición
     * @param  VentaEntrega|null $entregaExistente  Entrega existente (solo en edición)
     * @return array ['entrega' => [...], 'detalles' => [...]]
     *
     * @throws \Exception Si la cantidad a entregar excede el pendiente
     */
    private function processEntregaData(array $data, bool $isNew = true, ?VentaEntrega $entregaExistente = null): array
    {
        // Bloquear venta principal
        $venta = Venta::where('id', (int)$data['venta_id'])
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
            $ventaDetalleId = (int)($d['venta_detalle_id'] ?? 0);
            if (!$ventaDetalleId || !isset($ventaDetalles[$ventaDetalleId])) continue;

            $cantidadEntrega = (float)($d['cantidad'] ?? 0);
            if ($cantidadEntrega <= 0) continue;

            $vd = $ventaDetalles[$ventaDetalleId];

            // Validar pendiente real
            $cantidadVendida = (float)$vd->cantidad;
            $entregadoActual = (float)$vd->entregado;
            $pendienteActual = max(0, $cantidadVendida - $entregadoActual);

            if ($cantidadEntrega > $pendienteActual) {
                throw new \Exception("Entrega inválida: {$vd->producto_nombre} excede el pendiente. Pendiente: {$pendienteActual}");
            }

            $empaque = (float)($vd->producto_empaque ?? 0);
            $salidaKg = $empaque * $cantidadEntrega;

            $detallesFinal[] = [
                'venta_detalle_id' => $vd->id,
                'producto_id'      => $vd->producto_id,
                'producto_nombre'  => $vd->producto_nombre,
                'producto_empaque' => $vd->producto_empaque,
                'cantidad'         => $cantidadEntrega,
                'salida_kg'        => $salidaKg,
            ];
        }

        // Construir cabecera
        $entregaCabecera = [
            'venta_id'      => $venta->id,
            'fecha_entrega' => $data['fecha_entrega'] ?? now(),
            'comentario'    => $data['comentario'] ?? null,
            'estado'        => $data['estado'] ?? 'ENTREGADO',
        ];

        if ($isNew) {
            $entregaCabecera['numero_recibo'] = (string)($data['numero_recibo'] ?? null);
            $entregaCabecera['user_id']       = auth()->id();
            $entregaCabecera['user_nombre']   = auth()->user()->name;
        }

        return [
            'entrega'  => $entregaCabecera,
            'detalles' => $detallesFinal,
        ];
    }

    /**
     * Aplica las cantidades entregadas a los detalles de venta vinculados.
     * Suma entregado y recalcula saldo en cada venta_detalle con lockForUpdate.
     *
     * @param  int  $ventaEntregaId ID de la entrega cuyos detalles se aplican
     * @return void
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

            if (!$vd) continue;

            $qty = (float)$r->qty;

            $vd->entregado = (float)($vd->entregado ?? 0) + $qty;
            $vd->saldo     = max(0, (float)$vd->cantidad - (float)$vd->entregado);

            $vd->save();
        }
    }

    /**
     * Revierte las cantidades entregadas en los detalles de venta vinculados.
     * Resta entregado y recalcula saldo en cada venta_detalle con lockForUpdate.
     *
     * @param  int  $ventaEntregaId ID de la entrega cuyos detalles se revierten
     * @return void
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

            if (!$vd) continue;

            $qty = (float)$r->qty;

            $vd->entregado = (float)($vd->entregado ?? 0) - $qty;
            if ($vd->entregado < 0) $vd->entregado = 0;

            $vd->saldo = max(0, (float)$vd->cantidad - (float)$vd->entregado);

            $vd->save();
        }
    }
}
