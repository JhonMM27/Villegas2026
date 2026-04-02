<?php

/**
 * Servicio de Ventas Provisionales (Pagos Anticipados/Cobranzas).
 *
 * Concentra la lógica de negocio para la creación, actualización y eliminación
 * de pagos provisionales aplicados a ventas, incluyendo:
 * - Generación de número de recibo correlativo
 * - Cálculo de montos aplicados vs libres
 * - Actualización de abonos/saldos en las ventas vinculadas
 */

namespace App\Services;

use App\Models\VentaProvisional;
use App\Models\Venta;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;

class VentaProvisionalService
{
    /**
     * Crea un pago provisional dentro de una transacción:
     * 1. Genera el siguiente número de recibo correlativo
     * 2. Procesa datos de cabecera y detalles (montos por venta)
     * 3. Crea el registro VentaProvisional + detalles
     * 4. Aplica abonos a las ventas vinculadas (suma abonos, resta saldo)
     *
     * @param  array $data Datos validados del request
     * @return VentaProvisional El registro recién creado
     *
     * @throws \Exception Si ocurre cualquier error
     */
    public function createProvisional(array $data): VentaProvisional
    {
        return DB::transaction(function () use ($data) {

            // 1) Generar número_recibo correlativo numérico
            $ultimoRecibo = VentaProvisional::whereRaw("numero_recibo REGEXP '^[0-9]+$'")
                ->selectRaw("MAX(CAST(numero_recibo AS UNSIGNED)) as max_recibo")
                ->value('max_recibo');
            $data['numero_recibo'] = $ultimoRecibo ? ((int)$ultimoRecibo + 1) : 1;

            // 2) Procesar datos (cabecera + detalles calculados)
            $provisionalData = $this->processProvisionalData($data, true);

            // 3) Persistir cabecera y detalles
            $provisional = VentaProvisional::create($provisionalData['provisional']);
            if (!empty($provisionalData['detalles'])) {
                $provisional->detalles()->createMany($provisionalData['detalles']);
                $this->aplicarDetallesEnVentas($provisional->id);
            }

            return $provisional;
        });
    }

    /**
     * Actualiza un pago provisional dentro de una transacción:
     * 1. Revierte los abonos anteriores en las ventas vinculadas
     * 2. Recalcula cabecera y detalles con los nuevos datos
     * 3. Reemplaza detalles y aplica nuevos abonos
     *
     * @param  int   $id   ID del provisional a actualizar
     * @param  array $data Datos validados del request
     * @return VentaProvisional El registro actualizado
     *
     * @throws \Exception Si ocurre cualquier error
     */
    public function updateProvisional(int $id, array $data): VentaProvisional
    {
        return DB::transaction(function () use ($id, $data) {
            $provisional = VentaProvisional::with('detalles')->findOrFail($id);

            // 1) Procesar nuevos datos
            $provisionalData = $this->processProvisionalData($data, false);

            // 2) Revertir abonos anteriores en ventas
            $this->revertirDetallesEnVentas($provisional->id);

            // 3) Actualizar cabecera y recrear detalles
            $provisional->update($provisionalData['provisional']);
            $provisional->detalles()->delete();
            if (!empty($provisionalData['detalles'])) {
                $provisional->detalles()->createMany($provisionalData['detalles']);
                $this->aplicarDetallesEnVentas($provisional->id);
            }

            return $provisional;
        });
    }

    /**
     * Elimina un pago provisional dentro de una transacción:
     * 1. Revierte los abonos en las ventas vinculadas
     * 2. Elimina detalles y cabecera
     *
     * @param  int  $id ID del provisional a eliminar
     * @return void
     *
     * @throws \Exception Si ocurre cualquier error
     */
    public function deleteProvisional(int $id): void
    {
        DB::transaction(function () use ($id) {
            $provisional = VentaProvisional::with('detalles')->findOrFail($id);

            // Revertir impacto en ventas si tiene detalles
            if ($provisional->detalles->isNotEmpty()) {
                foreach ($provisional->detalles as $detalle) {
                    if (!$detalle->venta_id || $detalle->monto <= 0) {
                        continue;
                    }

                    $venta = Venta::where('id', $detalle->venta_id)
                        ->where('estado', '!=', 'anulada')
                        ->lockForUpdate()
                        ->first();

                    if (!$venta) continue;

                    $m = (float)$detalle->monto;

                    // Restar abono
                    $venta->abonos = (float)($venta->abonos ?? 0) - $m;
                    if ($venta->abonos < 0) {
                        $venta->abonos = 0;
                    }

                    // Sumar saldo
                    $venta->saldo = (float)($venta->saldo ?? 0) + $m;

                    $venta->save();
                }
            }

            // Eliminar detalles y cabecera
            $provisional->detalles()->delete();
            $provisional->delete();
        });
    }

    /**
     * Procesa y construye los datos del provisional (cabecera + detalles).
     *
     * Consulta el cliente y las ventas vinculadas, calcula el monto libre
     * (no aplicado a ninguna venta) y determina el tipo (APLICADO o ADELANTO).
     *
     * @param  array $data  Datos validados del request
     * @param  bool  $isNew true=creación (asigna user_id, numero_recibo), false=edición
     * @return array ['provisional' => [...], 'detalles' => [...]]
     */
    private function processProvisionalData(array $data, bool $isNew = true): array
    {
        $cliente = Cliente::find($data['cliente_id']);
        $ventasInput = $data['ventas'] ?? [];

        // Cargar ventas involucradas
        $ventas = Venta::whereIn('id', collect($ventasInput)->pluck('venta_id')->filter())
            ->where('estado', '!=', 'anulada')
            ->get()
            ->keyBy('id');

        // Calcular detalles (filtra montos <= 0 y ventas inexistentes)
        $detallesCalculados = [];
        foreach ($ventasInput as $detalle) {
            $monto = (float)($detalle['monto'] ?? 0);
            if ($monto <= 0) continue;

            $ventaId = $detalle['venta_id'] ?? null;
            if (!$ventaId || !isset($ventas[$ventaId])) continue;

            $detallesCalculados[] = $this->calculateDetail(
                $ventas[$ventaId],
                $detalle,
                $cliente->razon_social ?? ''
            );
        }

        // Calcular monto libre (total - aplicado)
        $totalAplicado = collect($detallesCalculados)
            ->sum(fn($d) => (float)($d['monto'] ?? 0));

        $totalProvisional = (float)($data['total_cobranza'] ?? 0);
        $montoLibre = $totalProvisional - $totalAplicado;

        // Construir cabecera
        $provisionalData = [
            'numero_interno'    => $data['numero_interno'],
            'fecha_provisional' => $data['fecha_provisional'] ?? now(),
            'monto'             => $data['total_cobranza'] ?? 0,
            'libre'             => $montoLibre,
            'cliente_id'        => $data['cliente_id'],
            'cliente_nombre'    => $cliente->razon_social ?? '',
            'importe_p'         => $data['principal'] ?? 0,
            'importe_d'         => $data['deposito'] ?? 0,
            'importe_c'         => $data['consorcio'] ?? 0,
            'tipo'              => count($detallesCalculados) ? 'APLICADO' : 'ADELANTO',
        ];

        if ($isNew) {
            $provisionalData['numero_recibo'] = $data['numero_recibo'];
            $provisionalData['user_id']       = auth()->id();
            $provisionalData['user_nombre']   = auth()->user()->name;
        }

        return [
            'provisional' => $provisionalData,
            'detalles'    => $detallesCalculados
        ];
    }

    /**
     * Aplica los montos de los detalles como abonos en las ventas vinculadas.
     * Agrupa por venta_id y suma abonos / resta saldo con lockForUpdate.
     *
     * @param  int  $provisionalId ID del provisional cuyos detalles se aplican
     * @return void
     */
    private function aplicarDetallesEnVentas(int $provisionalId): void
    {
        $sumas = DB::table('venta_provisional_detalles')
            ->selectRaw('venta_id, SUM(monto) as total')
            ->where('venta_provisional_id', $provisionalId)
            ->whereNotNull('venta_id')
            ->where('monto', '>', 0)
            ->groupBy('venta_id')
            ->get();

        foreach ($sumas as $s) {
            $venta = Venta::where('id', $s->venta_id)
                ->where('estado', '!=', 'anulada')
                ->lockForUpdate()
                ->firstOrFail();

            $m = (float)$s->total;

            $venta->abonos = (float)($venta->abonos ?? 0) + $m;
            $venta->saldo  = (float)($venta->saldo ?? 0) - $m;
            if ($venta->saldo < 0) $venta->saldo = 0;

            $venta->save();
        }
    }

    /**
     * Revierte los abonos previamente aplicados a las ventas vinculadas.
     * Agrupa por venta_id y resta abonos / suma saldo con lockForUpdate.
     *
     * @param  int  $provisionalId ID del provisional cuyos detalles se revierten
     * @return void
     */
    private function revertirDetallesEnVentas(int $provisionalId): void
    {
        $sumas = DB::table('venta_provisional_detalles')
            ->selectRaw('venta_id, SUM(monto) as total')
            ->where('venta_provisional_id', $provisionalId)
            ->whereNotNull('venta_id')
            ->where('monto', '>', 0)
            ->groupBy('venta_id')
            ->get();

        foreach ($sumas as $s) {
            $venta = Venta::where('id', $s->venta_id)
                ->where('estado', '!=', 'anulada')
                ->lockForUpdate()
                ->first();
            if (!$venta) continue;

            $m = (float)$s->total;

            $venta->abonos = (float)($venta->abonos ?? 0) - $m;
            if ($venta->abonos < 0) $venta->abonos = 0;

            $venta->saldo = (float)($venta->saldo ?? 0) + $m;

            $venta->save();
        }
    }

    /**
     * Calcula un detalle individual del provisional (genera comentario automático).
     *
     * @param  Venta  $venta          Venta vinculada
     * @param  array  $detalle        Datos del detalle desde el request
     * @param  string $clienteNombre  Nombre del cliente para el comentario
     * @return array  Detalle listo para createMany()
     */
    private function calculateDetail($venta, $detalle, string $clienteNombre): array
    {
        $comentarioBase = '';
        $doc = trim(($detalle['comprobante_tipo_codigo'] ?? '') . ($detalle['serie'] && $detalle['correlativo'] ? "{$detalle['serie']}-{$detalle['correlativo']}" : ''));
        $autoComentario = trim("COBRANZA A {$doc} {$clienteNombre}");
        $comentarioFinal = $comentarioBase !== '' ? ($autoComentario . ' - ' . $comentarioBase) : $autoComentario;

        return [
            'venta_id'                => $detalle['venta_id'],
            'comprobante_tipo_codigo' => $detalle['comprobante_tipo_codigo'] ?? null,
            'serie'                   => $detalle['serie'] ?? null,
            'correlativo'             => $detalle['correlativo'] ?? null,
            'monto'                   => $detalle['monto'] ?? 0,
            'comentario'              => $comentarioFinal
        ];
    }
}
