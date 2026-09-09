<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Gasto;
use App\Models\PlanillaPago;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PlanillaSincronizarGastosCommand extends Command
{
    protected $signature = 'planilla:sincronizar-gastos {--aplicar : Persiste las correcciones detectadas}';

    protected $description = 'Revisa y sincroniza los gastos automáticos de planilla con los pagos confirmados';

    public function handle(): int
    {
        $periodos = PlanillaPago::query()
            ->select('mes', 'anio')
            ->distinct()
            ->orderBy('anio')->orderBy('mes')->get();

        $hallazgos = [];
        foreach ($periodos as $periodo) {
            $pagos = PlanillaPago::where('mes', $periodo->mes)->where('anio', $periodo->anio)
                ->where('estado', 'pagado')->get();
            $esperado = [
                'monto' => round((float) $pagos->sum('total_pagar'), 2),
                'importe_p' => round((float) $pagos->sum('importe_p'), 2),
                'importe_d' => round((float) $pagos->sum('importe_d'), 2),
                'importe_c' => round((float) $pagos->sum('importe_c'), 2),
            ];
            $gastos = Gasto::where('planilla_mes', $periodo->mes)->where('planilla_anio', $periodo->anio)
                ->whereNull('empleado_id')->get();
            $estado = $gastos->count() > 1 ? 'ambiguous' : ($gastos->count() === 0 ? 'missing' : 'ok');
            if ($estado === 'ok') {
                $gasto = $gastos->first();
                $actual = [
                    'monto' => round((float) $gasto->monto, 2), 'importe_p' => round((float) $gasto->importe_p, 2),
                    'importe_d' => round((float) $gasto->importe_d, 2), 'importe_c' => round((float) $gasto->importe_c, 2),
                ];
                if ($actual === $esperado) {
                    continue;
                }
                $hallazgos[] = [$gasto, $actual, $esperado, 'corregir'];
            } elseif ($esperado['monto'] > 0 || $estado === 'ambiguous') {
                $hallazgos[] = [null, ['monto' => $gastos->count()], $esperado, $estado];
            }
        }

        if ($hallazgos === []) {
            $this->info('No se encontraron diferencias en gastos de planilla.');

            return self::SUCCESS;
        }
        $filas = [];
        foreach ($hallazgos as [$gasto, $actual, $esperado, $estado]) {
            $filas[] = [
                $gasto ? $gasto->planilla_mes.'/'.$gasto->planilla_anio : 'sin gasto', $estado,
                json_encode($actual), json_encode($esperado),
            ];
        }
        $this->table(['Período', 'Estado', 'Actual', 'Esperado'], $filas);
        if (! $this->option('aplicar')) {
            $this->warn('Modo revisión: use --aplicar para persistir correcciones inequívocas.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($hallazgos): void {
            foreach ($hallazgos as [$gasto, $actual, $esperado, $estado]) {
                if ($estado !== 'corregir' || ! $gasto) {
                    continue;
                }
                $gasto->update($esperado);
            }
        });
        $this->info('Correcciones aplicadas; casos ambiguos permanecen sin cambios.');

        return self::SUCCESS;
    }
}
