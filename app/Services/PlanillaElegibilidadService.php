<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Empleado;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PlanillaElegibilidadService
{
    public function empleadosElegibles(int $mes, int $anio): Collection
    {
        return $this->consultaEmpleadosElegibles($mes, $anio)
            ->orderBy('nombre')
            ->get();
    }

    public function idsEmpleadosElegibles(int $mes, int $anio): array
    {
        return $this->consultaEmpleadosElegibles($mes, $anio)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    public function esElegible(Empleado $empleado, int $mes, int $anio): bool
    {
        $inicio = CarbonImmutable::create($anio, $mes, 1)->startOfMonth();
        $fin = $inicio->endOfMonth();

        return $empleado->estado === 'activo'
            && (! $empleado->fecha_ingreso || $empleado->fecha_ingreso->lte($fin))
            && (! $empleado->fecha_salida || $empleado->fecha_salida->gte($inicio));
    }

    private function consultaEmpleadosElegibles(int $mes, int $anio): Builder
    {
        $inicio = CarbonImmutable::create($anio, $mes, 1)->startOfMonth()->toDateString();
        $fin = CarbonImmutable::create($anio, $mes, 1)->endOfMonth()->toDateString();

        return Empleado::query()
            ->where('estado', 'activo')
            ->where(function (Builder $query) use ($fin): void {
                $query->whereNull('fecha_ingreso')
                    ->orWhereDate('fecha_ingreso', '<=', $fin);
            })
            ->where(function (Builder $query) use ($inicio): void {
                $query->whereNull('fecha_salida')
                    ->orWhereDate('fecha_salida', '>=', $inicio);
            });
    }
}
