<?php

declare(strict_types=1);

/**
 * Servicio de Ventas Provisionales (Pagos Anticipados/Cobranzas).
 *
 * Concentra la lógica de negocio para la creación, actualización y eliminación
 * de pagos provisionales aplicados a ventas, incluyendo:
 * - Generación de número de recibo correlativo
 * - Validación estricta de límites (distribuido <= recibido, monto <= saldo disponible)
 * - Bloqueo pessimitic en orden ascendente de ID para evitar desbordes y deadlocks
 * - Actualización limpia de abonos/saldos en las ventas vinculadas
 */

namespace App\Services;

use App\Models\Cliente;
use App\Models\Venta;
use App\Models\VentaProvisional;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaProvisionalService
{
    /**
     * Crea un pago provisional dentro de una transacción.
     *
     * @param  array  $data  Datos validados del request
     * @return VentaProvisional El registro recién creado
     *
     * @throws ValidationException Si alguna regla de negocio falla (HTTP 422)
     */
    public function createProvisional(array $data): VentaProvisional
    {
        return DB::transaction(function () use ($data) {

            // 1) Generar número_recibo correlativo numérico
            $ultimoRecibo = VentaProvisional::whereRaw("numero_recibo REGEXP '^[0-9]+$'")
                ->selectRaw('MAX(CAST(numero_recibo AS UNSIGNED)) as max_recibo')
                ->value('max_recibo');
            $data['numero_recibo'] = $ultimoRecibo ? ((int) $ultimoRecibo + 1) : 1;

            // 2) Procesar y validar datos (cabecera + detalles)
            $provisionalData = $this->processProvisionalData($data, true, null);

            // 3) Persistir cabecera y detalles
            $provisional = VentaProvisional::create($provisionalData['provisional']);
            if (! empty($provisionalData['detalles'])) {
                $provisional->detalles()->createMany($provisionalData['detalles']);
                $this->aplicarDetallesEnVentas($provisional->id);
            }

            return $provisional;
        });
    }

    /**
     * Actualiza un pago provisional dentro de una transacción.
     *
     * @param  int  $id  ID del provisional a actualizar
     * @param  array  $data  Datos validados del request
     * @return VentaProvisional El registro actualizado
     *
     * @throws ValidationException Si alguna regla de negocio falla (HTTP 422)
     */
    public function updateProvisional(int $id, array $data): VentaProvisional
    {
        return DB::transaction(function () use ($id, $data) {
            $provisional = VentaProvisional::with('detalles')->findOrFail($id);

            // 1) Procesar y validar nuevos datos (recalculando saldo efectivo con previas aplicaciones)
            $provisionalData = $this->processProvisionalData($data, false, $provisional->id);

            // 2) Revertir abonos anteriores en ventas
            $this->revertirDetallesEnVentas($provisional->id);

            // 3) Actualizar cabecera y recrear detalles
            $provisional->update($provisionalData['provisional']);
            $provisional->detalles()->delete();
            if (! empty($provisionalData['detalles'])) {
                $provisional->detalles()->createMany($provisionalData['detalles']);
                $this->aplicarDetallesEnVentas($provisional->id);
            }

            return $provisional;
        });
    }

    /**
     * Elimina un pago provisional dentro de una transacción.
     *
     * @param  int  $id  ID del provisional a eliminar
     */
    public function deleteProvisional(int $id): void
    {
        DB::transaction(function () use ($id) {
            $provisional = VentaProvisional::with('detalles')->findOrFail($id);

            // Revertir impacto en ventas si tiene detalles
            if ($provisional->detalles->isNotEmpty()) {
                $this->revertirDetallesEnVentas($provisional->id);
            }

            // Eliminar detalles y cabecera
            $provisional->detalles()->delete();
            $provisional->delete();
        });
    }

    /**
     * Procesa y valida de manera estricta los datos del provisional.
     *
     * @param  array  $data  Datos validados del request
     * @param  bool  $isNew  true=creación, false=edición
     * @param  int|null  $provisionalId  ID del provisional si se está editando
     * @return array ['provisional' => [...], 'detalles' => [...]]
     *
     * @throws ValidationException
     */
    private function processProvisionalData(array $data, bool $isNew = true, ?int $provisionalId = null): array
    {
        $cliente = Cliente::findOrFail($data['cliente_id']);
        $ventasInput = $data['ventas'] ?? [];

        // 1. Validar coincidencia exacta de medios de pago en centavos
        $pCents = (int) round(((float) ($data['principal'] ?? 0)) * 100);
        $dCents = (int) round(((float) ($data['deposito'] ?? 0)) * 100);
        $cCents = (int) round(((float) ($data['consorcio'] ?? 0)) * 100);
        $totalCents = (int) round(((float) ($data['total_cobranza'] ?? 0)) * 100);

        if (($pCents + $dCents + $cCents) !== $totalCents) {
            throw ValidationException::withMessages([
                'total_cobranza' => ['Los medios de pago (Principal, Depósito, Consorcio) deben sumar exactamente el total recibido.'],
            ]);
        }

        // 2. Extraer e inspeccionar ventas vinculadas
        $ventaIdsInput = [];
        foreach ($ventasInput as $det) {
            $vId = $det['venta_id'] ?? null;
            $m = (float) ($det['monto'] ?? 0);
            if ($vId && $m > 0) {
                $ventaIdsInput[] = (int) $vId;
            }
        }

        // 3. Validar no duplicados en la lista enviada
        if (count($ventaIdsInput) !== count(array_unique($ventaIdsInput))) {
            throw ValidationException::withMessages([
                'ventas' => ['No se permiten ventas duplicadas en la misma distribución.'],
            ]);
        }

        // 4. Obtener aplicaciones previas de este recibo si es edición
        $prevApplications = [];
        if ($provisionalId) {
            $prevDetalles = DB::table('venta_provisional_detalles')
                ->where('venta_provisional_id', $provisionalId)
                ->whereNotNull('venta_id')
                ->get();
            foreach ($prevDetalles as $pd) {
                $prevApplications[(int) $pd->venta_id] = (int) round(((float) $pd->monto) * 100);
            }
        }

        // 5. Cargar y bloquear ventas en ORDEN ASCENDENTE DE ID para evitar deadlocks
        $uniqueVentaIds = array_values(array_unique($ventaIdsInput));
        sort($uniqueVentaIds);

        $ventas = [];
        if (! empty($uniqueVentaIds)) {
            $ventas = Venta::whereIn('id', $uniqueVentaIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
        }

        $detallesCalculados = [];
        $totalDistribuidoCents = 0;

        foreach ($ventasInput as $detalle) {
            $monto = (float) ($detalle['monto'] ?? 0);
            $montoCents = (int) round($monto * 100);
            if ($montoCents <= 0) {
                continue;
            }

            $ventaId = (int) ($detalle['venta_id'] ?? 0);
            if (! $ventaId || ! isset($ventas[$ventaId])) {
                throw ValidationException::withMessages([
                    'ventas' => ["La venta ID {$ventaId} no existe o no es válida."],
                ]);
            }

            $venta = $ventas[$ventaId];

            // Validar pertenencia al cliente
            if ((int) $venta->cliente_id !== (int) $data['cliente_id']) {
                throw ValidationException::withMessages([
                    'ventas' => ["La venta {$venta->serie}-{$venta->correlativo} pertenece a otro cliente."],
                ]);
            }

            // Validar estado no anulado
            if (strtolower(trim($venta->estado)) === 'anulada') {
                throw ValidationException::withMessages([
                    'ventas' => ["La venta {$venta->serie}-{$venta->correlativo} se encuentra anulada y no puede recibir abonos."],
                ]);
            }

            // Validar saldo disponible (saldo actual + aplicación previa del mismo recibo)
            $saldoActualCents = (int) round(((float) $venta->saldo) * 100);
            $aplicacionPreviaCents = $prevApplications[$ventaId] ?? 0;
            $saldoDisponibleCents = $saldoActualCents + $aplicacionPreviaCents;

            if ($montoCents > $saldoDisponibleCents) {
                $saldoDisponibleFmt = number_format($saldoDisponibleCents / 100, 2, '.', ',');
                throw ValidationException::withMessages([
                    'ventas' => ['El monto asignado (S/ '.number_format($monto, 2).") a la venta {$venta->serie}-{$venta->correlativo} supera su saldo disponible (S/ {$saldoDisponibleFmt})."],
                ]);
            }

            $totalDistribuidoCents += $montoCents;

            $detallesCalculados[] = $this->calculateDetail(
                $venta,
                $detalle,
                $cliente->razon_social ?? ''
            );
        }

        // 6. Validar que la suma distribuida NO supere el total recibido
        if ($totalDistribuidoCents > $totalCents) {
            $distFmt = number_format($totalDistribuidoCents / 100, 2, '.', ',');
            $recFmt = number_format($totalCents / 100, 2, '.', ',');
            throw ValidationException::withMessages([
                'ventas' => ["La suma distribuida (S/ {$distFmt}) no puede superar el total recibido (S/ {$recFmt})."],
            ]);
        }

        $montoLibre = round(($totalCents - $totalDistribuidoCents) / 100, 2);

        $provisionalData = [
            'numero_interno' => $data['numero_interno'],
            'fecha_provisional' => $data['fecha_provisional'] ?? now(),
            'monto' => $data['total_cobranza'] ?? 0,
            'libre' => $montoLibre,
            'cliente_id' => $data['cliente_id'],
            'cliente_nombre' => $cliente->razon_social ?? '',
            'importe_p' => $data['principal'] ?? 0,
            'importe_d' => $data['deposito'] ?? 0,
            'importe_c' => $data['consorcio'] ?? 0,
            'tipo' => count($detallesCalculados) ? 'APLICADO' : 'ADELANTO',
        ];

        if ($isNew) {
            $provisionalData['numero_recibo'] = $data['numero_recibo'];
            $provisionalData['user_id'] = auth()->id();
            $provisionalData['user_nombre'] = auth()->user()?->name ?? 'SISTEMA';
        }

        return [
            'provisional' => $provisionalData,
            'detalles' => $detallesCalculados,
        ];
    }

    /**
     * Aplica los montos de los detalles como abonos en las ventas vinculadas.
     */
    private function aplicarDetallesEnVentas(int $provisionalId): void
    {
        $detalles = DB::table('venta_provisional_detalles')
            ->selectRaw('venta_id, SUM(monto) as total')
            ->where('venta_provisional_id', $provisionalId)
            ->whereNotNull('venta_id')
            ->where('monto', '>', 0)
            ->groupBy('venta_id')
            ->get();

        $ventaIds = $detalles->pluck('venta_id')->map(fn ($id) => (int) $id)->sort()->values()->all();

        if (empty($ventaIds)) {
            return;
        }

        $ventas = Venta::whereIn('id', $ventaIds)
            ->where('estado', '!=', 'anulada')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($detalles as $s) {
            $vId = (int) $s->venta_id;
            if (! isset($ventas[$vId])) {
                continue;
            }
            $venta = $ventas[$vId];
            $m = (float) $s->total;

            $venta->abonos = (float) ($venta->abonos ?? 0) + $m;
            $venta->saldo = (float) ($venta->saldo ?? 0) - $m;
            if ($venta->saldo < 0) {
                $venta->saldo = 0;
            }

            $venta->save();
        }
    }

    /**
     * Revierte los abonos previamente aplicados a las ventas vinculadas.
     */
    private function revertirDetallesEnVentas(int $provisionalId): void
    {
        $detalles = DB::table('venta_provisional_detalles')
            ->selectRaw('venta_id, SUM(monto) as total')
            ->where('venta_provisional_id', $provisionalId)
            ->whereNotNull('venta_id')
            ->where('monto', '>', 0)
            ->groupBy('venta_id')
            ->get();

        $ventaIds = $detalles->pluck('venta_id')->map(fn ($id) => (int) $id)->sort()->values()->all();

        if (empty($ventaIds)) {
            return;
        }

        $ventas = Venta::whereIn('id', $ventaIds)
            ->where('estado', '!=', 'anulada')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($detalles as $s) {
            $vId = (int) $s->venta_id;
            if (! isset($ventas[$vId])) {
                continue;
            }
            $venta = $ventas[$vId];
            $m = (float) $s->total;

            $venta->abonos = (float) ($venta->abonos ?? 0) - $m;
            if ($venta->abonos < 0) {
                $venta->abonos = 0;
            }

            $venta->saldo = (float) ($venta->saldo ?? 0) + $m;

            $venta->save();
        }
    }

    /**
     * Calcula un detalle individual del provisional.
     */
    private function calculateDetail($venta, array $detalle, string $clienteNombre): array
    {
        $comentarioBase = '';
        $doc = trim(($detalle['comprobante_tipo_codigo'] ?? '').($detalle['serie'] && $detalle['correlativo'] ? " {$detalle['serie']}-{$detalle['correlativo']}" : ''));
        $autoComentario = trim("COBRANZA A {$doc} {$clienteNombre}");
        $comentarioFinal = $comentarioBase !== '' ? ($autoComentario.' - '.$comentarioBase) : $autoComentario;

        return [
            'venta_id' => $detalle['venta_id'],
            'comprobante_tipo_codigo' => $detalle['comprobante_tipo_codigo'] ?? null,
            'serie' => $detalle['serie'] ?? null,
            'correlativo' => $detalle['correlativo'] ?? null,
            'monto' => $detalle['monto'] ?? 0,
            'comentario' => $comentarioFinal,
        ];
    }
}
