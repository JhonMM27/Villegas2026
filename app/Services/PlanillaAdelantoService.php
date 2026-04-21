<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlanillaAdelanto;
use App\Models\PlanillaPago;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlanillaAdelantoService
{
    public function __construct(
        protected EmpleadoService $empleadoService,
        protected PlanillaPagoService $pagoService
    ) {}

    public function getAll(): Collection
    {
        return PlanillaAdelanto::with('empleado')
            ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'))
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
            $empleadoId = (int) $data['empleado_id'];
            $mes = (int) date('m', strtotime($data['fecha']));
            $anio = (int) date('Y', strtotime($data['fecha']));

            $monto = (float) $data['monto'];
            $disponible = $this->empleadoService->calcularDisponible($empleadoId, $mes, $anio);

            if ($monto > $disponible) {
                throw new \Exception("El monto excede el disponible ({$disponible})");
            }

            $adelanto = PlanillaAdelanto::create([
                'empleado_id' => $empleadoId,
                'numero_interno' => $data['numero_interno'],
                'monto' => $data['monto'],
                'fecha' => $data['fecha'],
                'planilla_pago_id' => $data['planilla_pago_id'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'importe_p' => $data['principal'] ?? 0,
                'importe_d' => $data['deposito'] ?? 0,
                'importe_c' => $data['consorcio'] ?? 0,
            ]);

            $this->recalcularPagoSiExiste($empleadoId, $mes, $anio);

            return $adelanto;
        });
    }

    public function update(PlanillaAdelanto $adelanto, array $data): bool
    {
        $oldEmpleadoId = $adelanto->empleado_id;
        $oldMes = (int) $adelanto->fecha->format('m');
        $oldAnio = (int) $adelanto->fecha->format('Y');

        $result = $adelanto->update($data);

        $newEmpleadoId = (int) ($data['empleado_id'] ?? $adelanto->empleado_id);
        $newFecha = $data['fecha'] ?? $adelanto->fecha->format('Y-m-d');
        $newMes = (int) date('m', strtotime($newFecha));
        $newAnio = (int) date('Y', strtotime($newFecha));

        $this->recalcularPagoSiExiste($oldEmpleadoId, $oldMes, $oldAnio);

        if ($oldEmpleadoId !== $newEmpleadoId || $oldMes !== $newMes || $oldAnio !== $newAnio) {
            $this->recalcularPagoSiExiste($newEmpleadoId, $newMes, $newAnio);
        }

        return $result;
    }

    public function delete(PlanillaAdelanto $adelanto): bool
    {
        $empleadoId = $adelanto->empleado_id;
        $mes = (int) $adelanto->fecha->format('m');
        $anio = (int) $adelanto->fecha->format('Y');

        $result = $adelanto->delete();

        $this->recalcularPagoSiExiste($empleadoId, $mes, $anio);

        return $result;
    }

    public function getTotalAdelantosMes(int $empleadoId, int $mes, int $anio): float
    {
        return (float) PlanillaAdelanto::where('empleado_id', $empleadoId)
            ->delMes($mes, $anio)
            ->sum('monto');
    }

    private function recalcularPagoSiExiste(int $empleadoId, int $mes, int $anio): void
    {
        $pago = PlanillaPago::where('empleado_id', $empleadoId)
            ->delMes($mes, $anio)
            ->pendientes()
            ->first();

        if ($pago) {
            $this->pagoService->recalcularPago($pago);
        }
    }
}
