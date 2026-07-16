<?php

namespace App\Http\Controllers;

use App\Exports\FormulacionesFechaExport;
use App\Exports\FormulacionesRangoExport;
use App\Models\Formulacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReporteFormulacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:formulaciones_list')->only(
            ['index', 'formulacionesFechas', 'exportarFormulacionesFechas', 'formulacionesRango', 'exportarFormulacionesRango', 'imprimirFormulacionesFechas', 'imprimirFormulacionesRango']);
    }

    public function index(Request $request)
    {
        // Lógica para generar el reporte de compras
        return view('reportes.formulaciones');
    }

    public function formulacionesFechas(Request $request)
    {
        if (! $request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = Formulacion::withCount([
            'detalles as detalles_count',
        ])
            ->with('detalles')
            ->addSelect([
                'formulaciones.id',
                'formulaciones.fecha',
                'formulaciones.producto_nombre',
                'formulaciones.producto_empaque',
                'formulaciones.cliente_nombre',
                'formulaciones.salida_kg',
                'formulaciones.activo',
                'formulaciones.user_nombre',
            ]);

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay(),
            ]);
        }

        $reportes = $query
            ->orderBy('formulaciones.id', 'asc')
            ->get();

        return view('reportes.formulaciones.formulaciones_fechas', compact('reportes', 'fechaInicio', 'fechaFin'));
    }

    public function exportarFormulacionesFechas(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $fileName = 'formulaciones_fecha_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new FormulacionesFechaExport($fechaInicio, $fechaFin),
            $fileName
        );
    }

    public function imprimirFormulacionesFecha(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = Formulacion::withCount([
            'detalles as detalles_count',
        ])
            ->with('detalles')
            ->addSelect([
                'formulaciones.id',
                'formulaciones.fecha',
                'formulaciones.producto_nombre',
                'formulaciones.producto_empaque',
                'formulaciones.cliente_nombre',
                'formulaciones.salida_kg',
                'formulaciones.activo',
                'formulaciones.user_nombre',
            ]);

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay(),
            ]);
        }

        $reportes = $query
            ->orderBy('formulaciones.id', 'asc')
            ->get();

        $pdf = Pdf::loadView(
            'reportes.formulaciones.formulaciones_fecha_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'Courier',
            ]);

        return $pdf->stream('formulaciones_fecha_'.now()->format('Ymd_His').'.pdf');
    }

    public function formulacionesRango(Request $request)
    {
        if (! $request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $request->validate([
            'numeroInicial' => 'required|integer',
            'numeroFinal' => 'required|integer|gte:numeroInicial',
        ]);

        $reportes = Formulacion::query()
            ->select([
                'id',
                'fecha',
                'producto_nombre',
                'producto_empaque',
                'salida_kg',
                'cliente_nombre',
            ])
            ->with([
                'detalles' => function ($q) {
                    $q->select([
                        'id',
                        'formulacion_id',   // ⚠️ obligatorio para la relación
                        'producto_id',
                        'producto_nombre',
                        'producto_empaque',
                        'salida_kg',
                    ]);
                },
            ])
            ->withCount('detalles')
            ->whereBetween('id', [
                $request->numeroInicial,
                $request->numeroFinal,
            ])
            ->orderBy('id')
            ->get();

        return view(
            'reportes.formulaciones.formulaciones_rango', compact('reportes')
        );
    }

    public function exportarFormulacionesRango(Request $request)
    {
        $numeroInicial = $request->input('numeroInicial');
        $numeroFinal = $request->input('numeroFinal');

        $fileName = 'formulaciones_rango_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new FormulacionesRangoExport($numeroInicial, $numeroFinal),
            $fileName
        );
    }

    public function imprimirFormulacionesRango(Request $request)
    {
        $numeroInicial = $request->input('numeroInicial');
        $numeroFinal = $request->input('numeroFinal');

        $reportes = Formulacion::query()
            ->with(['detalles']) // Cargar relación de detalles
            ->whereBetween('id', [$numeroInicial, $numeroFinal])
            ->orderBy('id', 'asc')
            ->get();

        $pdf = Pdf::loadView(
            'reportes.formulaciones.formulaciones_rango_pdf',
            compact('reportes', 'numeroInicial', 'numeroFinal')
        )->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'Courier',
            ]);

        return $pdf->stream('formulaciones_rango_'.now()->format('Ymd_His').'.pdf');
    }
}
