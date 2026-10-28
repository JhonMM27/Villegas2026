<?php

declare(strict_types=1);

/**
 * Servicio de Compras Provisionales (Pagos Anticipados/Cobranzas a Proveedores).
 *
 * Concentra la lógica de negocio para la creación, actualización y eliminación
 * de pagos provisionales aplicados a compras, incluyendo:
 * - Generación de número de recibo correlativo
 * - Validación estricta de límites (distribuido <= pagado, monto <= saldo disponible)
 * - Bloqueo pessimitic en orden ascendente de ID para evitar desbordes y deadlocks
 * - Actualización limpia de abonos/saldos en las compras vinculadas
 */

namespace App\Services;

use App\Models\Compra;
use App\Models\CompraProvisional;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompraProvisionalService
{
    /**
     * Crea un pago provisional dentro de una transacción.
     *
     * @param  array  $data  Datos validados del request
     * @return CompraProvisional El registro recién creado
     *
     * @throws ValidationException Si alguna regla de negocio falla (HTTP 422)
     */
    public function createProvisional(array $data): CompraProvisional
    {
        return DB::transaction(function () use ($data) {
            $control = app(ReciboControlService::class);
            $control->bloquear('compra_provisionales');
            if ($previo = $control->buscarReintento('compra_provisionales', $data)) {
                return $previo;
            }
            $data['numero_recibo'] = $control->siguienteNumero('compra_provisionales');

            // 2) Procesar y validar datos (cabecera + detalles)
            $provisionalData = $this->processProvisionalData($data, true, null);

            // 3) Persistir cabecera y detalles
            $provisional = CompraProvisional::create($provisionalData['provisional']);
            if (! empty($provisionalData['detalles'])) {
                $provisional->detalles()->createMany($provisionalData['detalles']);
                $this->aplicarDetallesEnCompras($provisional->id);
            }

            $control->registrarSolicitud('compra_provisionales', $data, $provisional->id);
            $auditoria = app(AuditoriaService::class);
            $nuevos = $auditoria->capturar('compra_provisionales', $provisional);
            $auditoria->registrarOperacionRecibo('creacion', $provisional, ['cabecera' => [], 'detalles' => []], $nuevos);

            return $provisional;
        }, 3);
    }

    /**
     * Actualiza un pago provisional dentro de una transacción.
     *
     * @param  int  $id  ID del provisional a actualizar
     * @param  array  $data  Datos validados del request
     * @return CompraProvisional El registro actualizado
     *
     * @throws ValidationException Si alguna regla de negocio falla (HTTP 422)
     */
    public function updateProvisional(int $id, array $data): CompraProvisional
    {
        return DB::transaction(function () use ($id, $data) {
            app(ReciboControlService::class)->bloquear('compra_provisionales');
            $provisional = CompraProvisional::lockForUpdate()->findOrFail($id);
            app(ReciboControlService::class)->validarAnterior($provisional, $data);
            $this->bloquearYValidarCompras($provisional->id, $data['compras'] ?? []);
            $auditoria = app(AuditoriaService::class);
            $antes = $auditoria->capturar('compra_provisionales', $provisional);

            // 1) Procesar y validar nuevos datos
            $provisionalData = $this->processProvisionalData($data, false, $provisional->id);

            // 2) Revertir abonos anteriores en compras
            $this->revertirDetallesEnCompras($provisional->id);

            // 3) Actualizar cabecera y recrear detalles
            $provisional->update($provisionalData['provisional']);
            $provisional->detalles()->delete();
            if (! empty($provisionalData['detalles'])) {
                $provisional->detalles()->createMany($provisionalData['detalles']);
                $this->aplicarDetallesEnCompras($provisional->id);
            }

            $provisional->unsetRelation('detalles');
            $auditoria->registrarOperacionRecibo('modificacion', $provisional, $antes, $auditoria->capturar('compra_provisionales', $provisional));

            return $provisional;
        }, 3);
    }

    /**
     * Elimina un pago provisional dentro de una transacción.
     *
     * @param  int  $id  ID del provisional a eliminar
     */
    public function deleteProvisional(int $id): void
    {
        DB::transaction(function () use ($id) {
            app(ReciboControlService::class)->bloquear('compra_provisionales');
            $provisional = CompraProvisional::lockForUpdate()->findOrFail($id);
            app(ReciboControlService::class)->validarAnterior($provisional);
            $this->bloquearYValidarCompras($provisional->id);
            $provisional->load('detalles');
            $auditoria = app(AuditoriaService::class);
            $antes = $auditoria->capturar('compra_provisionales', $provisional);

            // Revertir impacto en compras si tiene detalles
            if ($provisional->detalles->isNotEmpty()) {
                $this->revertirDetallesEnCompras($provisional->id);
            }

            // Eliminar detalles y cabecera
            $provisional->detalles()->delete();
            $provisional->delete();
            $despues = ['cabecera' => array_map(fn ($campo) => array_replace($campo, ['valor' => null]), $antes['cabecera']), 'detalles' => []];
            $auditoria->registrarOperacionRecibo('eliminacion', $provisional, $antes, $despues);
        }, 3);
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
        $proveedor = Proveedor::findOrFail($data['proveedor_id']);
        $comprasInput = $data['compras'] ?? [];

        // 1. Validar coincidencia exacta de medios de pago en centavos
        $pCents = (int) round(((float) ($data['principal'] ?? 0)) * 100);
        $dCents = (int) round(((float) ($data['deposito'] ?? 0)) * 100);
        $cCents = (int) round(((float) ($data['consorcio'] ?? 0)) * 100);
        $totalCents = (int) round(((float) ($data['total_cobranza'] ?? 0)) * 100);

        if ($totalCents <= 0 || min($pCents, $dCents, $cCents) < 0 || collect($comprasInput)->contains(fn ($d) => (float) ($d['monto'] ?? 0) < 0)) {
            throw ValidationException::withMessages(['total_cobranza' => 'Ingrese un pago positivo y aplicaciones y medios de pago no negativos.']);
        }

        if (($pCents + $dCents + $cCents) !== $totalCents) {
            throw ValidationException::withMessages([
                'total_cobranza' => ['Los medios de pago (Principal, Depósito, Consorcio) deben sumar exactamente el total pagado.'],
            ]);
        }

        // 2. Extraer e inspeccionar compras vinculadas
        $compraIdsInput = [];
        foreach ($comprasInput as $det) {
            $cId = $det['compra_id'] ?? null;
            $m = (float) ($det['monto'] ?? 0);
            if ($cId && $m > 0) {
                $compraIdsInput[] = (int) $cId;
            }
        }

        // 3. Validar no duplicados en la lista enviada
        if (count($compraIdsInput) !== count(array_unique($compraIdsInput))) {
            throw ValidationException::withMessages([
                'compras' => ['No se permiten compras duplicadas en la misma distribución.'],
            ]);
        }

        // 4. Obtener aplicaciones previas de este recibo si es edición
        $prevApplications = [];
        if ($provisionalId) {
            $prevDetalles = DB::table('compra_provisional_detalles')
                ->where('compra_provisional_id', $provisionalId)
                ->whereNotNull('compra_id')
                ->get();
            foreach ($prevDetalles as $pd) {
                $cId = (int) $pd->compra_id;
                $prevApplications[$cId] = ($prevApplications[$cId] ?? 0) + (int) round(((float) $pd->monto) * 100);
            }
        }

        // 5. Cargar y bloquear compras en ORDEN ASCENDENTE DE ID para evitar deadlocks
        $uniqueCompraIds = array_values(array_unique($compraIdsInput));
        sort($uniqueCompraIds);

        $compras = [];
        if (! empty($uniqueCompraIds)) {
            $compras = Compra::whereIn('id', $uniqueCompraIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
        }

        $detallesCalculados = [];
        $totalDistribuidoCents = 0;

        foreach ($comprasInput as $detalle) {
            $monto = (float) ($detalle['monto'] ?? 0);
            $montoCents = (int) round($monto * 100);
            if ($montoCents <= 0) {
                continue;
            }

            $compraId = (int) ($detalle['compra_id'] ?? 0);
            if (! $compraId || ! isset($compras[$compraId])) {
                throw ValidationException::withMessages([
                    'compras' => ["La compra ID {$compraId} no existe o no es válida."],
                ]);
            }

            $compra = $compras[$compraId];
            app(SaldoDocumentoService::class)->validar($compra);

            // Validar pertenencia al proveedor
            if ((int) $compra->proveedor_id !== (int) $data['proveedor_id']) {
                throw ValidationException::withMessages([
                    'compras' => ["La compra {$compra->serie}-{$compra->correlativo} pertenece a otro proveedor."],
                ]);
            }

            // Validar estado no anulado
            if (strtolower(trim($compra->estado)) === 'anulada') {
                throw ValidationException::withMessages([
                    'compras' => ["La compra {$compra->serie}-{$compra->correlativo} se encuentra anulada y no puede recibir pagos."],
                ]);
            }

            // Validar saldo disponible (saldo actual + aplicación previa del mismo recibo)
            $saldoActualCents = (int) round(((float) $compra->saldo) * 100);
            $aplicacionPreviaCents = $prevApplications[$compraId] ?? 0;
            $saldoDisponibleCents = $saldoActualCents + $aplicacionPreviaCents;

            if ($montoCents > $saldoDisponibleCents) {
                $saldoDisponibleFmt = number_format($saldoDisponibleCents / 100, 2, '.', ',');
                throw ValidationException::withMessages([
                    'compras' => ['El monto asignado (S/ '.number_format($monto, 2).") a la compra {$compra->serie}-{$compra->correlativo} supera su saldo disponible (S/ {$saldoDisponibleFmt})."],
                ]);
            }

            $totalDistribuidoCents += $montoCents;

            $detallesCalculados[] = $this->calculateDetail(
                $compra,
                $detalle,
                $proveedor->razon_social ?? ''
            );
        }

        // 6. Validar que la suma distribuida NO supere el total pagado
        if ($totalDistribuidoCents > $totalCents) {
            $distFmt = number_format($totalDistribuidoCents / 100, 2, '.', ',');
            $recFmt = number_format($totalCents / 100, 2, '.', ',');
            throw ValidationException::withMessages([
                'compras' => ["La suma distribuida (S/ {$distFmt}) no puede superar el total pagado (S/ {$recFmt})."],
            ]);
        }

        $montoLibre = round(($totalCents - $totalDistribuidoCents) / 100, 2);

        $provisionalData = [
            'numero_interno' => $data['numero_interno'],
            'fecha_provisional' => $data['fecha_provisional'] ?? now(),
            'monto' => $data['total_cobranza'] ?? 0,
            'libre' => $montoLibre,
            'proveedor_id' => $data['proveedor_id'],
            'proveedor_nombre' => $proveedor->razon_social ?? '',
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
     * Aplica los montos de los detalles como abonos en las compras vinculadas.
     */
    private function aplicarDetallesEnCompras(int $provisionalId): void
    {
        $compraIds = DB::table('compra_provisional_detalles')
            ->where('compra_provisional_id', $provisionalId)
            ->whereNotNull('compra_id')
            ->where('monto', '>', 0)
            ->pluck('compra_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->sincronizarSaldosCompras($compraIds);
    }

    /**
     * Revierte los abonos previamente aplicados a las compras vinculadas.
     */
    private function revertirDetallesEnCompras(int $provisionalId): void
    {
        $compraIds = DB::table('compra_provisional_detalles')
            ->where('compra_provisional_id', $provisionalId)
            ->whereNotNull('compra_id')
            ->where('monto', '>', 0)
            ->pluck('compra_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->sincronizarSaldosCompras($compraIds, $provisionalId);
    }

    /**
     * Sincroniza abonos y saldo conservando el historico conciliado y los recibos actuales.
     */
    private function sincronizarSaldosCompras(array $compraIds, ?int $excluirProvisionalId = null): void
    {
        if (empty($compraIds)) {
            return;
        }

        $compras = Compra::whereIn('id', $compraIds)
            ->where('estado', '!=', 'anulada')
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($compras as $compra) {
            app(SaldoDocumentoService::class)->sincronizar($compra, $excluirProvisionalId);
        }
    }

    private function bloquearYValidarCompras(int $provisionalId, array $nuevos = []): void
    {
        $ids = DB::table('compra_provisional_detalles')->where('compra_provisional_id', $provisionalId)
            ->whereNotNull('compra_id')->pluck('compra_id')->merge(collect($nuevos)->pluck('compra_id'))
            ->filter()->unique()->sort()->values();
        foreach (Compra::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get() as $compra) {
            app(SaldoDocumentoService::class)->validar($compra);
        }
    }

    /**
     * Calcula un detalle individual del provisional.
     */
    private function calculateDetail($compra, array $detalle, string $proveedorNombre): array
    {
        $comentarioBase = '';
        $doc = trim(($detalle['comprobante_tipo_codigo'] ?? '').($detalle['serie'] && $detalle['correlativo'] ? " {$detalle['serie']}-{$detalle['correlativo']}" : ''));
        $autoComentario = trim("PAGO A {$doc} {$proveedorNombre}");
        $comentarioFinal = $comentarioBase !== '' ? ($autoComentario.' - '.$comentarioBase) : $autoComentario;

        return [
            'compra_id' => $detalle['compra_id'],
            'comprobante_tipo_codigo' => $detalle['comprobante_tipo_codigo'] ?? null,
            'serie' => $detalle['serie'] ?? null,
            'correlativo' => $detalle['correlativo'] ?? null,
            'monto' => $detalle['monto'] ?? 0,
            'comentario' => $comentarioFinal,
        ];
    }
}
