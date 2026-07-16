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
        $oldFechaIngreso = $empleado->fecha_ingreso?->format('Y-m-d');
        $oldFechaSalida = $empleado->fecha_salida?->format('Y-m-d');

        $result = $empleado->update($data);

        if ($result) {
            $sueldoRealChanged = isset($data['sueldo_real']) && (float) $data['sueldo_real'] !== $oldSueldoReal;
            $sueldoPlanillaChanged = isset($data['sueldo_planilla']) && (float) $data['sueldo_planilla'] !== $oldSueldoPlanilla;
            $fechaIngresoChanged = array_key_exists('fecha_ingreso', $data) && $oldFechaIngreso !== $data['fecha_ingreso'];
            $fechaSalidaChanged = array_key_exists('fecha_salida', $data) && $oldFechaSalida !== $data['fecha_salida'];

            if ($sueldoRealChanged || $sueldoPlanillaChanged || $fechaIngresoChanged || $fechaSalidaChanged) {
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
        if (! $empleado) {
            return 0.0;
        }

        $sueldoReal = (float) $empleado->sueldo_real;
        $sueldoPlanilla = (float) $empleado->sueldo_planilla;
        $baseDisponible = $sueldoReal - $sueldoPlanilla;

        if ($mes && $anio) {
            $fechaIngreso = $empleado->fecha_ingreso;
            $fechaSalida = $empleado->fecha_salida;

            $ingresoEnMes = $fechaIngreso && $fechaIngreso->year === $anio && $fechaIngreso->month === $mes;
            $salidaEnMes = $fechaSalida && $fechaSalida->year === $anio && $fechaSalida->month === $mes;

            if ($salidaEnMes) {
                if ($ingresoEnMes) {
                    $diasTrabajados = $fechaSalida->day - $fechaIngreso->day + 1;
                } else {
                    $diasTrabajados = $fechaSalida->day;
                }
                $factor = $diasTrabajados / 30;
                $baseDisponible = $sueldoReal * $factor;
            } elseif ($ingresoEnMes && $fechaIngreso->day > 1) {
                $diasTrabajados = 30 - $fechaIngreso->day + 1;
                $factor = $diasTrabajados / 30;
                $baseDisponible = $sueldoReal * $factor;
            }
        }

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
