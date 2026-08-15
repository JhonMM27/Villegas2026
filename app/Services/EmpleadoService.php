<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Empleado;
use App\Models\EmpleadoSueldo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EmpleadoService
{
    public function __construct(
        protected EmpleadoSueldoService $sueldoService
    ) {}

    public function getAll(): Collection
    {
        return Empleado::with('sueldoActual')->orderBy('nombre')->get();
    }

    public function getActivos(): Collection
    {
        return Empleado::with('sueldoActual')->activos()->orderBy('nombre')->get();
    }

    public function findById(int $id): ?Empleado
    {
        return Empleado::with('sueldoActual')->find($id);
    }

    public function create(array $data): Empleado
    {
        return DB::transaction(function () use ($data): Empleado {
            $empleado = Empleado::create(Arr::except($data, $this->camposSueldo()));
            $this->sueldoService->crearInicial($empleado, $this->datosSueldo($data));

            return $empleado->load('sueldoActual');
        });
    }

    public function update(Empleado $empleado, array $data): bool
    {
        $oldFechaIngreso = $empleado->fecha_ingreso?->format('Y-m-d');
        $oldFechaSalida = $empleado->fecha_salida?->format('Y-m-d');

        $sueldoAnterior = $empleado->sueldoActual;
        $datosSueldo = $this->datosSueldo($data);
        $sueldoChanged = ! $sueldoAnterior || ! $this->sueldoService->mismosImportes($sueldoAnterior, $datosSueldo);

        $result = DB::transaction(function () use ($empleado, $data, $datosSueldo, $sueldoChanged): bool {
            $actualizado = $empleado->update(Arr::except($data, $this->camposSueldo()));

            if ($sueldoChanged) {
                $this->sueldoService->registrarCambio($empleado, $datosSueldo);
            }

            return $actualizado;
        });

        if ($result) {
            $fechaIngresoChanged = array_key_exists('fecha_ingreso', $data) && $oldFechaIngreso !== $data['fecha_ingreso'];
            $fechaSalidaChanged = array_key_exists('fecha_salida', $data) && $oldFechaSalida !== $data['fecha_salida'];

            if ($sueldoChanged || $fechaIngresoChanged || $fechaSalidaChanged) {
                $pagoService = app(\App\Services\PlanillaPagoService::class);
                $pagoService->recalcularPagosDelEmpleado($empleado->id);
            }
        }

        return $result;
    }

    public function delete(Empleado $empleado): bool
    {
        return $empleado->update([
            'estado' => 'inactivo',
            'fecha_salida' => $empleado->fecha_salida?->toDateString() ?? now()->toDateString(),
        ]);
    }

    public function buscar(string $termino): Collection
    {
        return Empleado::buscar($termino)
            ->with('sueldoActual')
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

        $fechaSueldo = $mes && $anio
            ? now()->setDate($anio, $mes, 1)->endOfMonth()
            : now();
        $sueldo = $this->sueldoService->vigenteEn($empleado, $fechaSueldo);
        if (! $sueldo) {
            return 0.0;
        }

        $baseDisponible = (float) $sueldo->sueldo_base;

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
                $baseDisponible = (float) $sueldo->sueldo_base * $factor;
            } elseif ($ingresoEnMes && $fechaIngreso->day > 1) {
                $diasTrabajados = 30 - $fechaIngreso->day + 1;
                $factor = $diasTrabajados / 30;
                $baseDisponible = (float) $sueldo->sueldo_base * $factor;
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

    public function sueldoParaPeriodo(int $empleadoId, int $mes, int $anio): ?EmpleadoSueldo
    {
        return $this->sueldoService->vigenteEn(
            $empleadoId,
            now()->setDate($anio, $mes, 1)->endOfMonth()
        );
    }

    private function datosSueldo(array $data): array
    {
        return Arr::only($data, [
            'sueldo_base',
            'sueldo_real',
            'sueldo_planilla',
            'vigente_desde',
            'motivo',
            'observaciones_sueldo',
        ]);
    }

    private function camposSueldo(): array
    {
        return [
            'sueldo_base',
            'sueldo_real',
            'sueldo_planilla',
            'vigente_desde',
            'motivo',
            'observaciones_sueldo',
        ];
    }
}
