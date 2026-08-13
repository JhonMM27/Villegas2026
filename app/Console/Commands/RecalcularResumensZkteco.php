<?php

namespace App\Console\Commands;

use App\Models\ZktecoAttLog;
use App\Services\Zkteco\AttendanceSummaryService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RecalcularResumensZkteco extends Command
{
    protected $signature = 'zkteco:recalcular-resumenes
        {--desde= : Fecha desde (Y-m-d), default: hace 90 días}
        {--hasta= : Fecha hasta (Y-m-d), default: hoy}
        {--device= : Filtrar por device_sn específico}';

    protected $description = 'Recalcula los resúmenes de asistencia ZKTeco (zkteco_asistencia_resumenes) para todos los device/pin/fecha con marcaciones.';

    public function handle(AttendanceSummaryService $summary): int
    {
        $desde = $this->option('desde') ?? now()->subDays(90)->toDateString();
        $hasta = $this->option('hasta') ?? now()->toDateString();
        $deviceFilter = $this->option('device');

        $this->info("Recalculando resúmenes desde {$desde} hasta {$hasta}".($deviceFilter ? " (device: {$deviceFilter})" : ''));

        $query = ZktecoAttLog::query()
            ->select('device_sn', 'pin', 'punch_time')
            ->whereBetween('punch_time', [$desde.' 00:00:00', $hasta.' 23:59:59']);

        if ($deviceFilter) {
            $query->where('device_sn', $deviceFilter);
        }

        $punches = $query->get();

        $combinaciones = $punches->groupBy(fn ($p) => $p->device_sn.'|'.$p->pin)
            ->map(fn ($group) => $group->map(fn ($p) => Carbon::parse($p->punch_time)->toDateString())->unique()->values());

        $total = $combinaciones->count();
        $procesados = 0;
        $errores = 0;

        $this->output->progressStart($total);

        foreach ($combinaciones as $key => $fechas) {
            [$deviceSn, $pin] = explode('|', $key);
            foreach ($fechas as $fecha) {
                try {
                    $summary->recalculate($deviceSn, $pin, $fecha);
                    $procesados++;
                } catch (\Throwable $e) {
                    $errores++;
                    $this->error("\nError recalculando {$deviceSn}/{$pin}/{$fecha}: ".$e->getMessage());
                }
            }
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->info("Procesados: {$procesados} | Errores: {$errores}");

        return self::SUCCESS;
    }
}
