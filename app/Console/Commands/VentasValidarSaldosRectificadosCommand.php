<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VentasValidarSaldosRectificadosCommand extends Command
{
    protected $signature = 'ventas:validar-saldos-rectificados
        {--cliente_id= : Limita la auditoría a un cliente}';

    protected $description = 'Audita sin modificar datos las ventas rectificadas cuyos abonos vinculados superan los reflejados en la cabecera.';

    public function handle(): int
    {
        $clienteId = $this->option('cliente_id');

        if ($clienteId !== null && filter_var($clienteId, FILTER_VALIDATE_INT) === false) {
            $this->error('La opción --cliente_id debe ser un número entero.');

            return self::INVALID;
        }

        $aplicaciones = DB::table('venta_provisional_detalles')
            ->selectRaw('venta_id, ROUND(SUM(monto), 2) AS monto_aplicado')
            ->whereNotNull('venta_id')
            ->where('monto', '>', 0)
            ->groupBy('venta_id');

        $incidencias = DB::table('ventas as venta')
            ->joinSub($aplicaciones, 'aplicaciones', function ($join) {
                $join->on('aplicaciones.venta_id', '=', 'venta.id');
            })
            ->selectRaw('
                venta.id,
                venta.cliente_id,
                venta.cliente_nombre,
                CONCAT(
                    venta.comprobante_tipo_codigo,
                    " ",
                    venta.serie,
                    "-",
                    venta.correlativo
                ) AS documento,
                venta.total,
                venta.acuenta,
                venta.abonos,
                aplicaciones.monto_aplicado,
                venta.saldo,
                ROUND(
                    venta.total
                    - venta.acuenta
                    - aplicaciones.monto_aplicado,
                    2
                ) AS saldo_segun_aplicaciones
            ')
            ->whereRaw("LOWER(TRIM(venta.estado)) = 'rectificada'")
            ->whereRaw('aplicaciones.monto_aplicado > COALESCE(venta.abonos, 0) + 0.009')
            ->when(
                $clienteId !== null,
                fn ($query) => $query->where('venta.cliente_id', (int) $clienteId)
            )
            ->orderBy('venta.cliente_id')
            ->orderBy('venta.id')
            ->get();

        if ($incidencias->isEmpty()) {
            $this->info('No se encontraron ventas rectificadas con abonos vinculados superiores a la cabecera.');

            return self::SUCCESS;
        }

        $this->warn(
            "Se encontraron {$incidencias->count()} incidencia(s). "
            .'Esta auditoría no realizó ninguna modificación.'
        );

        $this->table(
            [
                'Venta ID',
                'Cliente',
                'Documento',
                'Total',
                'A cuenta',
                'Abonos cabecera',
                'Aplicado vinculado',
                'Saldo actual',
                'Saldo según aplicaciones',
            ],
            $incidencias->map(fn ($venta) => [
                $venta->id,
                $venta->cliente_id.' - '.$venta->cliente_nombre,
                $venta->documento,
                number_format((float) $venta->total, 2, '.', ''),
                number_format((float) $venta->acuenta, 2, '.', ''),
                number_format((float) $venta->abonos, 2, '.', ''),
                number_format((float) $venta->monto_aplicado, 2, '.', ''),
                number_format((float) $venta->saldo, 2, '.', ''),
                number_format((float) $venta->saldo_segun_aplicaciones, 2, '.', ''),
            ])->all()
        );

        return self::FAILURE;
    }
}
