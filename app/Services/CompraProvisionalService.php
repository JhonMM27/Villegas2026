<?php

/**
 * Servicio de Compras Provisionales (Pagos Anticipados/Cobranzas a Proveedores).
 *
 * Concentra la lógica de negocio para la creación, actualización y eliminación
 * de pagos provisionales aplicados a compras, incluyendo:
 * - Generación de número de recibo correlativo
 * - Cálculo de montos aplicados vs libres
 * - Actualización de abonos/saldos en las compras vinculadas
 */

namespace App\Services;

use App\Models\CompraProvisional;
use App\Models\Compra;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class CompraProvisionalService
{
    /**
     * Crea un pago provisional de compra dentro de una transacción:
     * 1. Genera el siguiente número de recibo correlativo
     * 2. Procesa datos de cabecera y detalles (montos por compra)
     * 3. Crea el registro CompraProvisional + detalles
     * 4. Aplica abonos a las compras vinculadas (suma abonos, resta saldo)
     *
     * @param  array $data Datos validados del request
     * @return CompraProvisional El registro recién creado
     *
     * @throws \Exception Si ocurre cualquier error
     */
    public function createProvisional(array $data): CompraProvisional
    {
        return DB::transaction(function () use ($data) {

            // 1) Generar número_recibo correlativo numérico
            $ultimoRecibo = CompraProvisional::whereRaw("numero_recibo REGEXP '^[0-9]+$'")
                ->selectRaw("MAX(CAST(numero_recibo AS UNSIGNED)) as max_recibo")
                ->value('max_recibo');
            $data['numero_recibo'] = $ultimoRecibo ? ((int)$ultimoRecibo + 1) : 1;

            // 2) Procesar datos (cabecera + detalles calculados)
            $provisionalData = $this->processProvisionalData($data, true);

            // 3) Persistir cabecera y detalles
            $provisional = CompraProvisional::create($provisionalData['provisional']);
            if (!empty($provisionalData['detalles'])) {
                $provisional->detalles()->createMany($provisionalData['detalles']);
                $this->aplicarDetallesEnCompras($provisional->id);
            }

            return $provisional;
        });
    }

    /**
     * Actualiza un pago provisional de compra dentro de una transacción:
     * 1. Revierte los abonos anteriores en las compras vinculadas
     * 2. Recalcula cabecera y detalles con los nuevos datos
     * 3. Reemplaza detalles y aplica nuevos abonos
     *
     * @param  int   $id   ID del provisional a actualizar
     * @param  array $data Datos validados del request
     * @return CompraProvisional El registro actualizado
     *
     * @throws \Exception Si ocurre cualquier error
     */
    public function updateProvisional(int $id, array $data): CompraProvisional
    {
        return DB::transaction(function () use ($id, $data) {
            $provisional = CompraProvisional::with('detalles')->findOrFail($id);

            // 1) Procesar nuevos datos
            $provisionalData = $this->processProvisionalData($data, false);

            // 2) Revertir abonos anteriores en compras
            $this->revertirDetallesEnCompras($provisional->id);

            // 3) Actualizar cabecera y recrear detalles
            $provisional->update($provisionalData['provisional']);
            $provisional->detalles()->delete();
            if (!empty($provisionalData['detalles'])) {
                $provisional->detalles()->createMany($provisionalData['detalles']);
                $this->aplicarDetallesEnCompras($provisional->id);
            }

            return $provisional;
        });
    }

    /**
     * Elimina un pago provisional de compra dentro de una transacción:
     * 1. Revierte los abonos en las compras vinculadas
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
            $provisional = CompraProvisional::with('detalles')->findOrFail($id);

            // Revertir impacto en compras si tiene detalles
            if ($provisional->detalles->isNotEmpty()) {
                foreach ($provisional->detalles as $detalle) {
                    if (!$detalle->compra_id || $detalle->monto <= 0) {
                        continue;
                    }

                    $compra = Compra::where('id', $detalle->compra_id)
                        ->where('estado', '!=', 'anulada')
                        ->lockForUpdate()
                        ->first();

                    if (!$compra) continue;

                    $m = (float)$detalle->monto;

                    // Restar abono
                    $compra->abonos = (float)($compra->abonos ?? 0) - $m;
                    if ($compra->abonos < 0) {
                        $compra->abonos = 0;
                    }

                    // Sumar saldo
                    $compra->saldo = (float)($compra->saldo ?? 0) + $m;

                    $compra->save();
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
     * Consulta el proveedor y las compras vinculadas, calcula el monto libre
     * (no aplicado a ninguna compra) y determina el tipo (APLICADO o ADELANTO).
     *
     * @param  array $data  Datos validados del request
     * @param  bool  $isNew true=creación (asigna user_id, numero_recibo), false=edición
     * @return array ['provisional' => [...], 'detalles' => [...]]
     */
    private function processProvisionalData(array $data, bool $isNew = true): array
    {
        $proveedor = Proveedor::find($data['proveedor_id']);
        $comprasInput = $data['compras'] ?? [];

        // Cargar compras involucradas
        $compras = Compra::whereIn('id', collect($comprasInput)->pluck('compra_id')->filter())
            ->where('estado', '!=', 'anulada')
            ->get()
            ->keyBy('id');

        // Calcular detalles (filtra montos <= 0 y compras inexistentes)
        $detallesCalculados = [];
        foreach ($comprasInput as $detalle) {
            $monto = (float)($detalle['monto'] ?? 0);
            if ($monto <= 0) continue;

            $compraId = $detalle['compra_id'] ?? null;
            if (!$compraId || !isset($compras[$compraId])) continue;

            $detallesCalculados[] = $this->calculateDetail(
                $compras[$compraId],
                $detalle,
                $proveedor->razon_social ?? ''
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
            'proveedor_id'      => $data['proveedor_id'],
            'proveedor_nombre'  => $proveedor->razon_social ?? '',
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
     * Aplica los montos de los detalles como abonos en las compras vinculadas.
     * Agrupa por compra_id y suma abonos / resta saldo con lockForUpdate.
     *
     * @param  int  $provisionalId ID del provisional cuyos detalles se aplican
     * @return void
     */
    private function aplicarDetallesEnCompras(int $provisionalId): void
    {
        $sumas = DB::table('compra_provisional_detalles')
            ->selectRaw('compra_id, SUM(monto) as total')
            ->where('compra_provisional_id', $provisionalId)
            ->whereNotNull('compra_id')
            ->where('monto', '>', 0)
            ->groupBy('compra_id')
            ->get();

        foreach ($sumas as $s) {
            $compra = Compra::where('id', $s->compra_id)
                ->where('estado', '!=', 'anulada')
                ->lockForUpdate()
                ->firstOrFail();

            $m = (float)$s->total;

            $compra->abonos = (float)($compra->abonos ?? 0) + $m;
            $compra->saldo  = (float)($compra->saldo ?? 0) - $m;
            if ($compra->saldo < 0) $compra->saldo = 0;

            $compra->save();
        }
    }

    /**
     * Revierte los abonos previamente aplicados a las compras vinculadas.
     * Agrupa por compra_id y resta abonos / suma saldo con lockForUpdate.
     *
     * @param  int  $provisionalId ID del provisional cuyos detalles se revierten
     * @return void
     */
    private function revertirDetallesEnCompras(int $provisionalId): void
    {
        $sumas = DB::table('compra_provisional_detalles')
            ->selectRaw('compra_id, SUM(monto) as total')
            ->where('compra_provisional_id', $provisionalId)
            ->whereNotNull('compra_id')
            ->where('monto', '>', 0)
            ->groupBy('compra_id')
            ->get();

        foreach ($sumas as $s) {
            $compra = Compra::where('id', $s->compra_id)
                ->where('estado', '!=', 'anulada')
                ->lockForUpdate()
                ->first();
            if (!$compra) continue;

            $m = (float)$s->total;

            $compra->abonos = (float)($compra->abonos ?? 0) - $m;
            if ($compra->abonos < 0) $compra->abonos = 0;

            $compra->saldo = (float)($compra->saldo ?? 0) + $m;

            $compra->save();
        }
    }

    /**
     * Calcula un detalle individual del provisional (genera comentario automático).
     *
     * @param  Compra $compra           Compra vinculada
     * @param  array  $detalle          Datos del detalle desde el request
     * @param  string $proveedorNombre  Nombre del proveedor para el comentario
     * @return array  Detalle listo para createMany()
     */
    private function calculateDetail(Compra $compra, array $detalle, string $proveedorNombre): array
    {
        $comentarioBase = '';
        $doc = trim(($detalle['comprobante_tipo_codigo'] ?? '') . ($detalle['serie'] && $detalle['correlativo'] ? "{$detalle['serie']}-{$detalle['correlativo']}" : ''));
        $autoComentario = trim("COBRANZA A {$doc} {$proveedorNombre}");
        $comentarioFinal = $comentarioBase !== '' ? ($autoComentario . ' - ' . $comentarioBase) : $autoComentario;

        return [
            'compra_id'               => $detalle['compra_id'],
            'comprobante_tipo_codigo' => $detalle['comprobante_tipo_codigo'] ?? null,
            'serie'                   => $detalle['serie'] ?? null,
            'correlativo'             => $detalle['correlativo'] ?? null,
            'monto'                   => $detalle['monto'] ?? 0,
            'comentario'              => $comentarioFinal
        ];
    }
}
