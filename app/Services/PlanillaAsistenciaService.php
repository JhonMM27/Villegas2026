<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlanillaAsistencia;
use App\Models\PlanillaInasistencia;

class PlanillaAsistenciaService
{
    public const DIAS_LABORABLES_MES = 30;

    public function getByEmpleadoMes(int $empleadoId, int $mes, int $anio): ?PlanillaAsistencia
    {
        return PlanillaAsistencia::where('empleado_id', $empleadoId)
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->first();
    }

    public function createOrUpdate(int $empleadoId, int $mes, int $anio, float $diasFaltados): PlanillaAsistencia
    {
        return PlanillaAsistencia::updateOrCreate(
            [
                'empleado_id' => $empleadoId,
                'mes' => $mes,
                'anio' => $anio,
            ],
            [
                'dias_faltados' => $diasFaltados,
            ]
        );
    }

    public function calcularDescuentoFaltas(float $sueldoReal, float $diasFaltados): float
    {
        if ($diasFaltados <= 0) {
            return 0.0;
        }

        $costoPorDia = $sueldoReal / self::DIAS_LABORABLES_MES;

        return round($diasFaltados * $costoPorDia, 2);
    }

    public function resetearFaltasMes(int $mes, int $anio): int
    {
        return PlanillaAsistencia::where('mes', $mes)
            ->where('anio', $anio)
            ->update(['dias_faltados' => 0]);
    }

    public function syncDiasFaltadosFromInasistencias(int $empleadoId, int $mes, int $anio): float
    {
        $inasistencias = PlanillaInasistencia::where('empleado_id', $empleadoId)
            ->delMes($mes, $anio)
            ->get();

        $diasFaltados = 0.0;
        foreach ($inasistencias as $inasistencia) {
            $diasFaltados += $inasistencia->medio_dia ? 0.5 : 1.0;
        }

        $this->createOrUpdate($empleadoId, $mes, $anio, $diasFaltados);

        return $diasFaltados;
    }
}
