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
    public function __construct(
        protected PlanillaCalculoService $calculoService,
        protected AuditoriaService $auditoriaService
    ) {}

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

    public function rectificarMes(Empleado $empleado, int $mes, int $anio, array $data): EmpleadoSueldo
    {
        return DB::transaction(function () use ($empleado, $mes, $anio, $data): EmpleadoSueldo {
            $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
            $fin = $inicio->copy()->endOfMonth();
            $versiones = $empleado->sueldos()
                ->with('empleado')
                ->lockForUpdate()
                ->orderBy('vigente_desde')
                ->orderBy('id')
                ->get();
            $pagosMes = $empleado->pagos()
                ->where('mes', $mes)
                ->where('anio', $anio)
                ->lockForUpdate()
                ->get();

            if ($pagosMes->contains(fn ($pago): bool => $pago->estado === 'pagado')) {
                throw new InvalidArgumentException('No se puede rectificar el sueldo porque el período tiene pagos confirmados. Reviértalos primero.');
            }

            $versionesDelMes = $versiones->filter(fn (EmpleadoSueldo $version): bool => $version->vigente_desde->betweenIncluded($inicio, $fin)
            );
            $vigenteAlFin = $versiones->filter(fn (EmpleadoSueldo $version): bool => $version->vigente_desde->lte($fin)
                && (! $version->vigente_hasta || $version->vigente_hasta->gte($fin))
            )->sortByDesc('vigente_desde')->first();
            $candidatas = $versionesDelMes->isNotEmpty()
                ? $versionesDelMes
                : collect(array_filter([$vigenteAlFin]));
            $idsReferenciados = $pagosMes->pluck('empleado_sueldo_id')->filter()->map(fn ($id) => (int) $id);
            $principal = $candidatas->first(fn (EmpleadoSueldo $version): bool => $idsReferenciados->contains($version->id))
                ?? $candidatas->sortByDesc('vigente_desde')->first();

            if (! $principal) {
                throw new InvalidArgumentException('No existe una versión salarial para el período seleccionado.');
            }

            $idsAfectados = $candidatas->pluck('id');
            $tienePagados = $empleado->pagos()
                ->whereIn('empleado_sueldo_id', $idsAfectados)
                ->where('estado', 'pagado')
                ->where(function ($query) use ($anio, $mes): void {
                    $query->where('anio', '>', $anio)
                        ->orWhere(fn ($q) => $q->where('anio', $anio)->where('mes', '>=', $mes));
                })
                ->exists();
            if ($tienePagados) {
                throw new InvalidArgumentException('La versión salarial también está vinculada a pagos confirmados. Reviértalos antes de rectificar.');
            }

            $datos = $this->normalizarDatos($data + ['vigente_desde' => $inicio->toDateString()]);
            $anteriores = [];
            foreach ($candidatas as $version) {
                $anteriores[$version->id] = $this->auditoriaService->capturar('empleado_sueldos', $version);
                $version->update([
                    'sueldo_base' => $datos['sueldo_base'],
                    'sueldo_real' => $datos['sueldo_real'],
                    'sueldo_planilla' => $datos['sueldo_planilla'],
                    'motivo' => $data['motivo'],
                    'observaciones' => $data['observaciones_sueldo'] ?? $version->observaciones,
                    'registrado_por' => auth()->id(),
                ]);
            }

            $anterior = $versiones
                ->filter(fn (EmpleadoSueldo $version): bool => $version->vigente_desde->lt($inicio) && ! $idsAfectados->contains($version->id))
                ->sortByDesc('vigente_desde')
                ->first();
            if ($anterior && (! $anterior->vigente_hasta || $anterior->vigente_hasta->gte($inicio))) {
                $anterior->update(['vigente_hasta' => $inicio->copy()->subDay()->toDateString()]);
            }

            $yaExisteInicio = $candidatas->first(fn (EmpleadoSueldo $version): bool => $version->vigente_desde->isSameDay($inicio));
            if (! $yaExisteInicio) {
                $principal->update(['vigente_desde' => $inicio->toDateString()]);
            }

            foreach ($candidatas as $version) {
                $version->refresh()->load('empleado');
                $this->auditoriaService->registrarRectificacion(
                    'empleado_sueldos',
                    $version,
                    1,
                    $anteriores[$version->id],
                    $this->auditoriaService->capturar('empleado_sueldos', $version),
                    $data['motivo']
                );
            }

            $pagoService = app(PlanillaPagoService::class);
            $pagoService->recalcularPagosDelEmpleado($empleado->id);

            return $principal->refresh();
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
        $sueldoReal = (float) $data['sueldo_real'];
        $sueldoPlanilla = (float) $data['sueldo_planilla'];
        $sueldoBase = $this->calculoService->sueldoNoPlanilla($sueldoReal, $sueldoPlanilla);

        return (float) $sueldo->sueldo_base === $sueldoBase
            && (float) $sueldo->sueldo_real === $sueldoReal
            && (float) $sueldo->sueldo_planilla === (float) $data['sueldo_planilla'];
    }

    private function normalizarDatos(array $data): array
    {
        $sueldoReal = round((float) $data['sueldo_real'], 2);
        $sueldoPlanilla = round((float) $data['sueldo_planilla'], 2);
        if ($sueldoPlanilla > $sueldoReal) {
            throw new InvalidArgumentException('El sueldo de planilla no puede ser mayor que el sueldo real.');
        }

        return [
            'sueldo_base' => $this->calculoService->sueldoNoPlanilla($sueldoReal, $sueldoPlanilla),
            'sueldo_real' => $sueldoReal,
            'sueldo_planilla' => $sueldoPlanilla,
            'vigente_desde' => Carbon::parse($data['vigente_desde'])->startOfMonth()->toDateString(),
            'vigente_hasta' => null,
            'motivo' => $data['motivo'] ?? null,
            'observaciones' => $data['observaciones_sueldo'] ?? null,
        ];
    }
}
