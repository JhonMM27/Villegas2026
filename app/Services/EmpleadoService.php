<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Empleado;
use Illuminate\Database\Eloquent\Collection;

class EmpleadoService
{
    public function getAll(): Collection
    {
        return Empleado::orderBy('nombre')->get();
    }

    public function getActivos(): Collection
    {
        return Empleado::activos()->orderBy('nombre')->get();
    }

    public function findById(int $id): ?Empleado
    {
        return Empleado::find($id);
    }

    public function create(array $data): Empleado
    {
        return Empleado::create($data);
    }

    public function update(Empleado $empleado, array $data): bool
    {
        $oldSueldoReal = (float) $empleado->sueldo_real;
        $oldSueldoPlanilla = (float) $empleado->sueldo_planilla;

        $result = $empleado->update($data);

        if ($result) {
            $sueldoRealChanged = isset($data['sueldo_real']) && (float) $data['sueldo_real'] !== $oldSueldoReal;
            $sueldoPlanillaChanged = isset($data['sueldo_planilla']) && (float) $data['sueldo_planilla'] !== $oldSueldoPlanilla;

            if ($sueldoRealChanged || $sueldoPlanillaChanged) {
                $pagoService = app(\App\Services\PlanillaPagoService::class);
                $pagoService->recalcularPagosDelEmpleado($empleado->id);
            }
        }

        return $result;
    }

    public function delete(Empleado $empleado): bool
    {
        return $empleado->delete();
    }

    public function buscar(string $termino): Collection
    {
        return Empleado::buscar($termino)
            ->activos()
            ->limit(20)
            ->get();
    }

    public function calcularDisponible(int $empleadoId, ?int $mes = null, ?int $anio = null): float
    {
        $empleado = $this->findById($empleadoId);
        if (!$empleado) {
            return 0.0;
        }

        $sueldoReal = (float) $empleado->sueldo_real;
        $sueldoPlanilla = (float) $empleado->sueldo_planilla;
        $baseDisponible = $sueldoReal - $sueldoPlanilla;

        $query = $empleado->adelantos();
        
        if ($mes && $anio) {
            $query->delMes($mes, $anio);
        } else {
            $query->whereYear('fecha', now()->year)
                  ->whereMonth('fecha', now()->month);
        }

        $totalAdelantos = (float) $query->sum('monto');

        return max(0, $baseDisponible - $totalAdelantos);
    }

    
}
