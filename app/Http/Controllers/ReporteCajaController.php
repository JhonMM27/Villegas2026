<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReporteCajaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReporteCajaController extends Controller
{
    public function __construct(
        protected ReporteCajaService $reporteCajaService
    ) {
        $this->middleware('can:caja_report')->only(['index', 'general', 'detallado', 'generalPdf']);
    }

    public function index(Request $request)
    {
        return view('reportes.caja');
    }

    public function general(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $reporteData = $this->reporteCajaService->obtenerDatosReporteCaja($ini, $fin, false);

        return view('reportes.caja.general', $reporteData);
    }

    public function detallado(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $reporteData = $this->reporteCajaService->obtenerDatosReporteCaja($ini, $fin, true);

        return view('reportes.caja.detallado', $reporteData);
    }

    public function generalPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $reporteData = $this->reporteCajaService->obtenerDatosReporteCaja($ini, $fin, false);

        $pdf = Pdf::loadView('reportes.caja.general_pdf', $reporteData)->setPaper('a4', 'portrait');

        return $pdf->stream("reporte_caja_{$ini->format('Ymd')}_{$fin->format('Ymd')}.pdf");
    }
}
