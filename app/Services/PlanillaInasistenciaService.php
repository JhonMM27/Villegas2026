<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlanillaInasistencia;
use App\Models\PlanillaPago;
use Illuminate\Database\Eloquent\Collection;

class PlanillaInasistenciaService
{
    public function __construct(
        protected PlanillaPagoService $pagoService,
        protected PlanillaAsistenciaService $asistenciaService
    ) {}

    public function getAll(): Collection
    {
        return PlanillaInasistencia::with('empleado')
            ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
    }

    public function getByEmpleado(int $empleadoId): Collection
    {
        return PlanillaInasistencia::where('empleado_id', $empleadoId)
            ->orderByDesc('fecha')
            ->get();
    }

    public function findById(int $id): ?PlanillaInasistencia
    {
        return PlanillaInasistencia::with('empleado')->find($id);
    }

    public function getDiasFaltadosDelMes(int $empleadoId, int $mes, int $anio): float
    {
        $inasistencias = PlanillaInasistencia::where('empleado_id', $empleadoId)
            ->delMes($mes, $anio)
            ->get();

        $dias = 0.0;
        foreach ($inasistencias as $inasistencia) {
            $dias += $inasistencia->medio_dia ? 0.5 : 1.0;
        }

        return $dias;
    }

    public function create(array $data): PlanillaInasistencia
    {
        $inasistencia = PlanillaInasistencia::create([
            'empleado_id' => (int) $data['empleado_id'],
            'fecha' => $data['fecha'],
            'medio_dia' => ! empty($data['medio_dia']),
            'observacion' => $data['observacion'] ?? null,
        ]);

        $this->recalcularPagoDelMes($inasistencia);

        return $inasistencia;
    }

    public function update(PlanillaInasistencia $inasistencia, array $data): bool
    {
        $inasistencia->fecha = $data['fecha'];
        $inasistencia->medio_dia = ! empty($data['medio_dia']);
        $inasistencia->observacion = $data['observacion'] ?? null;

        $result = $inasistencia->save();

        if ($result) {
            $this->recalcularPagoDelMes($inasistencia);
        }

        return $result;
    }

    public function delete(PlanillaInasistencia $inasistencia): bool
    {
        $this->recalcularPagoDelMes($inasistencia);

        return $inasistencia->delete();
    }

    protected function recalcularPagoDelMes(PlanillaInasistencia $inasistencia): void
    {
        $fecha = $inasistencia->fecha;
        $mes = (int) $fecha->format('n');
        $anio = (int) $fecha->format('Y');

        $this->asistenciaService->syncDiasFaltadosFromInasistencias(
            $inasistencia->empleado_id,
            $mes,
            $anio
        );

        $pago = PlanillaPago::where('empleado_id', $inasistencia->empleado_id)
            ->delMes($mes, $anio)
            ->first();

        if ($pago && $pago->estado === 'pendiente') {
            $this->pagoService->recalcularPago($pago);
        }
    }

    public function existeInasistencia(int $empleadoId, string $fecha): bool
    {
        return PlanillaInasistencia::where('empleado_id', $empleadoId)
            ->whereDate('fecha', $fecha)
            ->exists();
    }
}
