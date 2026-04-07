<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\PlanillaAdelanto;
use App\Models\PlanillaPago;
use App\Models\PlanillaPrestamo;
use App\Services\EmpleadoService;
use App\Services\PlanillaPagoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportePlanillaController extends Controller
{
    public function __construct(
        protected PlanillaPagoService $pagoService,
        protected EmpleadoService $empleadoService
    ) {
        $this->middleware('can:planilla_report')->only(['index', 'mensual', 'porEmpleado', 'adelantosPdf', 'prestamosPdf', 'pagosPendientesPdf', 'empleadoPdf']);
    }

    public function index()
    {
        $empleados = $this->empleadoService->getActivos();
        $mesActual = (int) date('m');
        $anioActual = (int) date('Y');

        return view('planilla.reportes.index', compact('empleados', 'mesActual', 'anioActual'));
    }

    public function mensual(Request $request)
    {
        $mes = (int) $request->get('mes', date('m'));
        $anio = (int) $request->get('anio', date('Y'));

        $pagos = $this->pagoService->getByMes($mes, $anio);
        $resumen = $this->pagoService->getResumenMensual($mes, $anio);

        $nombreMes = $this->getNombreMes($mes);

        return view('planilla.reportes.mensual', compact('pagos', 'resumen', 'nombreMes', 'mes', 'anio'));
    }

    public function porEmpleado(Request $request)
    {
        $empleadoId = (int) $request->get('empleado_id');

        $empleado = $this->empleadoService->findById($empleadoId);
        if (! $empleado) {
            return redirect()->back()->with('error', 'Empleado no encontrado');
        }

        $pagos = PlanillaPago::with('adelantos')
            ->where('empleado_id', $empleadoId)
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get();

        $adelantos = PlanillaAdelanto::where('empleado_id', $empleadoId)
            ->orderByDesc('fecha')
            ->get();

        $resumen = [
            'total_sueldo_planilla' => (float) $empleado->sueldo_planilla,
            'total_sueldo_real' => (float) $empleado->sueldo_real,
            'total_sueldo_base' => $pagos->sum('sueldo_base'),
            'total_horas_extras' => $pagos->sum('horas_extras'),
            'total_adelantos' => $adelantos->sum('monto'),
            'total_pagado' => $pagos->where('estado', 'pagado')->sum('total_pagar'),
            'total_pendiente' => $pagos->where('estado', 'pendiente')->sum('total_pagar'),
            'total_general' => $pagos->sum('total_pagar'),
        ];

        return view('planilla.reportes.empleado', compact('empleado', 'pagos', 'adelantos', 'resumen'));
    }

    public function adelantosPdf(Request $request)
    {
        $empresa = $this->getEmpresa();

        $fechaInicio = $request->get('fecha_inicio', date('Y-01-01'));
        $fechaFin = $request->get('fecha_fin', date('Y-m-d'));

        $adelantos = PlanillaAdelanto::with('empleado')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->orderByDesc('fecha')
            ->get();

        $totalRegistros = $adelantos->count();

        $pdf = PDF::loadView('planilla.reportes.adelantos', compact('adelantos', 'empresa', 'totalRegistros'));

        return $pdf->stream('reporte_adelantos.pdf');
    }

    public function prestamosPdf(Request $request)
    {
        $empresa = $this->getEmpresa();

        $fechaInicio = $request->get('fecha_inicio', date('Y-01-01'));
        $fechaFin = $request->get('fecha_fin', date('Y-m-d'));

        $prestamos = PlanillaPrestamo::with('empleado')
            ->whereBetween('fecha_prestamo', [$fechaInicio, $fechaFin])
            ->whereIn('estado', ['activo', 'pagado'])
            ->orderByDesc('fecha_prestamo')
            ->get();

        $totalRegistros = $prestamos->count();

        $pdf = PDF::loadView('planilla.reportes.prestamos', compact('prestamos', 'empresa', 'totalRegistros'));

        return $pdf->stream('reporte_prestamos.pdf');
    }

    public function pagosPendientesPdf()
    {
        $empresa = $this->getEmpresa();

        $pagos = PlanillaPago::with(['empleado'])
            ->where('estado', 'pendiente')
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get();

        $totalRegistros = $pagos->count();

        $pagos->each(function ($pago) {
            $adelantos = PlanillaAdelanto::where('empleado_id', $pago->empleado_id)
                ->whereMonth('fecha', $pago->mes)
                ->whereYear('fecha', $pago->anio)
                ->sum('monto');
            $pago->adelantos_calculado = $adelantos;
        });

        $pdf = PDF::loadView('planilla.reportes.pagos_pendientes', compact('pagos', 'empresa', 'totalRegistros'));

        return $pdf->stream('reporte_pagos_pendientes.pdf');
    }

    public function empleadoPdf(Request $request)
    {
        $empresa = $this->getEmpresa();

        $empleadoId = (int) $request->get('empleado_id');
        $fechaInicio = $request->get('fecha_inicio', date('Y-01-01'));
        $fechaFin = $request->get('fecha_fin', date('Y-m-d'));

        $mesInicio = (int) date('m', strtotime($fechaInicio));
        $anioInicio = (int) date('Y', strtotime($fechaInicio));
        $mesFin = (int) date('m', strtotime($fechaFin));
        $anioFin = (int) date('Y', strtotime($fechaFin));

        $query = PlanillaPago::with('empleado');

        if ($empleadoId > 0) {
            $query->where('empleado_id', $empleadoId);
        }

        if ($anioInicio == $anioFin) {
            $query->where('anio', $anioInicio)
                ->whereBetween('mes', [$mesInicio, $mesFin]);
        } else {
            $query->where(function ($q) use ($anioInicio, $anioFin, $mesInicio, $mesFin) {
                $q->where(function ($q1) use ($anioInicio, $mesInicio) {
                    $q1->where('anio', $anioInicio)
                        ->where('mes', '>=', $mesInicio);
                })->orWhere(function ($q2) use ($anioFin, $mesFin) {
                    $q2->where('anio', $anioFin)
                        ->where('mes', '<=', $mesFin);
                })->orWhere(function ($q3) use ($anioInicio, $anioFin) {
                    $q3->where('anio', '>', $anioInicio)
                        ->where('anio', '<', $anioFin);
                });
            });
        }

        $pagos = $query->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get();

        $adelantosQuery = PlanillaAdelanto::whereBetween('fecha', [$fechaInicio, $fechaFin]);
        if ($empleadoId > 0) {
            $adelantosQuery->where('empleado_id', $empleadoId);
        }
        $adelantos = $adelantosQuery->orderByDesc('fecha')->get();

        $totalRegistros = $pagos->count();

        if ($empleadoId > 0) {
            $empleado = Empleado::find($empleadoId);
            if (! $empleado) {
                return redirect()->back()->with('error', 'Empleado no encontrado');
            }
            $resumen = [
                'total_sueldo_planilla' => (float) $empleado->sueldo_planilla,
                'total_sueldo_real' => (float) $empleado->sueldo_real,
                'total_sueldo_base' => $pagos->sum('sueldo_base'),
                'total_horas_extras' => $pagos->sum('horas_extras'),
                'total_adelantos' => $adelantos->sum('monto'),
                'total_pagado' => $pagos->where('estado', 'pagado')->sum('total_pagar'),
                'total_pendiente' => $pagos->where('estado', 'pendiente')->sum('total_pagar'),
                'total_general' => $pagos->sum('total_pagar'),
            ];
            $pdf = PDF::loadView('planilla.reportes.empleados_pdf', compact('empleado', 'pagos', 'adelantos', 'empresa', 'totalRegistros', 'resumen'));

            return $pdf->stream('reporte_empleado_'.$empleadoId.'.pdf');
        }

        $pdf = PDF::loadView('planilla.reportes.empleados_pdf', compact('pagos', 'adelantos', 'empresa', 'totalRegistros'));

        return $pdf->stream('reporte_empleados.pdf');
    }

    private function getEmpresa()
    {
        return (object) [
            'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
            'direccion' => 'Carretera Pomalca KM 3',
            'ruc' => '20538937321',
        ];
    }

    private function getNombreMes(int $mes): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
            4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Setiembre',
            10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return $meses[$mes] ?? $mes;
    }
}
