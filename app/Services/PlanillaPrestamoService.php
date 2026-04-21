<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlanillaPrestamo;
use App\Models\PlanillaPrestamoPago;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlanillaPrestamoService
{
    public function getAll(): Collection
    {
        return PlanillaPrestamo::with('empleado')
            ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'))
            ->orderByDesc('id')
            ->get();
    }

    public function getActivos(): Collection
    {
        return PlanillaPrestamo::with('empleado')
            ->activos()
            ->orderByDesc('id')
            ->get();
    }

    public function getByEmpleado(int $empleadoId): Collection
    {
        return PlanillaPrestamo::where('empleado_id', $empleadoId)
            ->orderByDesc('id')
            ->get();
    }

    public function findById(int $id): ?PlanillaPrestamo
    {
        return PlanillaPrestamo::with(['empleado', 'pagos'])->find($id);
    }

    public function create(array $data): PlanillaPrestamo
    {
        return DB::transaction(function () use ($data) {
            $prestamo = PlanillaPrestamo::create([
                'empleado_id' => $data['empleado_id'],
                'numero_interno' => $data['numero_interno'],
                'monto_original' => $data['monto_original'],
                'saldo_pendiente' => $data['monto_original'],
                'fecha_prestamo' => $data['fecha_prestamo'],
                'observaciones' => $data['observaciones'] ?? null,
                'estado' => 'activo',
                'importe_p' => $data['principal'] ?? 0,
                'importe_d' => $data['deposito'] ?? 0,
                'importe_c' => $data['consorcio'] ?? 0,
            ]);

            return $prestamo;
        });
    }

    public function update(PlanillaPrestamo $prestamo, array $data): bool
    {
        return $prestamo->update($data);
    }

    public function delete(PlanillaPrestamo $prestamo): bool
    {
        return $prestamo->delete();
    }

    public function registrarPago(int $prestamoId, array $data): PlanillaPrestamoPago
    {
        return DB::transaction(function () use ($prestamoId, $data) {
            $prestamo = $this->findById($prestamoId);
            if (! $prestamo) {
                throw new \Exception('Préstamo no encontrado');
            }

            $montoPago = (float) $data['monto_pagado'];

            if ($montoPago > $prestamo->saldo_pendiente) {
                $montoPago = (float) $prestamo->saldo_pendiente;
            }

            $pago = PlanillaPrestamoPago::create([
                'numero_interno' => $data['numero_interno'],
                'planilla_prestamo_id' => $prestamoId,
                'monto_pagado' => $montoPago,
                'fecha_pago' => $data['fecha_pago'],
                'observaciones' => $data['observaciones'] ?? null,
                'importe_p' => $data['importe_p'] ?? 0,
                'importe_d' => $data['importe_d'] ?? 0,
                'importe_c' => $data['importe_c'] ?? $montoPago,
            ]);

            $prestamo->refresh();
            $prestamo->actualizarSaldo();

            return $pago;
        });
    }

    public function getPagos(int $prestamoId): Collection
    {
        return PlanillaPrestamoPago::where('planilla_prestamo_id', $prestamoId)
            ->orderBy('fecha_pago', 'desc')
            ->orderByDesc('id')
            ->get();
    }

    public function findPagoById(int $id): ?PlanillaPrestamoPago
    {
        return PlanillaPrestamoPago::with('prestamo.empleado')->find($id);
    }

    public function updatePago(PlanillaPrestamoPago $pago, array $data): bool
    {
        return DB::transaction(function () use ($pago, $data) {
            $prestamo = $pago->prestamo;
            $montoAnterior = (float) $pago->monto_pagado;
            $montoNuevo = (float) $data['monto_pagado'];

            $pago->update([
                'numero_interno' => $data['numero_interno'],
                'monto_pagado' => $montoNuevo,
                'fecha_pago' => $data['fecha_pago'],
                'observaciones' => $data['observaciones'] ?? null,
                'importe_p' => $data['importe_p'] ?? 0,
                'importe_d' => $data['importe_d'] ?? 0,
                'importe_c' => $data['importe_c'] ?? $montoNuevo,
            ]);

            $prestamo->actualizarSaldo();

            return true;
        });
    }

    public function deletePago(PlanillaPrestamoPago $pago): bool
    {
        return DB::transaction(function () use ($pago) {
            $prestamo = $pago->prestamo;
            $pago->delete();
            $prestamo->actualizarSaldo();

            return true;
        });
    }

    public function anular(PlanillaPrestamo $prestamo): bool
    {
        return DB::transaction(function () use ($prestamo) {
            $prestamo->pagos()->delete();
            $prestamo->estado = 'anulado';
            $prestamo->saldo_pendiente = 0;

            return $prestamo->save();
        });
    }

    public function getSaldosActivosPorEmpleado(int $empleadoId): Collection
    {
        return PlanillaPrestamo::where('empleado_id', $empleadoId)
            ->activos()
            ->get();
    }

    public function getTotalDeudaPorEmpleado(int $empleadoId): float
    {
        return (float) PlanillaPrestamo::where('empleado_id', $empleadoId)
            ->activos()
            ->sum('saldo_pendiente');
    }
}
