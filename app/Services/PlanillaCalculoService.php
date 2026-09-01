<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Empleado;
use App\Models\EmpleadoSueldo;
use App\Models\PlanillaAdelanto;
use App\Models\PlanillaPago;

class PlanillaCalculoService
{
    public const DIAS_MES = 30;

    public function sueldoNoPlanilla(float $sueldoReal, float $sueldoPlanilla): float
    {
        return round(max($sueldoReal - $sueldoPlanilla, 0), 2);
    }

    public function diasTrabajados(Empleado $empleado, int $mes, int $anio): int
    {
        $inicio = 1;
        $fin = self::DIAS_MES;

        if ($empleado->fecha_ingreso
            && $empleado->fecha_ingreso->year === $anio
            && $empleado->fecha_ingreso->month === $mes) {
            $inicio = min($empleado->fecha_ingreso->day, self::DIAS_MES);
        }

        if ($empleado->fecha_salida
            && $empleado->fecha_salida->year === $anio
            && $empleado->fecha_salida->month === $mes) {
            $fin = min($empleado->fecha_salida->day, self::DIAS_MES);
        }

        return max(0, min(self::DIAS_MES, $fin - $inicio + 1));
    }

    public function calcular(
        Empleado $empleado,
        EmpleadoSueldo $sueldo,
        int $mes,
        int $anio,
        float $horasExtras = 0,
        float $diasFaltados = 0,
        float $ctsSueldoReal = 0
    ): array {
        $diasTrabajados = $this->diasTrabajados($empleado, $mes, $anio);
        $factor = $diasTrabajados / self::DIAS_MES;
        $sueldoReal = (float) $sueldo->sueldo_real;
        $sueldoPlanilla = (float) $sueldo->sueldo_planilla;
        $sueldoNoPlanilla = $this->sueldoNoPlanilla($sueldoReal, $sueldoPlanilla);
        $sueldoRealPeriodo = round($sueldoReal * $factor, 2);
        $sueldoPlanillaPeriodo = round($sueldoPlanilla * $factor, 2);
        $sueldoNoPlanillaPeriodo = round($sueldoNoPlanilla * $factor, 2);
        $adelantos = (float) PlanillaAdelanto::query()
            ->where('empleado_id', $empleado->id)
            ->delMes($mes, $anio)
            ->sum('monto');
        $disponible = round(max($sueldoNoPlanillaPeriodo - $adelantos, 0), 2);
        $descuentoFaltas = $this->descuentoFaltas($sueldoReal, $diasFaltados);
        $totalPagar = round($disponible + $horasExtras - $descuentoFaltas + $ctsSueldoReal, 2);

        return [
            'dias_trabajados' => $diasTrabajados,
            'sueldo_real' => $sueldoReal,
            'sueldo_planilla' => $sueldoPlanilla,
            'sueldo_no_planilla' => $sueldoNoPlanilla,
            'sueldo_real_periodo' => $sueldoRealPeriodo,
            'sueldo_planilla_periodo' => $sueldoPlanillaPeriodo,
            'sueldo_no_planilla_periodo' => $sueldoNoPlanillaPeriodo,
            'adelantos' => round($adelantos, 2),
            'disponible' => $disponible,
            'horas_extras' => round($horasExtras, 2),
            'dias_faltados' => round($diasFaltados, 2),
            'descuento_faltas' => $descuentoFaltas,
            'cts_sueldo_real' => round($ctsSueldoReal, 2),
            'total_pagar' => $totalPagar,
        ];
    }

    public function presentarPago(PlanillaPago $pago): array
    {
        $pago->loadMissing(['empleado', 'sueldoAplicado']);
        $sueldo = $pago->sueldoAplicado;

        if (! $pago->empleado || ! $sueldo) {
            return [
                'dias_trabajados' => self::DIAS_MES,
                'sueldo_real_periodo' => 0.0,
                'sueldo_planilla_periodo' => 0.0,
                'sueldo_no_planilla_periodo' => 0.0,
                'adelantos' => (float) $pago->adelantos,
                'disponible' => (float) $pago->sueldo_base,
                'total_pagar' => (float) $pago->total_pagar,
            ];
        }

        $desglose = $this->calcular(
            $pago->empleado,
            $sueldo,
            (int) $pago->mes,
            (int) $pago->anio,
            (float) $pago->horas_extras,
            (float) $pago->dias_faltados,
            (float) ($pago->cts_sueldo_real ?? 0)
        );

        // Los importes persistidos son la fuente contable de los reportes.
        $desglose['adelantos'] = (float) $pago->adelantos;
        $desglose['disponible'] = (float) $pago->sueldo_base;
        $desglose['descuento_faltas'] = (float) $pago->descuento_faltas;
        $desglose['total_pagar'] = (float) $pago->total_pagar;

        return $desglose;
    }

    public function descuentoFaltas(float $sueldoReal, float $diasFaltados): float
    {
        if ($diasFaltados <= 0) {
            return 0.0;
        }

        return round($diasFaltados * ($sueldoReal / self::DIAS_MES), 2);
    }
}
