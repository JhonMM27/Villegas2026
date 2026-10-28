<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CompraProvisional;
use App\Models\VentaProvisional;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ReciboControlService
{
    /** Todos los cambios de recibos toman este bloqueo antes de bloquear cabeceras y documentos. */
    public function bloquear(string $modulo): void
    {
        $this->prefijo($modulo);
        if (DB::transactionLevel() < 1 || ! DB::table('recibo_contadores')->where('modulo', $modulo)->lockForUpdate()->first()) {
            throw new RuntimeException('No se pudo bloquear el contador de recibos. Verifique la migracion de proteccion.');
        }
    }

    public function siguienteNumero(string $modulo): string
    {
        $contador = DB::table('recibo_contadores')->where('modulo', $modulo)->lockForUpdate()->first();
        $siguiente = (int) $contador->ultimo_numero + 1;
        if (strlen((string) $siguiente) > 10) {
            throw ValidationException::withMessages(['numero_recibo' => 'La numeracion excede la longitud disponible.']);
        }
        DB::table('recibo_contadores')->where('modulo', $modulo)->update(['ultimo_numero' => $siguiente]);

        return (string) $siguiente;
    }

    /** Un recibo borrado conserva su clave: el reintento no puede recrear un ingreso. */
    public function buscarReintento(string $modulo, array $data): VentaProvisional|CompraProvisional|null
    {
        $clave = $this->clave($data);
        if ($clave === null) {
            return null;
        }
        $solicitud = DB::table('recibo_solicitudes')->where('modulo', $modulo)->where('clave', $clave)->lockForUpdate()->first();
        if (! $solicitud) {
            return null;
        }
        if ($solicitud->huella !== $this->huella($modulo, $data)) {
            throw ValidationException::withMessages(['idempotencia_clave' => 'Este envio ya se proceso con otros datos. Recargue el formulario.']);
        }
        $modelo = $modulo === 'venta_provisionales' ? VentaProvisional::class : CompraProvisional::class;
        $recibo = $modelo::lockForUpdate()->find($solicitud->recibo_id);
        if (! $recibo) {
            throw ValidationException::withMessages(['idempotencia_clave' => 'El recibo de este envio fue eliminado. Revise su auditoria antes de registrar otro pago.']);
        }

        return $recibo;
    }

    public function registrarSolicitud(string $modulo, array $data, int $reciboId): void
    {
        if (($clave = $this->clave($data)) !== null) {
            DB::table('recibo_solicitudes')->insert(['modulo' => $modulo, 'clave' => $clave,
                'huella' => $this->huella($modulo, $data), 'recibo_id' => $reciboId, 'created_at' => now()]);
        }
    }

    /** El formulario lleva la huella de la version leida para impedir sobrescribir otra edicion. */
    public function version(VentaProvisional|CompraProvisional $recibo): string
    {
        $recibo = $recibo->fresh('detalles') ?? throw new RuntimeException('El recibo ya no existe.');

        return hash('sha256', json_encode([$recibo->getRawOriginal(),
            $recibo->detalles->sortBy('id')->map(fn ($d) => $d->getRawOriginal())->values()->all()], JSON_THROW_ON_ERROR));
    }

    public function validarAnterior(VentaProvisional|CompraProvisional $recibo, array $data = []): void
    {
        if (isset($data['version_recibo']) && $data['version_recibo'] !== $this->version($recibo)) {
            throw ValidationException::withMessages(['version_recibo' => 'Otro usuario modifico este recibo. Cierre y vuelva a abrir el formulario.']);
        }
        $p = $this->prefijo($recibo->getTable());
        $saldos = app(SaldoDocumentoService::class);
        $detalles = DB::table($p.'_provisional_detalles')->where($p.'_provisional_id', $recibo->id)->orderBy('id')->lockForUpdate()->get();
        $aplicado = $detalles->sum(fn ($d) => $saldos->centavos($d->monto));
        $monto = $saldos->centavos($recibo->monto);
        if ($detalles->contains(fn ($d) => $saldos->centavos($d->monto) < 0) || $monto < 0 || $aplicado > $monto
            || $saldos->centavos($recibo->libre) !== $monto - $aplicado
            || $monto !== $saldos->centavos($recibo->importe_p) + $saldos->centavos($recibo->importe_d) + $saldos->centavos($recibo->importe_c)) {
            throw ValidationException::withMessages(['total_cobranza' => 'El recibo existente tiene importes inconsistentes y requiere conciliacion.']);
        }
    }

    private function clave(array $data): ?string
    {
        $clave = $data['idempotencia_clave'] ?? null;
        if ($clave !== null && ! Str::isUuid($clave)) {
            throw ValidationException::withMessages(['idempotencia_clave' => 'La clave del envio no es valida.']);
        }

        return $clave === null ? null : strtolower($clave);
    }

    private function huella(string $modulo, array $data): string
    {
        $p = $this->prefijo($modulo);
        $owner = $p === 'venta' ? 'cliente_id' : 'proveedor_id';
        $saldos = app(SaldoDocumentoService::class);
        $detalles = collect($data[$p.'s'] ?? [])->filter(fn ($d) => $saldos->centavos($d['monto'] ?? 0) > 0)
            ->map(fn ($d) => [(int) ($d[$p.'_id'] ?? 0), $saldos->centavos($d['monto']),
                (string) ($d['comprobante_tipo_codigo'] ?? ''), (string) ($d['serie'] ?? ''), (string) ($d['correlativo'] ?? '')])
            ->sortBy(fn ($d) => json_encode($d, JSON_THROW_ON_ERROR))->values()->all();

        return hash('sha256', json_encode([(int) $data[$owner], (string) ($data['numero_interno'] ?? ''),
            Carbon::parse($data['fecha_provisional'])->format('Y-m-d H:i:s'),
            $saldos->centavos($data['total_cobranza'] ?? 0), $saldos->centavos($data['principal'] ?? 0),
            $saldos->centavos($data['deposito'] ?? 0), $saldos->centavos($data['consorcio'] ?? 0), $detalles], JSON_THROW_ON_ERROR));
    }

    private function prefijo(string $modulo): string
    {
        return match ($modulo) {
            'venta_provisionales' => 'venta',
            'compra_provisionales' => 'compra',
            default => throw new RuntimeException('Modulo de recibos no permitido.'),
        };
    }
}
