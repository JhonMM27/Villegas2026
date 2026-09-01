<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlanillaPago;
use App\Services\EmpleadoService;
use App\Services\PlanillaCalculoService;
use Illuminate\Console\Command;

class PlanillaValidarPagosCommand extends Command
{
    protected $signature = 'planilla:validar-pagos {--empleado_id= : Validar solo un empleado} {--todos : Incluir pagos confirmados}';

    protected $description = 'Valida que los pagos de planilla coincidan con el cálculo canónico';

    public function handle(PlanillaCalculoService $calculoService, EmpleadoService $empleadoService): int
    {
        $query = PlanillaPago::query()->with(['empleado', 'sueldoAplicado']);
        if (! $this->option('todos')) {
            $query->where('estado', 'pendiente');
        }
        if ($this->option('empleado_id')) {
            $query->where('empleado_id', (int) $this->option('empleado_id'));
        }

        $errores = [];
        foreach ($query->orderBy('anio')->orderBy('mes')->orderBy('empleado_id')->get() as $pago) {
            $sueldo = $empleadoService->sueldoParaPeriodo($pago->empleado_id, $pago->mes, $pago->anio);
            if (! $pago->empleado || ! $sueldo) {
                $errores[] = [$pago->id, $pago->empleado?->nombre ?? 'Sin empleado', "{$pago->mes}/{$pago->anio}", 'Sin sueldo vigente'];

                continue;
            }

            $esperado = $calculoService->calcular(
                $pago->empleado,
                $sueldo,
                (int) $pago->mes,
                (int) $pago->anio,
                (float) $pago->horas_extras,
                (float) $pago->dias_faltados,
                (float) ($pago->cts_sueldo_real ?? 0)
            );
            $diferencias = [];
            $this->comparar($diferencias, 'versión', (float) $pago->empleado_sueldo_id, (float) $sueldo->id);
            $this->comparar($diferencias, 'disponible', (float) $pago->sueldo_base, $esperado['disponible']);
            $this->comparar($diferencias, 'adelantos', (float) $pago->adelantos, $esperado['adelantos']);
            $this->comparar($diferencias, 'descuento', (float) $pago->descuento_faltas, $esperado['descuento_faltas']);
            $this->comparar($diferencias, 'total', (float) $pago->total_pagar, $esperado['total_pagar']);
            $caja = round((float) $pago->importe_p + (float) $pago->importe_d + (float) $pago->importe_c, 2);
            $this->comparar($diferencias, 'caja', $caja, (float) $pago->total_pagar);

            if ($diferencias !== []) {
                $errores[] = [
                    $pago->id,
                    $pago->empleado->nombre,
                    sprintf('%02d/%d', $pago->mes, $pago->anio),
                    implode('; ', $diferencias),
                ];
            }
        }

        if ($errores === []) {
            $this->info('Kardex de pagos de planilla válido: cero discrepancias.');

            return self::SUCCESS;
        }

        $this->table(['Pago', 'Empleado', 'Período', 'Discrepancias'], $errores);
        $this->error(count($errores).' pago(s) con discrepancias.');

        return self::FAILURE;
    }

    private function comparar(array &$diferencias, string $campo, float $actual, float $esperado): void
    {
        if (abs($actual - $esperado) > 0.009) {
            $diferencias[] = sprintf('%s %.2f→%.2f', $campo, $actual, $esperado);
        }
    }
}
