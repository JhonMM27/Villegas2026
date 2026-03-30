<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlanillaPago;
use App\Models\PlanillaPagoDetalle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlanillaPagoService
{
    public function __construct(
        protected EmpleadoService $empleadoService,
        protected PlanillaAdelantoService $adelantoService
    ) {}

    public function getAll(): Collection
    {
        return PlanillaPago::with('empleado')
            ->orderByDesc('id')
            ->get();
    }

    public function getByMes(int $mes, int $anio): Collection
    {
        return PlanillaPago::with('empleado')
            ->delMes($mes, $anio)
            ->get();
    }

    public function getByEmpleado(int $empleadoId): Collection
    {
        return PlanillaPago::where('empleado_id', $empleadoId)
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get();
    }

    public function findById(int $id): ?PlanillaPago
    {
        return PlanillaPago::with(['empleado', 'detalles', 'adelantos'])->find($id);
    }

    public function existePagoMes(int $empleadoId, int $mes, int $anio): bool
    {
        return PlanillaPago::where('empleado_id', $empleadoId)
            ->delMes($mes, $anio)
            ->exists();
    }

    public function procesarPago(array $data): PlanillaPago
    {
        return DB::transaction(function () use ($data) {
            $empleado = $this->empleadoService->findById((int) $data['empleado_id']);
            if (! $empleado) {
                throw new \Exception('Empleado no encontrado');
            }

            $disponible = $this->empleadoService->calcularDisponible(
                (int) $data['empleado_id'],
                (int) $data['mes'],
                (int) $data['anio']
            );

            $horasExtras = (float) ($data['horas_extras'] ?? 0);
            $totalPagar = $disponible + $horasExtras;

            $pago = PlanillaPago::create([
                'empleado_id' => (int) $data['empleado_id'],
                'mes' => (int) $data['mes'],
                'anio' => (int) $data['anio'],
                'sueldo_base' => $disponible,
                'horas_extras' => $horasExtras,
                'adelantos' => 0,
                'total_pagar' => $totalPagar,
                'estado' => 'pendiente',
                'observaciones' => $data['observaciones'] ?? null,
                'importe_p' => $data['principal'] ?? 0,
                'importe_d' => $data['deposito'] ?? 0,
                'importe_c' => $data['consorcio'] ?? 0,
            ]);

            if ($horasExtras > 0) {
                PlanillaPagoDetalle::create([
                    'planilla_pago_id' => $pago->id,
                    'concepto' => 'Horas Extras',
                    'monto' => $horasExtras,
                    'tipo' => 'ingreso',
                ]);
            }

            PlanillaPagoDetalle::create([
                'planilla_pago_id' => $pago->id,
                'concepto' => 'Disponible (Sueldo Real - Planilla)',
                'monto' => $disponible,
                'tipo' => 'ingreso',
            ]);

            return $pago;
        });
    }

    public function marcarPagado(PlanillaPago $pago): bool
    {
        return DB::transaction(function () use ($pago) {
            $pago->marcarComoPagado();

            return true;
        });
    }

    public function update(PlanillaPago $pago, array $data): bool
    {
        return DB::transaction(function () use ($pago, $data) {
            $horasExtras = (float) ($data['horas_extras'] ?? 0);
            $totalPagar = (float) $pago->sueldo_base + $horasExtras;

            $pago->horas_extras = $horasExtras;
            $pago->total_pagar = $totalPagar;
            $pago->observaciones = $data['observaciones'] ?? $pago->observaciones;

            return $pago->save();
        });
    }

    public function getResumenMensual(int $mes, int $anio): array
    {
        $pagos = $this->getByMes($mes, $anio);

        return [
            'total_pagar' => $pagos->sum('total_pagar'),
            'total_horas_extras' => $pagos->sum('horas_extras'),
            'total_adelantos' => $pagos->sum('adelantos'),
            'cantidad_empleados' => $pagos->count(),
            'pendientes' => $pagos->where('estado', 'pendiente')->count(),
            'pagados' => $pagos->where('estado', 'pagado')->count(),
        ];
    }
}
