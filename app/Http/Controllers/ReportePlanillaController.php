<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\PlanillaAdelanto;
use App\Models\PlanillaInasistencia;
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
        $this->middleware('can:planilla_report')->only(['index', 'mensual', 'porEmpleado', 'adelantosPdf', 'prestamosPdf', 'pagosPendientesPdf', 'empleadoPdf', 'inasistenciasPdf', 'trabajadoresPdf']);
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
            ->orderBy('fecha', 'asc')
            ->get();

        $sueldoReal = (float) $empleado->sueldo_real;
        $sueldoPlanilla = (float) $empleado->sueldo_planilla;

        $pagos->each(function ($pago) use ($empleado, $sueldoReal, $sueldoPlanilla) {
            $pago->prorrateo_ingreso = false;
            $pago->prorrateo_salida = false;
            $pago->sueldo_base_mostrar = (float) $pago->sueldo_base;
            $pago->total_pagar_mostrar = (float) $pago->total_pagar;

            if ($empleado->fecha_salida) {
                $fs = $empleado->fecha_salida;
                if ($fs->year === $pago->anio && $fs->month === $pago->mes && $fs->day < 30) {
                    $diasTrabajados = $fs->day;
                    $factor = $diasTrabajados / 30;
                    $baseDisponible = $sueldoReal * $factor;
                    $pago->prorrateo_salida = true;
                    $pago->sueldo_base_mostrar = $baseDisponible;
                    $pago->total_pagar_mostrar = $baseDisponible + (float) $pago->horas_extras - (float) $pago->descuento_faltas;
                }
            }

            if ($empleado->fecha_ingreso) {
                $fi = $empleado->fecha_ingreso;
                if ($fi->year === $pago->anio && $fi->month === $pago->mes && $fi->day > 1) {
                    $diasTrabajados = 30 - $fi->day + 1;
                    $factor = $diasTrabajados / 30;
                    $baseDisponible = $sueldoReal * $factor;
                    $pago->prorrateo_ingreso = true;
                    $pago->sueldo_base_mostrar = $baseDisponible;
                    $pago->total_pagar_mostrar = $baseDisponible + (float) $pago->horas_extras - (float) $pago->descuento_faltas;
                }
            }
        });

        $totalSueldoBaseMostrar = $pagos->sum('sueldo_base_mostrar');
        $totalPagadoMostrar = $pagos->where('estado', 'pagado')->sum('total_pagar_mostrar');
        $totalPendienteMostrar = $pagos->where('estado', 'pendiente')->sum('total_pagar_mostrar');

        $resumen = [
            'total_sueldo_planilla' => (float) $empleado->sueldo_planilla,
            'total_sueldo_real' => (float) $empleado->sueldo_real,
            'total_sueldo_base' => $totalSueldoBaseMostrar,
            'total_horas_extras' => $pagos->sum('horas_extras'),
            'total_adelantos' => $adelantos->sum('monto'),
            'total_dias_faltas' => $pagos->sum('dias_faltados'),
            'total_descuento_faltas' => $pagos->sum('descuento_faltas'),
            'total_cts_planilla' => $pagos->sum('cts_planilla') ?? 0,
            'total_cts_sueldo_real' => $pagos->sum('cts_sueldo_real') ?? 0,
            'total_pagado' => $totalPagadoMostrar,
            'total_pendiente' => $totalPendienteMostrar,
            'total_general' => $totalSueldoBaseMostrar + $pagos->sum('horas_extras') - $pagos->sum('descuento_faltas'),
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
            ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'))
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
            ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'))
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
            ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'))
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

    // public function empleadoPdf(Request $request)
    // {
    //     $empresa = $this->getEmpresa();

    //     $empleadoId = (int) $request->get('empleado_id');
    //     $fechaInicio = $request->get('fecha_inicio', date('Y-01-01'));
    //     $fechaFin = $request->get('fecha_fin', date('Y-m-d'));

    //     $mesInicio = (int) date('m', strtotime($fechaInicio));
    //     $anioInicio = (int) date('Y', strtotime($fechaInicio));
    //     $mesFin = (int) date('m', strtotime($fechaFin));
    //     $anioFin = (int) date('Y', strtotime($fechaFin));

    //     $query = PlanillaPago::with('empleado')
    //         ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'));

    //     if ($empleadoId > 0) {
    //         $query->where('empleado_id', $empleadoId);
    //     }

    //     if ($anioInicio == $anioFin) {
    //         $query->where('anio', $anioInicio)
    //             ->whereBetween('mes', [$mesInicio, $mesFin]);
    //     } else {
    //         $query->where(function ($q) use ($anioInicio, $anioFin, $mesInicio, $mesFin) {
    //             $q->where(function ($q1) use ($anioInicio, $mesInicio) {
    //                 $q1->where('anio', $anioInicio)
    //                     ->where('mes', '>=', $mesInicio);
    //             })->orWhere(function ($q2) use ($anioFin, $mesFin) {
    //                 $q2->where('anio', $anioFin)
    //                     ->where('mes', '<=', $mesFin);
    //             })->orWhere(function ($q3) use ($anioInicio, $anioFin) {
    //                 $q3->where('anio', '>', $anioInicio)
    //                     ->where('anio', '<', $anioFin);
    //             });
    //         });
    //     }

    //     $pagos = $query->orderByDesc('anio')
    //         ->orderByDesc('mes')
    //         ->get();

    //     $adelantosQuery = PlanillaAdelanto::whereBetween('fecha', [$fechaInicio, $fechaFin])
    //         ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'));
    //     if ($empleadoId > 0) {
    //         $adelantosQuery->where('empleado_id', $empleadoId);
    //     }
    //     $adelantos = $adelantosQuery->orderBy('fecha', 'asc')->get();

    //     $totalRegistros = $pagos->count();

    //     $inicio = strtotime($fechaInicio);
    //     $fin = strtotime($fechaFin);
    //     $diasEnRango = floor(($fin - $inicio) / 86400) + 1;

    //     $totalProporcional = 0;
    //     $totalPagadoProporcional = 0;
    //     foreach ($pagos as $pago) {
    //         $fechaIngreso = $pago->empleado?->fecha_ingreso;
    //         $esMesIngreso = $fechaIngreso 
    //             && $pago->anio === $fechaIngreso->year 
    //             && $pago->mes === $fechaIngreso->month 
    //             && $fechaIngreso->day > 1;
            
    //         if ($esMesIngreso) {
    //             $pago->monto_proporcional = (float) $pago->total_pagar;
    //         } else {
    //             $pago->monto_proporcional = round(((float) $pago->total_pagar / 30) * $diasEnRango, 2);
    //         }
    //         $totalProporcional += $pago->monto_proporcional;
    //         if ($pago->estado === 'pagado') {
    //             $totalPagadoProporcional += $pago->monto_proporcional;
    //         }
    //     }

    //     if ($empleadoId > 0) {
    //         $empleado = Empleado::find($empleadoId);
    //         if (! $empleado) {
    //             return redirect()->back()->with('error', 'Empleado no encontrado');
    //         }
    //         foreach ($pagos as $pago) {
    //             $pago->sueldo_planilla_proporcional = round(((float) $empleado->sueldo_planilla / 30) * $diasEnRango, 2);
    //         }
    //         $resumen = [
    //             'total_sueldo_planilla' => (float) $empleado->sueldo_planilla,
    //             'total_sueldo_real' => (float) $empleado->sueldo_real,
    //             'total_sueldo_base' => $pagos->sum('sueldo_base'),
    //             'total_horas_extras' => $pagos->sum('horas_extras'),
    //             'total_adelantos' => $adelantos->sum('monto'),
    //             'total_dias_faltas' => $pagos->sum('dias_faltados'),
    //             'total_descuento_faltas' => $pagos->sum('descuento_faltas'),
    //             'total_cts_planilla' => $pagos->sum('cts_planilla') ?? 0,
    //             'total_cts_sueldo_real' => $pagos->sum('cts_sueldo_real') ?? 0,
    //             'total_pagado' => $pagos->where('estado', 'pagado')->sum('total_pagar'),
    //             'total_pendiente' => $pagos->where('estado', 'pendiente')->sum('total_pagar'),
    //             'total_general' => $pagos->sum('total_pagar'),
    //             'dias_en_rango' => $diasEnRango,
    //             'total_proporcional' => $totalProporcional,
    //             'total_pagado_proporcional' => $totalPagadoProporcional,
    //             'total_pendiente_proporcional' => $totalProporcional - $totalPagadoProporcional,
    //         ];
    //         $pdf = PDF::loadView('planilla.reportes.empleados_pdf', compact('empleado', 'pagos', 'adelantos', 'empresa', 'totalRegistros', 'resumen', 'fechaInicio', 'fechaFin'));

    //         return $pdf->stream('reporte_empleado_'.$empleadoId.'.pdf');
    //     }

    //     $pdf = PDF::loadView('planilla.reportes.empleados_pdf', compact('pagos', 'adelantos', 'empresa', 'totalRegistros'));

    //     return $pdf->stream('reporte_empleados.pdf');
    // }

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

    $query = PlanillaPago::with('empleado')
        ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'));

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

    $adelantosQuery = PlanillaAdelanto::whereBetween('fecha', [$fechaInicio, $fechaFin])
        ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'));

    if ($empleadoId > 0) {
        $adelantosQuery->where('empleado_id', $empleadoId);
    }

    $adelantos = $adelantosQuery->orderBy('fecha', 'asc')->get();
    $totalRegistros = $pagos->count();

    $totalProporcional = 0;
    $totalPagadoProporcional = 0;

    foreach ($pagos as $pago) {
        // No se prorratea de nuevo.
        $pago->monto_proporcional = (float) $pago->total_pagar;

        $totalProporcional += $pago->monto_proporcional;

        if ($pago->estado === 'pagado') {
            $totalPagadoProporcional += $pago->monto_proporcional;
        }
    }

    if ($empleadoId > 0) {
        $empleado = Empleado::find($empleadoId);

        if (! $empleado) {
            return redirect()->back()->with('error', 'Empleado no encontrado');
        }

        /*
         * Días trabajados reales dentro del rango seleccionado.
         * Ejemplo:
         * fechaInicio = 01/06/2026
         * fechaFin = 05/06/2026
         * fecha_ingreso = 02/06/2026
         * Resultado = 4 días
         */
        $inicioReporte = \Carbon\Carbon::parse($fechaInicio)->startOfDay();
        $finReporte = \Carbon\Carbon::parse($fechaFin)->startOfDay();

        $inicioTrabajo = $inicioReporte->copy();
        $finTrabajo = $finReporte->copy();

        if ($empleado->fecha_ingreso) {
            $fechaIngreso = \Carbon\Carbon::parse($empleado->fecha_ingreso)->startOfDay();

            if ($fechaIngreso->greaterThan($inicioTrabajo)) {
                $inicioTrabajo = $fechaIngreso;
            }
        }

        if ($empleado->fecha_salida) {
            $fechaSalida = \Carbon\Carbon::parse($empleado->fecha_salida)->startOfDay();

            if ($fechaSalida->lessThan($finTrabajo)) {
                $finTrabajo = $fechaSalida;
            }
        }

        $diasTrabajados = $inicioTrabajo->lessThanOrEqualTo($finTrabajo)
            ? $inicioTrabajo->diffInDays($finTrabajo) + 1
            : 0;

        foreach ($pagos as $pago) {
            $pago->sueldo_planilla_proporcional = (float) $pago->sueldo_base;
            $pago->dias_trabajados_reporte = $diasTrabajados;
        }

        $resumen = [
            'total_sueldo_planilla' => (float) $empleado->sueldo_planilla,
            'total_sueldo_real' => (float) $empleado->sueldo_real,
            'total_sueldo_base' => $pagos->sum('sueldo_base'),
            'total_horas_extras' => $pagos->sum('horas_extras'),
            'total_adelantos' => $adelantos->sum('monto'),
            'total_dias_faltas' => $pagos->sum('dias_faltados'),
            'total_descuento_faltas' => $pagos->sum('descuento_faltas'),
            'total_cts_planilla' => $pagos->sum('cts_planilla') ?? 0,
            'total_cts_sueldo_real' => $pagos->sum('cts_sueldo_real') ?? 0,
            'total_pagado' => $pagos->where('estado', 'pagado')->sum('total_pagar'),
            'total_pendiente' => $pagos->where('estado', 'pendiente')->sum('total_pagar'),
            'total_general' => $pagos->sum('total_pagar'),

            // Este es el que debe mostrarse en el PDF.
            'dias_en_rango' => $diasTrabajados,

            'total_proporcional' => $totalProporcional,
            'total_pagado_proporcional' => $totalPagadoProporcional,
            'total_pendiente_proporcional' => $totalProporcional - $totalPagadoProporcional,
        ];

        $pdf = PDF::loadView(
            'planilla.reportes.empleados_pdf',
            compact(
                'empleado',
                'pagos',
                'adelantos',
                'empresa',
                'totalRegistros',
                'resumen',
                'fechaInicio',
                'fechaFin'
            )
        );

        return $pdf->stream('reporte_empleado_'.$empleadoId.'.pdf');
    }

    $pdf = PDF::loadView(
        'planilla.reportes.empleados_pdf',
        compact(
            'pagos',
            'adelantos',
            'empresa',
            'totalRegistros',
            'fechaInicio',
            'fechaFin'
        )
    );

    return $pdf->stream('reporte_empleados.pdf');
}

    public function inasistenciasPdf(Request $request)
    {
        $empresa = $this->getEmpresa();

        $fechaInicio = $request->get('fecha_inicio', date('Y-01-01'));
        $fechaFin = $request->get('fecha_fin', date('Y-m-d'));
        $empleadoId = (int) $request->get('empleado_id', 0);

        $query = PlanillaInasistencia::with('empleado')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'))
            ->orderBy('fecha', 'desc');

        if ($empleadoId > 0) {
            $query->where('empleado_id', $empleadoId);
        }

        $inasistencias = $query->get();

        $totalRegistros = $inasistencias->count();
        $totalDias = $inasistencias->sum(fn ($i) => $i->medio_dia ? 0.5 : 1.0);

        $pdf = PDF::loadView('planilla.reportes.inasistencias_pdf', compact(
            'inasistencias',
            'empresa',
            'totalRegistros',
            'totalDias',
            'fechaInicio',
            'fechaFin'
        ));

        return $pdf->stream('reporte_inasistencias.pdf');
    }

    public function trabajadoresPdf()
    {
        $empresa = $this->getEmpresa();

        $empleados = Empleado::where('estado', 'activo')
            ->orderBy('nombre')
            ->get();

        $totalRegistros = $empleados->count();

        $pdf = PDF::loadView('planilla.reportes.trabajadores_pdf', compact(
            'empleados',
            'empresa',
            'totalRegistros'
        ));

        return $pdf->stream('reporte_trabajadores.pdf');
    }

    public function trabajadoresConSueldoPdf()
    {
        $empresa = $this->getEmpresa();

        $empleados = Empleado::where('estado', 'activo')
            ->orderBy('nombre')
            ->get();

        $totalRegistros = $empleados->count();

        $pdf = PDF::loadView('planilla.reportes.trabajadores_sueldo_pdf', compact(
            'empleados',
            'empresa',
            'totalRegistros'
        ));

        return $pdf->stream('reporte_trabajadores_sueldo.pdf');
    }

    private function getEmpresa()
    {
        return (object) [
            'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
            'direccion' => 'Carretera Pomalca KM 3',
            'ruc' => '20538937321',
        ];
    }

    public function porEmpleadoConSueldo(Request $request)
    {
        $empleadoId = (int) $request->get('empleado_id');
        $fechaInicio = $request->get('fecha_inicio', date('Y-01-01'));
        $fechaFin = $request->get('fecha_fin', date('Y-m-d'));

        $empleado = $this->empleadoService->findById($empleadoId);
        if (! $empleado) {
            return redirect()->back()->with('error', 'Empleado no encontrado');
        }

        $mesInicio = (int) date('m', strtotime($fechaInicio));
        $anioInicio = (int) date('Y', strtotime($fechaInicio));
        $mesFin = (int) date('m', strtotime($fechaFin));
        $anioFin = (int) date('Y', strtotime($fechaFin));

        $query = PlanillaPago::with('adelantos')
            ->where('empleado_id', $empleadoId);

        if ($anioInicio == $anioFin) {
            $query->where('anio', $anioInicio)
                ->whereBetween('mes', [$mesInicio, $mesFin]);
        } else {
            $query->where(function ($q) use ($anioInicio, $anioFin, $mesInicio, $mesFin) {
                $q->where(function ($q1) use ($anioInicio, $mesInicio) {
                    $q1->where('anio', $anioInicio)->where('mes', '>=', $mesInicio);
                })->orWhere(function ($q2) use ($anioFin, $mesFin) {
                    $q2->where('anio', $anioFin)->where('mes', '<=', $mesFin);
                })->orWhere(function ($q3) use ($anioInicio, $anioFin) {
                    $q3->where('anio', '>', $anioInicio)->where('anio', '<', $anioFin);
                });
            });
        }

        $pagos = $query->orderByDesc('anio')->orderByDesc('mes')->get();

        $adelantosQuery = PlanillaAdelanto::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('empleado_id', $empleadoId);
        $adelantos = $adelantosQuery->orderBy('fecha', 'asc')->get();

        $resumen = [
            'total_sueldo_planilla' => (float) $empleado->sueldo_planilla,
            'total_sueldo_real' => (float) $empleado->sueldo_real,
            'total_sueldo_base' => $pagos->sum('sueldo_base'),
            'total_horas_extras' => $pagos->sum('horas_extras'),
            'total_adelantos' => $adelantos->sum('monto'),
            'total_dias_faltas' => $pagos->sum('dias_faltados'),
            'total_descuento_faltas' => $pagos->sum('descuento_faltas'),
            'total_cts_planilla' => $pagos->sum('cts_planilla') ?? 0,
            'total_cts_sueldo_real' => $pagos->sum('cts_sueldo_real') ?? 0,
            'total_pagado' => $pagos->where('estado', 'pagado')->sum('total_pagar'),
            'total_pendiente' => $pagos->where('estado', 'pendiente')->sum('total_pagar'),
            'total_general' => $pagos->sum('total_pagar'),
        ];

        return view('planilla.reportes.empleado_sueldo', compact('empleado', 'pagos', 'adelantos', 'resumen', 'fechaInicio', 'fechaFin'));
    }

    public function empleadoConSueldoPdf(Request $request)
    {
        $empleadoId = (int) $request->get('empleado_id');
        $fechaInicio = $request->get('fecha_inicio', date('Y-m-01'));
        $fechaFin = $request->get('fecha_fin', date('Y-m-d'));

        $inicio = strtotime($fechaInicio);
        $fin = strtotime($fechaFin);
        $diasEnRango = floor(($fin - $inicio) / 86400) + 1;

        if ($diasEnRango > 30) {
            return redirect()->back()->with('error', 'El rango de fechas no puede exceder 30 días');
        }

        $empleado = $this->empleadoService->findById($empleadoId);
        if (! $empleado) {
            return redirect()->back()->with('error', 'Empleado no encontrado');
        }

        $empresa = $this->getEmpresa();

        $mesInicio = (int) date('m', strtotime($fechaInicio));
        $anioInicio = (int) date('Y', strtotime($fechaInicio));
        $mesFin = (int) date('m', strtotime($fechaFin));
        $anioFin = (int) date('Y', strtotime($fechaFin));

        $query = PlanillaPago::with('adelantosRecords')
            ->where('empleado_id', $empleadoId);

        if ($anioInicio == $anioFin) {
            $query->where('anio', $anioInicio)
                ->whereBetween('mes', [$mesInicio, $mesFin]);
        } else {
            $query->where(function ($q) use ($anioInicio, $anioFin, $mesInicio, $mesFin) {
                $q->where(function ($q1) use ($anioInicio, $mesInicio) {
                    $q1->where('anio', $anioInicio)->where('mes', '>=', $mesInicio);
                })->orWhere(function ($q2) use ($anioFin, $mesFin) {
                    $q2->where('anio', $anioFin)->where('mes', '<=', $mesFin);
                })->orWhere(function ($q3) use ($anioInicio, $anioFin) {
                    $q3->where('anio', '>', $anioInicio)->where('anio', '<', $anioFin);
                });
            });
        }

        $pagos = $query->orderByDesc('anio')->orderByDesc('mes')->get();

        $adelantosQuery = PlanillaAdelanto::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('empleado_id', $empleadoId);
        $adelantos = $adelantosQuery->orderBy('fecha', 'asc')->get();

        $totalProporcional = 0;
        $totalPagadoProporcional = 0;
        foreach ($pagos as $pago) {
            $fechaIngreso = $pago->empleado?->fecha_ingreso;
            $esMesIngreso = $fechaIngreso 
                && $pago->anio === $fechaIngreso->year 
                && $pago->mes === $fechaIngreso->month 
                && $fechaIngreso->day > 1;
            
            if ($esMesIngreso) {
                $pago->monto_proporcional = (float) $pago->total_pagar;
            } else {
                $pago->monto_proporcional = round(((float) $pago->total_pagar / 30) * $diasEnRango, 2);
            }
            $pago->sueldo_planilla_proporcional = round(((float) $empleado->sueldo_planilla / 30) * $diasEnRango, 2);
            $totalProporcional += $pago->monto_proporcional;
            if ($pago->estado === 'pagado') {
                $totalPagadoProporcional += $pago->monto_proporcional;
            }
        }

        $resumen = [
            'total_sueldo_planilla' => (float) $empleado->sueldo_planilla,
            'total_sueldo_real' => (float) $empleado->sueldo_real,
            'total_sueldo_base' => $pagos->sum('sueldo_base'),
            'total_horas_extras' => $pagos->sum('horas_extras'),
            'total_adelantos' => $adelantos->sum('monto'),
            'total_dias_faltas' => $pagos->sum('dias_faltados'),
            'total_descuento_faltas' => $pagos->sum('descuento_faltas'),
            'total_cts_planilla' => $pagos->sum('cts_planilla') ?? 0,
            'total_cts_sueldo_real' => $pagos->sum('cts_sueldo_real') ?? 0,
            'total_pagado' => $pagos->where('estado', 'pagado')->sum('total_pagar'),
            'total_pendiente' => $pagos->where('estado', 'pendiente')->sum('total_pagar'),
            'total_general' => $pagos->sum('total_pagar'),
            'dias_en_rango' => $diasEnRango,
            'total_proporcional' => $totalProporcional,
            'total_pagado_proporcional' => $totalPagadoProporcional,
            'total_pendiente_proporcional' => $totalProporcional - $totalPagadoProporcional,
        ];

        $totalRegistros = $pagos->count();

        $pdf = PDF::loadView('planilla.reportes.empleados_pdf', compact('empleado', 'pagos', 'adelantos', 'empresa', 'totalRegistros', 'resumen', 'fechaInicio', 'fechaFin'));

        return $pdf->download('reporte_empleado_'.$empleadoId.'_sueldo.pdf');
    }

    public function empleadosAllZipPdf(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', date('Y-01-01'));
        $fechaFin = $request->get('fecha_fin', date('Y-m-d'));

        $mesInicio = (int) date('m', strtotime($fechaInicio));
        $anioInicio = (int) date('Y', strtotime($fechaInicio));
        $mesFin = (int) date('m', strtotime($fechaFin));
        $anioFin = (int) date('Y', strtotime($fechaFin));

        $empleados = Empleado::where('estado', 'activo')->get();

        if ($empleados->isEmpty()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'No hay empleados activos.'], 400);
            }

            return redirect()->back()->with('error', 'No hay empleados activos.');
        }

        $pdfUrls = [];
        foreach ($empleados as $empleado) {
            $query = PlanillaPago::where('empleado_id', $empleado->id);

            if ($anioInicio == $anioFin) {
                $query->where('anio', $anioInicio)
                    ->whereBetween('mes', [$mesInicio, $mesFin]);
            } else {
                $query->where(function ($q) use ($anioInicio, $anioFin, $mesInicio, $mesFin) {
                    $q->where(function ($q1) use ($anioInicio, $mesInicio) {
                        $q1->where('anio', $anioInicio)->where('mes', '>=', $mesInicio);
                    })->orWhere(function ($q2) use ($anioFin, $mesFin) {
                        $q2->where('anio', $anioFin)->where('mes', '<=', $mesFin);
                    })->orWhere(function ($q3) use ($anioInicio, $anioFin) {
                        $q3->where('anio', '>', $anioInicio)->where('anio', '<', $anioFin);
                    });
                });
            }

            $pagos = $query->orderByDesc('anio')->orderByDesc('mes')->get();

            if ($pagos->count() == 0) {
                continue;
            }

            $fileName = 'empleado_'.preg_replace('/[^a-zA-Z0-9]/', '_', $empleado->nombre).'_'.$empleado->dni.'.pdf';
            $url = route('reportes.planilla.empleado_sueldo_pdf', [
                'empleado_id' => $empleado->id,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ]);
            $pdfUrls[] = ['name' => $fileName, 'url' => $url];
        }

        if (empty($pdfUrls)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'No hay pagos en el período seleccionado.'], 400);
            }

            return redirect()->back()->with('error', 'No hay pagos en el período seleccionado.');
        }

        if ($request->ajax() || $request->wantsJson() || $request->get('json') === '1') {
            return response()->json(['pdfUrls' => $pdfUrls]);
        }

        return view('planilla.reportes.descargar_pdfs', compact('pdfUrls'));
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
