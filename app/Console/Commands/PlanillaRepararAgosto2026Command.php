<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Empleado;
use App\Models\PlanillaPago;
use App\Services\EmpleadoSueldoService;
use App\Services\PlanillaPagoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PlanillaRepararAgosto2026Command extends Command
{
    protected $signature = 'planilla:reparar-agosto-2026 {--dry-run : Validar y mostrar cambios sin escribir}';

    protected $description = 'Rectifica los pagos pendientes de Kevin, Augusto y valida el caso de Ercli en agosto de 2026';

    public function handle(EmpleadoSueldoService $sueldoService, PlanillaPagoService $pagoService): int
    {
        try {
            $casos = $this->validarPrecondiciones();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->yaEstaCorregido()) {
            $this->info('Los tres casos ya están corregidos; no hay cambios por aplicar.');

            return self::SUCCESS;
        }

        $this->table(
            ['Empleado', 'Pago', 'Acción'],
            [
                [$casos['kevin']->nombre, 99, 'Agosto: real 1900, planilla 0; total esperado 1100'],
                [$casos['augusto']->nombre, 112, 'Agosto: real 1900, planilla 1130; total esperado 706.67'],
                [$casos['ercli']->nombre, 119, 'Sin modificar; total confirmado 1680'],
            ]
        );

        if ($this->option('dry-run')) {
            $this->info('Dry-run correcto. No se modificaron datos.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($casos, $sueldoService, $pagoService): void {
            $sueldoService->rectificarMes($casos['kevin'], 8, 2026, [
                'sueldo_real' => 1900,
                'sueldo_planilla' => 0,
                'motivo' => 'Corrección de aumento erróneo de agosto 2026',
                'observaciones_sueldo' => 'Rectificación controlada del incidente reportado el 31/08/2026',
            ]);
            $sueldoService->rectificarMes($casos['augusto'], 8, 2026, [
                'sueldo_real' => 1900,
                'sueldo_planilla' => 1130,
                'motivo' => 'Corrección del sueldo no planilla de agosto 2026',
                'observaciones_sueldo' => 'Sueldo no planilla derivado: 1900 - 1130 = 770',
            ]);

            // Normaliza todos los pendientes; no toca pagos confirmados ni gastos.
            foreach (PlanillaPago::query()->where('estado', 'pendiente')->get() as $pago) {
                $pagoService->recalcularPago($pago);
            }
        });

        $this->info('Reparación aplicada correctamente.');

        return self::SUCCESS;
    }

    private function validarPrecondiciones(): array
    {
        $empleados = [];
        foreach ([
            'kevin' => ['dni' => '72985795', 'nombre' => 'KEVIN IPANAQUE VASQUEZ', 'pago' => 99, 'total' => 1100.0],
            'augusto' => ['dni' => '71173143', 'nombre' => 'AUGUSTO LOPEZ JIMENEZ', 'pago' => 112, 'total' => 1836.67],
            'ercli' => ['dni' => '60155210', 'nombre' => 'ERCLI RENAN CUSMA IRIGOIN', 'pago' => 119, 'total' => 1680.0],
        ] as $clave => $esperado) {
            $empleado = Empleado::query()->where('dni', $esperado['dni'])->first();
            if (! $empleado || $empleado->nombre !== $esperado['nombre']) {
                throw new RuntimeException("Precondición fallida para {$esperado['nombre']}.");
            }
            $pago = PlanillaPago::query()->find($esperado['pago']);
            if (! $pago || $pago->empleado_id !== $empleado->id || $pago->mes !== 8 || $pago->anio !== 2026 || $pago->estado !== 'pendiente') {
                throw new RuntimeException("El pago {$esperado['pago']} ya no coincide con el caso esperado.");
            }
            $totalesPermitidos = $clave === 'augusto' ? [1836.67, 706.67] : [$esperado['total']];
            if (collect($totalesPermitidos)->every(fn (float $total): bool => abs((float) $pago->total_pagar - $total) > 0.009)) {
                throw new RuntimeException("El pago {$esperado['pago']} cambió desde la auditoría inicial.");
            }
            $empleados[$clave] = $empleado;
        }

        return $empleados;
    }

    private function yaEstaCorregido(): bool
    {
        $kevin = PlanillaPago::query()->with('sueldoAplicado')->find(99);
        $augusto = PlanillaPago::query()->with('sueldoAplicado')->find(112);
        $ercli = PlanillaPago::query()->find(119);

        return $kevin
            && (float) $kevin->sueldoAplicado?->sueldo_real === 1900.0
            && (float) $kevin->total_pagar === 1100.0
            && $augusto
            && (float) $augusto->sueldoAplicado?->sueldo_real === 1900.0
            && (float) $augusto->sueldoAplicado?->sueldo_planilla === 1130.0
            && (float) $augusto->sueldoAplicado?->sueldo_base === 770.0
            && (float) $augusto->total_pagar === 706.67
            && $ercli
            && (float) $ercli->total_pagar === 1680.0;
    }
}
