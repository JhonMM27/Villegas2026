<?php

namespace App\Http\Controllers;

use App\Exports\PreparadasAcumuladasExport;
use App\Exports\PreparadasFechaExport;
use App\Models\Preparada;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportePreparadaController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:preparadas_list')->only(
            ['index', 'preparadasFechas', 'exportarPreparadasFechas', 'preparadasAcumuladas', 'exportarPreparadasAcumuladas', 'imprimirPreparadasFechas', 'imprimirPreparadasAcumuladas']);
    }

    public function index(Request $request)
    {
        // Lógica para generar el reporte de compras
        return view('reportes.preparadas');
    }

    public function preparadasFechas(Request $request)
    {
        if (! $request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $reportes = Preparada::query()
            ->select([
                'id',
                'fecha',
                'formulacion_id',
                'producto_nombre',
                'producto_empaque',
                'cliente_nombre',
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
            ->orderBy('producto_nombre')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        return view(
            'reportes.preparadas.preparadas_fechas',
            compact('reportes')
        );
    }

    public function imprimirPreparadasFechas(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $reportes = Preparada::query()
            ->join('productos', 'preparadas.producto_id', '=', 'productos.id')
            ->whereBetween('preparadas.fecha', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay(),
            ])
            ->orderBy('preparadas.producto_nombre')
            ->orderBy('preparadas.fecha')
            ->select([
                'preparadas.id',
                'preparadas.fecha',
                'preparadas.items',
                'preparadas.formulacion_id',
                'preparadas.producto_nombre',
                'preparadas.producto_empaque',
                'preparadas.ingreso_kg',
                'preparadas.ingreso_saco',
                'preparadas.ingreso_soles',
            ])
            ->where('estado', '!=', 'anulada')
            ->get();

        $pdf = Pdf::loadView(
            'reportes.preparadas.preparadas_fechas_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'landscape') // 👈 landscape porque tiene más columnas
            ->setOptions([
                'defaultFont' => 'Courier',
            ]);

        return $pdf->stream('preparadas_fechas_'.now()->format('Ymd_His').'.pdf');
    }

    public function exportarPreparadasFechas(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $fileName = 'preparadas_fecha_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new PreparadasFechaExport($fechaInicio, $fechaFin),
            $fileName
        );
    }

    public function preparadasAcumuladas(Request $request)
    {
        $fechaInicio = $request->fecha_inicio;
        $fechaFin = $request->fecha_fin;

        $reportes = Preparada::query()
            ->join('productos', 'preparadas.producto_id', '=', 'productos.id')
            ->join('lineas', 'productos.linea_id', '=', 'lineas.id')
            ->whereBetween('preparadas.fecha', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay(),
            ])
            ->groupBy(
                'preparadas.producto_id',
                'preparadas.producto_nombre',
                'preparadas.producto_empaque',
                'lineas.nombre'
            )
            ->select([
                'preparadas.producto_id',
                'preparadas.producto_nombre',
                'preparadas.producto_empaque',
                'lineas.nombre as linea_nombre',
                DB::raw('AVG(preparadas.costo_unitario)   as costo_unitario'),
                DB::raw('SUM(preparadas.ingreso_saco)  as ingreso_saco'),
                DB::raw('SUM(preparadas.ingreso_kg)    as ingreso_kg'),
                DB::raw('SUM(preparadas.ingreso_soles) as ingreso_soles'),
            ])
            ->where('estado', '!=', 'anulada')
            ->orderBy('preparadas.producto_nombre')
            ->get();

        return view(
            'reportes.preparadas.preparadas_acumuladas',
            compact('reportes')
        );
    }

    public function exportarPreparadasAcumuladas(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $fileName = 'preparadas_acumuladas_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new PreparadasAcumuladasExport($fechaInicio, $fechaFin),
            $fileName
        );
    }

    public function imprimirPreparadasAcumuladas(Request $request)
    {
        $fechaInicio = $request->fecha_inicio;
        $fechaFin = $request->fecha_fin;

        $reportes = Preparada::query()
            ->join('productos', 'preparadas.producto_id', '=', 'productos.id')
            ->join('lineas', 'productos.linea_id', '=', 'lineas.id')
            ->whereBetween('preparadas.fecha', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay(),
            ])
            ->orderBy('lineas.nombre')
            ->orderBy('preparadas.producto_nombre')
            ->select([
                'preparadas.producto_nombre',
                'preparadas.producto_empaque',
                'lineas.nombre as linea_nombre',
                'preparadas.costo_unitario',
                DB::raw('SUM(preparadas.ingreso_kg)    as ingreso_kg'),
                DB::raw('SUM(preparadas.ingreso_saco) as ingreso_saco'),
                DB::raw('SUM(preparadas.ingreso_soles) as ingreso_soles'),
            ])
            ->where('estado', '!=', 'anulada')
            ->groupBy(
                'preparadas.producto_id',
                'preparadas.producto_nombre',
                'preparadas.producto_empaque',
                'lineas.nombre',
                'preparadas.costo_unitario'
            )
            ->get();

        $pdf = Pdf::loadView(
            'reportes.preparadas.preparadas_acumuladas_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'Courier',
            ]);

        return $pdf->stream('preparadas_acumuladas.pdf');
    }
}
