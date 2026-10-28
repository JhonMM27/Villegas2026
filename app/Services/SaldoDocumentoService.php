<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Compra;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaldoDocumentoService
{
    /** Valida el estado anterior antes de crear, quitar o reasignar aplicaciones. */
    public function validar(Venta|Compra $documento): void
    {
        $aplicado = $this->aplicado($documento);
        $historico = $this->centavos($documento->abonos_migrados ?? 0);
        $abonos = $this->centavos($documento->abonos);
        $saldo = $this->centavos($documento->saldo);
        $credito = $this->centavos($documento->total) - $this->centavos($documento->acuenta);

        if ($historico < 0 || $abonos !== $historico + $aplicado || $saldo !== $credito - $abonos || $saldo < 0) {
            $this->rechazar($documento, 'Sus abonos o saldo requieren conciliacion. Se conservaron los importes existentes.');
        }
    }

    /** Recalcula exclusivamente despues de validar y modificar las aplicaciones bajo bloqueo. */
    public function sincronizar(Venta|Compra $documento, ?int $excluirRecibo = null): void
    {
        $abonos = $this->centavos($documento->abonos_migrados ?? 0) + $this->aplicado($documento, $excluirRecibo);
        $saldo = $this->centavos($documento->total) - $this->centavos($documento->acuenta) - $abonos;
        if ($abonos < 0 || $saldo < 0) {
            $this->rechazar($documento, 'Las aplicaciones exceden el credito disponible.');
        }

        $documento->abonos = $abonos / 100;
        $documento->saldo = $saldo / 100;
        $documento->save();
    }

    /** Obtiene aplicaciones actuales; los documentos deben estar bloqueados por el llamador. */
    public function aplicado(Venta|Compra $documento, ?int $excluirRecibo = null): int
    {
        $venta = $documento instanceof Venta;
        $prefijo = $venta ? 'venta' : 'compra';
        $titular = $venta ? 'cliente_id' : 'proveedor_id';
        $detalles = DB::table($prefijo.'_provisional_detalles')
            ->where($prefijo.'_id', $documento->id)
            ->when($excluirRecibo !== null, fn ($q) => $q->where($prefijo.'_provisional_id', '!=', $excluirRecibo))
            ->orderBy('id')->lockForUpdate()->get();

        $recibos = DB::table($prefijo.'_provisionales')
            ->whereIn('id', $detalles->pluck($prefijo.'_provisional_id')->unique())
            ->get()->keyBy('id');

        $total = 0;
        foreach ($detalles as $detalle) {
            $recibo = $recibos->get($detalle->{$prefijo.'_provisional_id'});
            $monto = $this->centavos($detalle->monto);
            if (! $recibo || (int) $recibo->{$titular} !== (int) $documento->{$titular} || $monto < 0) {
                $this->rechazar($documento, 'Existe una aplicacion negativa, sin recibo o de otro titular.');
            }
            if (isset($recibo->libre) && $this->centavos($recibo->libre) < 0) {
                $this->rechazar($documento, 'Existe un recibo sobreaplicado que requiere revision.');
            }
            $total += $monto;
        }

        return $total;
    }

    public function centavos(mixed $valor): int
    {
        return (int) round((float) $valor * 100);
    }

    private function rechazar(Venta|Compra $documento, string $motivo): never
    {
        throw ValidationException::withMessages([
            $documento instanceof Venta ? 'ventas' : 'compras' => ["Documento {$documento->id}: {$motivo}"],
        ]);
    }
}
