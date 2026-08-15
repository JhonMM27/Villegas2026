<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Empleado;
use App\Models\EmpleadoSueldo;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmpleadoSueldoService
{
    public function crearInicial(Empleado $empleado, array $data): EmpleadoSueldo
    {
        return $empleado->sueldos()->create($this->normalizarDatos($data) + [
            'registrado_por' => auth()->id(),
        ]);
    }

    public function registrarCambio(Empleado $empleado, array $data): EmpleadoSueldo
    {
        return DB::transaction(function () use ($empleado, $data): EmpleadoSueldo {
            $datos = $this->normalizarDatos($data);
            $ultimo = $empleado->sueldos()
                ->lockForUpdate()
                ->orderByDesc('vigente_desde')
                ->orderByDesc('id')
                ->first();

            if (! $ultimo) {
                return $this->crearInicial($empleado, $datos);
            }

            if ($this->mismosImportes($ultimo, $datos)) {
                return $ultimo;
            }

            $vigenteDesde = Carbon::parse($datos['vigente_desde'])->startOfDay();
            if ($vigenteDesde->lessThanOrEqualTo($ultimo->vigente_desde)) {
                throw new InvalidArgumentException('La nueva vigencia debe ser posterior al último cambio salarial ('.$ultimo->vigente_desde->format('d/m/Y').').');
            }

            $ultimo->update([
                'vigente_hasta' => $vigenteDesde->copy()->subDay()->toDateString(),
            ]);

            return $empleado->sueldos()->create($datos + [
                'registrado_por' => auth()->id(),
            ]);
        });
    }

    public function vigenteEn(Empleado|int $empleado, CarbonInterface|string $fecha): ?EmpleadoSueldo
    {
        $empleadoId = $empleado instanceof Empleado ? $empleado->id : $empleado;
        $fechaConsulta = Carbon::parse($fecha)->toDateString();

        return EmpleadoSueldo::query()
            ->where('empleado_id', $empleadoId)
            ->whereDate('vigente_desde', '<=', $fechaConsulta)
            ->where(function ($query) use ($fechaConsulta): void {
                $query->whereNull('vigente_hasta')
                    ->orWhereDate('vigente_hasta', '>=', $fechaConsulta);
            })
            ->orderByDesc('vigente_desde')
            ->orderByDesc('id')
            ->first();
    }

    public function historial(int $empleadoId, ?string $desde = null, ?string $hasta = null): Collection
    {
        return EmpleadoSueldo::query()
            ->with('registradoPor:id,name')
            ->where('empleado_id', $empleadoId)
            ->when($desde, fn ($query) => $query->where(function ($q) use ($desde): void {
                $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $desde);
            }))
            ->when($hasta, fn ($query) => $query->whereDate('vigente_desde', '<=', $hasta))
            ->orderBy('vigente_desde')
            ->orderBy('id')
            ->get();
    }

    public function mismosImportes(EmpleadoSueldo $sueldo, array $data): bool
    {
        return (float) $sueldo->sueldo_base === (float) $data['sueldo_base']
            && (float) $sueldo->sueldo_real === (float) $data['sueldo_real']
            && (float) $sueldo->sueldo_planilla === (float) $data['sueldo_planilla'];
    }

    private function normalizarDatos(array $data): array
    {
        return [
            'sueldo_base' => round((float) $data['sueldo_base'], 2),
            'sueldo_real' => round((float) $data['sueldo_real'], 2),
            'sueldo_planilla' => round((float) $data['sueldo_planilla'], 2),
            'vigente_desde' => Carbon::parse($data['vigente_desde'])->toDateString(),
            'vigente_hasta' => null,
            'motivo' => $data['motivo'] ?? null,
            'observaciones' => $data['observaciones_sueldo'] ?? null,
        ];
    }
}
