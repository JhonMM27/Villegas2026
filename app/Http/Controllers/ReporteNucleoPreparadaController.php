<?php

namespace App\Http\Controllers;

use App\Models\NucleoPreparada;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReporteNucleoPreparadaController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:preparadas_list')->only(
            ['index', 'nucleoPreparadasAcumuladas', 'imprimirNucleoPreparadasAcumuladas']);
    }

    public function index(Request $request)
    {
        // Lógica para generar el reporte de compras
        return view('reportes.nucleo-preparadas');
    }

    public function nucleoPreparadasAcumuladas(Request $request)
    {
        $fechaInicio = $request->fecha_inicio;
        $fechaFin = $request->fecha_fin;

        $reportes = NucleoPreparada::query()
            ->join('productos', 'nucleo_preparadas.nucleo_id', '=', 'productos.id')
            ->join('lineas', 'productos.linea_id', '=', 'lineas.id')
            ->whereBetween('nucleo_preparadas.fecha', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay(),
            ])
            ->groupBy(
                'nucleo_preparadas.nucleo_id',
                'nucleo_preparadas.nucleo_nombre',
                'nucleo_preparadas.producto_empaque',
                'lineas.nombre'
            )
            ->where('nucleo_preparadas.estado', '!=', 'anulada')
            ->select([
                'nucleo_preparadas.nucleo_id',
                'nucleo_preparadas.nucleo_nombre',
                'nucleo_preparadas.producto_empaque',
                'lineas.nombre as linea_nombre',
                DB::raw('AVG(nucleo_preparadas.costo_unitario)   as costo_unitario'),
                DB::raw('SUM(nucleo_preparadas.ingreso_saco)  as ingreso_saco'),
                DB::raw('SUM(nucleo_preparadas.ingreso_kg)    as ingreso_kg'),
                DB::raw('SUM(nucleo_preparadas.ingreso_soles) as ingreso_soles'),
            ])
            ->orderBy('nucleo_preparadas.nucleo_nombre')
            ->get();

        return view(
            'reportes.nucleo-preparadas.nucleo_preparadas_acumuladas',
            compact('reportes')
        );
    }

    public function imprimirNucleoPreparadasAcumuladas(Request $request)
    {
        $fechaInicio = $request->fecha_inicio;
        $fechaFin = $request->fecha_fin;

        $reportes = NucleoPreparada::query()
            ->join('productos', 'nucleo_preparadas.nucleo_id', '=', 'productos.id')
            ->join('lineas', 'productos.linea_id', '=', 'lineas.id')
            ->whereBetween('nucleo_preparadas.fecha', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay(),
            ])
            ->groupBy(
                'nucleo_preparadas.nucleo_id',
                'nucleo_preparadas.nucleo_nombre',
                'nucleo_preparadas.producto_empaque',
                'lineas.nombre'
            )
            ->where('nucleo_preparadas.estado', '!=', 'anulada')
            ->select([
                'nucleo_preparadas.nucleo_id',
                'nucleo_preparadas.nucleo_nombre',
                'nucleo_preparadas.producto_empaque',
                'lineas.nombre as linea_nombre',
                DB::raw('AVG(nucleo_preparadas.costo_unitario)   as costo_unitario'),
                DB::raw('SUM(nucleo_preparadas.ingreso_saco)  as ingreso_saco'),
                DB::raw('SUM(nucleo_preparadas.ingreso_kg)    as ingreso_kg'),
                DB::raw('SUM(nucleo_preparadas.ingreso_soles) as ingreso_soles'),
            ])
            ->orderBy('nucleo_preparadas.nucleo_nombre')
            ->get();

        $pdf = Pdf::loadView(
            'reportes.nucleo-preparadas.nucleo_preparadas_acumuladas_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'Courier',
            ]);

        return $pdf->stream('nucleo_preparadas_acumuladas.pdf');
    }

    public function exportarNucleoPreparadasAcumuladas(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $fileName = 'nucleo_preparadas_acumuladas_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new \App\Exports\NucleoPreparadasAcumuladasExport($fechaInicio, $fechaFin),
            $fileName
        );
    }

    public function nucleoPreparadasFechas(Request $request)
    {
        if (! $request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $reportes = NucleoPreparada::query()
            ->select([
                'id',
                'fecha',
                'nucleo_id',
                'nucleo_nombre',
                'producto_empaque',
                'ingreso_kg',
                'ingreso_saco',
                'ingreso_soles',
                'items',
            ])
            ->whereBetween('fecha', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay(),
            ])
            ->where('estado', '!=', 'anulada')
            ->orderBy('nucleo_nombre')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        return view(
            'reportes.nucleo-preparadas.nucleo_preparadas_fechas',
            compact('reportes')
        );
    }

    public function imprimirNucleoPreparadasFechas(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $reportes = NucleoPreparada::query()
            ->join('productos', 'nucleo_preparadas.nucleo_id', '=', 'productos.id')
            ->whereBetween('nucleo_preparadas.fecha', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay(),
            ])
            ->where('nucleo_preparadas.estado', '!=', 'anulada')
            ->orderBy('nucleo_preparadas.nucleo_nombre')
            ->orderBy('nucleo_preparadas.fecha')
            ->select([
                'nucleo_preparadas.id',
                'nucleo_preparadas.fecha',
                'nucleo_preparadas.items',
                'nucleo_preparadas.nucleo_id',
                'nucleo_preparadas.nucleo_nombre',
                'nucleo_preparadas.producto_empaque',
                'nucleo_preparadas.ingreso_kg',
                'nucleo_preparadas.ingreso_saco',
                'nucleo_preparadas.ingreso_soles',
            ])
            ->get();

        $pdf = Pdf::loadView(
            'reportes.nucleo-preparadas.nucleo_preparadas_fechas_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'landscape') // 👈 landscape porque tiene más columnas
            ->setOptions([
                'defaultFont' => 'Courier',
            ]);

        return $pdf->stream('nucleo_preparadas_fechas_'.now()->format('Ymd_His').'.pdf');
    }

    public function exportarNucleoPreparadasFechas(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $fileName = 'nucleo_preparadas_fecha_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new \App\Exports\NucleoPreparadasFechaExport($fechaInicio, $fechaFin),
            $fileName
        );
    }
}
