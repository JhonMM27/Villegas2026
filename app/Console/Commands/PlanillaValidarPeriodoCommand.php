<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlanillaPago;
use App\Services\PlanillaElegibilidadService;
use Illuminate\Console\Command;

class PlanillaValidarPeriodoCommand extends Command
{
    protected $signature = 'planilla:validar-periodo {mes : Mes 1-12} {anio : Año}';

    protected $description = 'Detecta empleados activos sin pago y pagos fuera de la nómina elegible';

    public function handle(PlanillaElegibilidadService $elegibilidadService): int
    {
        $mes = (int) $this->argument('mes');
        $anio = (int) $this->argument('anio');
        if ($mes < 1 || $mes > 12 || $anio < 2026) {
            $this->error('Período inválido.');

            return self::FAILURE;
        }

        $empleados = $elegibilidadService->empleadosElegibles($mes, $anio);
        $pagos = PlanillaPago::query()->with('empleado')->delMes($mes, $anio)->get();
        $idsElegibles = $empleados->modelKeys();
        $idsConPago = $pagos->where('estado', '!=', 'anulado')->pluck('empleado_id')->all();
        $errores = [];

        foreach ($empleados->whereNotIn('id', $idsConPago) as $empleado) {
            $errores[] = ['SIN_PAGO', $empleado->nombre, 'Empleado elegible sin pago generado'];
        }
        foreach ($pagos->where('estado', '!=', 'anulado')->whereNotIn('empleado_id', $idsElegibles) as $pago) {
            $errores[] = ['FUERA_NOMINA', $pago->empleado?->nombre ?? "Empleado #{$pago->empleado_id}", "Pago #{$pago->id} en estado {$pago->estado}"];
        }
        foreach ($pagos->where('estado', 'pagado')->whereNull('fecha_pago') as $pago) {
            $errores[] = ['SIN_FECHA', $pago->empleado?->nombre ?? "Empleado #{$pago->empleado_id}", "Pago confirmado #{$pago->id} sin fecha"];
        }
        foreach ($pagos->where('estado', 'pendiente')->whereNotNull('fecha_pago') as $pago) {
            $errores[] = ['FECHA_PENDIENTE', $pago->empleado?->nombre ?? "Empleado #{$pago->empleado_id}", "Pago pendiente #{$pago->id} con fecha efectiva"];
        }

        if ($errores === []) {
            $this->info(sprintf('Período %02d/%d válido: %d empleados elegibles y cero discrepancias.', $mes, $anio, $empleados->count()));

            return self::SUCCESS;
        }

        $this->table(['Tipo', 'Empleado', 'Detalle'], $errores);
        $this->error(count($errores).' discrepancia(s) encontrada(s).');

        return self::FAILURE;
    }
}
