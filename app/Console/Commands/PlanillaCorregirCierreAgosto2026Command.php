<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Empleado;
use App\Models\PlanillaPago;
use App\Services\PlanillaPagoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PlanillaCorregirCierreAgosto2026Command extends Command
{
    protected $signature = 'planilla:corregir-cierre-agosto-2026 {--dry-run : Validar y mostrar sin modificar}';

    protected $description = 'Restaura la fecha de Víctor Salazar y anula el pago improcedente de René Villegas';

    public function handle(PlanillaPagoService $pagoService): int
    {
        try {
            [$victorPago, $renePago] = $this->obtenerCasos();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['Empleado', 'Pago', 'Estado actual', 'Corrección'], [
            ['VICTOR KEVIN SALAZAR ASPAJO', $victorPago->id, $victorPago->estado.' / '.($victorPago->fecha_pago?->format('d/m/Y') ?? 'sin fecha'), 'Fecha efectiva y original: 18/08/2026'],
            ['RENE VILLEGAS GUEVARA', $renePago->id, $renePago->estado, 'Anular pago de agosto y excluirlo de caja'],
        ]);

        if ($this->option('dry-run')) {
            $this->info('Dry-run correcto. No se modificaron datos.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($pagoService, $victorPago, $renePago): void {
            if ($victorPago->fecha_pago?->toDateString() !== '2026-08-18'
                || $victorPago->fecha_pago_original?->toDateString() !== '2026-08-18') {
                $pagoService->rectificarFechaPago(
                    $victorPago,
                    '2026-08-18',
                    'Restauración de la fecha original después de una reversión masiva'
                );
            }

            if ($renePago->estado !== 'anulado') {
                $pagoService->anularPago(
                    $renePago,
                    'Pago improcedente de agosto: el empleado salió el 30/07/2026'
                );
            }
        });

        $this->info('Corrección aplicada. Víctor conserva 18/08/2026 y René quedó excluido de caja.');

        return self::SUCCESS;
    }

    private function obtenerCasos(): array
    {
        $victor = Empleado::query()->where('dni', '71837739')->first();
        $rene = Empleado::query()->where('dni', '48670033')->first();

        if (! $victor || $victor->nombre !== 'VICTOR KEVIN SALAZAR ASPAJO') {
            throw new RuntimeException('No se encontró a Víctor Kevin Salazar Aspajo con DNI 71837739.');
        }
        if (! $rene || $rene->nombre !== 'RENE VILLEGAS GUEVARA') {
            throw new RuntimeException('No se encontró a René Villegas Guevara con DNI 48670033.');
        }
        if ($rene->estado !== 'inactivo' || $rene->fecha_salida?->toDateString() !== '2026-07-30') {
            throw new RuntimeException('René no conserva las precondiciones esperadas: inactivo y salida 30/07/2026.');
        }

        $victorPago = PlanillaPago::query()->where('empleado_id', $victor->id)->delMes(8, 2026)->first();
        $renePago = PlanillaPago::query()->where('empleado_id', $rene->id)->delMes(8, 2026)->first();

        if (! $victorPago || (float) $victorPago->total_pagar !== 840.0 || $victorPago->estado !== 'pagado') {
            throw new RuntimeException('El pago de agosto de Víctor no coincide con el caso auditado (pagado por S/ 840).');
        }
        if (! $renePago || (float) $renePago->total_pagar !== 2000.0 || ! in_array($renePago->estado, ['pagado', 'anulado'], true)) {
            throw new RuntimeException('El pago de agosto de René no coincide con el caso auditado (S/ 2,000).');
        }

        return [$victorPago, $renePago];
    }
}
