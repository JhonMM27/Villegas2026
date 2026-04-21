<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Empleado;
use App\Models\PlanillaAdelanto;
use App\Models\PlanillaPago;
use App\Models\PlanillaPagoDetalle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlanillaPagoService
{
    public function __construct(
        protected EmpleadoService $empleadoService,
        protected PlanillaAsistenciaService $asistenciaService
    ) {}

    public function getAll(): Collection
    {
        return PlanillaPago::with('empleado')
            ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'))
            ->orderByDesc('id')
            ->get();
    }

    public function getByMes(int $mes, int $anio): Collection
    {
        return PlanillaPago::with('empleado')
            ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'))
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
        return PlanillaPago::with(['empleado', 'detalles', 'adelantosRecords'])->find($id);
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

            $sueldoReal = (float) $empleado->sueldo_real;
            $disponible = $this->empleadoService->calcularDisponible(
                (int) $data['empleado_id'],
                (int) $data['mes'],
                (int) $data['anio']
            );

            $horasExtras = (float) ($data['horas_extras'] ?? 0);
            $this->asistenciaService->syncDiasFaltadosFromInasistencias(
                (int) $data['empleado_id'],
                (int) $data['mes'],
                (int) $data['anio']
            );
            $asistencia = $this->asistenciaService->getByEmpleadoMes(
                (int) $data['empleado_id'],
                (int) $data['mes'],
                (int) $data['anio']
            );
            $diasFaltados = $asistencia ? (float) $asistencia->dias_faltados : 0.0;
            $descuentoFaltas = $this->calcularDescuentoFaltas($sueldoReal, $diasFaltados);
            $totalPagar = $disponible + $horasExtras - $descuentoFaltas;

            $pago = PlanillaPago::create([
                'empleado_id' => (int) $data['empleado_id'],
                'mes' => (int) $data['mes'],
                'anio' => (int) $data['anio'],
                'sueldo_base' => $disponible,
                'horas_extras' => $horasExtras,
                'dias_faltados' => $diasFaltados,
                'descuento_faltas' => $descuentoFaltas,
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

            if ($descuentoFaltas > 0) {
                PlanillaPagoDetalle::create([
                    'planilla_pago_id' => $pago->id,
                    'concepto' => 'Descuento por Faltas',
                    'monto' => $descuentoFaltas,
                    'tipo' => 'descuento',
                ]);
            }

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
            $empleado = $this->empleadoService->findById($pago->empleado_id);
            $sueldoReal = $empleado ? (float) $empleado->sueldo_real : 0;

            $horasExtras = (float) ($data['horas_extras'] ?? 0);
            $diasFaltados = (float) ($data['dias_faltados'] ?? 0);
            $descuentoFaltas = $this->calcularDescuentoFaltas($sueldoReal, $diasFaltados);
            $newTotal = (float) $pago->sueldo_base + $horasExtras - $descuentoFaltas;

            $oldTotal = (float) $pago->total_pagar;

            $pago->mes = (int) ($data['mes'] ?? $pago->mes);
            $pago->anio = (int) ($data['anio'] ?? $pago->anio);
            $pago->horas_extras = $horasExtras;
            $pago->dias_faltados = $diasFaltados;
            $pago->descuento_faltas = $descuentoFaltas;
            $pago->total_pagar = $newTotal;
            $pago->observaciones = $data['observaciones'] ?? $pago->observaciones;

            if ($oldTotal > 0 && abs($newTotal - $oldTotal) > 0.01) {
                $ratio = $newTotal / $oldTotal;
                $pago->importe_p = round((float) $pago->importe_p * $ratio, 2);
                $pago->importe_d = round((float) $pago->importe_d * $ratio, 2);
                $pago->importe_c = round((float) $pago->importe_c * $ratio, 2);
            } else {
                $pago->importe_p = (float) ($data['principal'] ?? 0);
                $pago->importe_d = (float) ($data['deposito'] ?? 0);
                $pago->importe_c = (float) ($data['consorcio'] ?? 0);
            }

            return $pago->save();
        });
    }

    public function recalcularPago(PlanillaPago $pago): bool
    {
        if ($pago->estado === 'pagado') {
            return false;
        }

        $empleado = $this->empleadoService->findById($pago->empleado_id);
        if (! $empleado) {
            return false;
        }

        $sueldoReal = (float) $empleado->sueldo_real;
        $disponible = $this->empleadoService->calcularDisponible(
            $pago->empleado_id,
            $pago->mes,
            $pago->anio
        );

        $totalAdelantos = (float) PlanillaAdelanto::where('empleado_id', $pago->empleado_id)
            ->delMes($pago->mes, $pago->anio)
            ->sum('monto');

        $asistencia = $this->asistenciaService->getByEmpleadoMes($pago->empleado_id, $pago->mes, $pago->anio);
        $diasFaltados = $asistencia ? (float) $asistencia->dias_faltados : 0.0;

        $descuentoFaltas = $this->calcularDescuentoFaltas($sueldoReal, $diasFaltados);

        $oldTotal = (float) $pago->total_pagar;
        $newTotal = $disponible + (float) $pago->horas_extras - $descuentoFaltas;

        $pago->sueldo_base = $disponible;
        $pago->adelantos = $totalAdelantos;
        $pago->dias_faltados = $diasFaltados;
        $pago->descuento_faltas = $descuentoFaltas;
        $pago->total_pagar = $newTotal;

        if ($oldTotal > 0 && abs($newTotal - $oldTotal) > 0.01) {
            $ratio = $newTotal / $oldTotal;
            $pago->importe_p = round((float) $pago->importe_p * $ratio, 2);
            $pago->importe_d = round((float) $pago->importe_d * $ratio, 2);
            $pago->importe_c = round((float) $pago->importe_c * $ratio, 2);
        }

        return $pago->save();
    }

    public function getResumenMensual(int $mes, int $anio): array
    {
        $pagos = $this->getByMes($mes, $anio);

        return [
            'total_pagar' => $pagos->sum('total_pagar'),
            'total_horas_extras' => $pagos->sum('horas_extras'),
            'total_adelantos' => $pagos->sum('adelantos'),
            'total_descuento_faltas' => $pagos->sum('descuento_faltas'),
            'cantidad_empleados' => $pagos->count(),
            'pendientes' => $pagos->where('estado', 'pendiente')->count(),
            'pagados' => $pagos->where('estado', 'pagado')->count(),
        ];
    }

    private function calcularDescuentoFaltas(float $sueldoReal, float $diasFaltados): float
    {
        if ($diasFaltados <= 0) {
            return 0.0;
        }

        return round($diasFaltados * ($sueldoReal / PlanillaAsistenciaService::DIAS_LABORABLES_MES), 2);
    }

    public function generarPagosDelMes(int $mes, int $anio): int
    {
        $diaLimite = $this->getUltimoDiaDelMes($mes, $anio);
        $fechaLimite = "{$anio}-{$mes}-{$diaLimite} 23:59:59";

        $empleados = Empleado::where('estado', 'activo')
            ->where('created_at', '<=', $fechaLimite)
            ->get();

        $contador = 0;

        foreach ($empleados as $empleado) {
            $existe = PlanillaPago::where('empleado_id', $empleado->id)
                ->where('mes', $mes)
                ->where('anio', $anio)
                ->exists();

            if ($existe) {
                continue;
            }

            $disponible = $this->empleadoService->calcularDisponible($empleado->id, $mes, $anio);

            $this->asistenciaService->syncDiasFaltadosFromInasistencias($empleado->id, $mes, $anio);
            $asistencia = $this->asistenciaService->getByEmpleadoMes($empleado->id, $mes, $anio);
            $diasFaltados = $asistencia ? (float) $asistencia->dias_faltados : 0.0;
            $descuentoFaltas = $this->calcularDescuentoFaltas((float) $empleado->sueldo_real, $diasFaltados);

            PlanillaPago::create([
                'empleado_id' => $empleado->id,
                'mes' => $mes,
                'anio' => $anio,
                'sueldo_base' => $disponible,
                'horas_extras' => 0,
                'adelantos' => 0,
                'dias_faltados' => $diasFaltados,
                'descuento_faltas' => $descuentoFaltas,
                'total_pagar' => $disponible - $descuentoFaltas,
                'estado' => 'pendiente',
                'importe_p' => $disponible - $descuentoFaltas,
                'importe_d' => 0,
                'importe_c' => 0,
            ]);

            $contador++;
        }

        return $contador;
    }

    public function confirmarPagosDelMes(int $mes, int $anio): int
    {
        return PlanillaPago::where('mes', $mes)
            ->where('anio', $anio)
            ->where('estado', 'pendiente')
            ->update([
                'estado' => 'pagado',
                'fecha_pago' => now()->toDateString(),
            ]);
    }

    public function existenPagosDelMes(int $mes, int $anio): bool
    {
        return PlanillaPago::where('mes', $mes)
            ->where('anio', $anio)
            ->exists();
    }

    public function getCantidadPagosDelMes(int $mes, int $anio): array
    {
        $total = PlanillaPago::where('mes', $mes)
            ->where('anio', $anio)
            ->count();

        $pendientes = PlanillaPago::where('mes', $mes)
            ->where('anio', $anio)
            ->where('estado', 'pendiente')
            ->count();

        return [
            'total' => $total,
            'pendientes' => $pendientes,
        ];
    }

    private function getUltimoDiaDelMes(int $mes, int $anio): int
    {
        return (int) date('t', strtotime("{$anio}-{$mes}-01"));
    }
}
