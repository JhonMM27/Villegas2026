<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlanillaAdelanto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlanillaAdelantoService
{
    public function __construct(
        protected EmpleadoService $empleadoService
    ) {}

    public function getAll(): Collection
    {
        return PlanillaAdelanto::with('empleado')
            ->orderByDesc('fecha')
            ->get();
    }

    public function getByEmpleado(int $empleadoId): Collection
    {
        return PlanillaAdelanto::where('empleado_id', $empleadoId)
            ->orderByDesc('fecha')
            ->get();
    }

    public function findById(int $id): ?PlanillaAdelanto
    {
        return PlanillaAdelanto::with('empleado')->find($id);
    }

    public function create(array $data): PlanillaAdelanto
    {
        return DB::transaction(function () use ($data) {
            $monto = (float) $data['monto'];
            $disponible = $this->empleadoService->calcularDisponible(
                (int) $data['empleado_id'],
                (int) date('m', strtotime($data['fecha'])),
                (int) date('Y', strtotime($data['fecha']))
            );

            if ($monto > $disponible) {
                throw new \Exception("El monto excede el disponible ({$disponible})");
            }

            return PlanillaAdelanto::create([
                'empleado_id' => $data['empleado_id'],
                'monto' => $data['monto'],
                'fecha' => $data['fecha'],
                'planilla_pago_id' => $data['planilla_pago_id'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'importe_p' => $data['principal'] ?? 0,
                'importe_d' => $data['deposito'] ?? 0,
                'importe_c' => $data['consorcio'] ?? 0,
            ]);
        });
    }

    public function update(PlanillaAdelanto $adelanto, array $data): bool
    {
        return $adelanto->update($data);
    }

    public function delete(PlanillaAdelanto $adelanto): bool
    {
        return $adelanto->delete();
    }

    public function getTotalAdelantosMes(int $empleadoId, int $mes, int $anio): float
    {
        return (float) PlanillaAdelanto::where('empleado_id', $empleadoId)
            ->delMes($mes, $anio)
            ->sum('monto');
    }
}
