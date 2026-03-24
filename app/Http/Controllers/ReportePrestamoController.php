<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Formulacion;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Exports\FormulacionesFechaExport;
use App\Exports\FormulacionesRangoExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class ReportePrestamoController extends Controller
{
    public function __construct(){
        $this->middleware('can:prestamos_list')->only(
                ['index', 'prestamoAPendiente','imprimirPrestamoAPediente','prestamoDePendiente','imprimirPrestamoDePendiente']);
    }

    public function index(Request $request)
    {
        // Lógica para generar el reporte de compras
        return view('reportes.prestamos');
    }

    public function prestamoAPendiente(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $clienteEmpresaId = $request->input('cliente_destino_id');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        /* =========================
        SUBCONSULTA: PRESTADO (PA)
        ==========================*/
        $prestado = DB::table('prestamo_detalles as d')
            ->join('prestamos as p', 'p.id', '=', 'd.prestamo_id')
            ->where('p.movimiento_tipo', 'PA')
            ->select(
                'p.id as prestamo_id',
                'p.user_nombre',
                'p.fecha_prestamo',
                'p.cliente_destino_id',
                'p.comprobante_tipo_codigo',
                'p.serie',
                'p.correlativo',
                'd.producto_id',
                'd.producto_nombre',
                'd.unidad_nombre',
                'd.producto_empaque',
                DB::raw('SUM(d.cantidad) AS cantidad_prestada')
            )
            ->where('p.estado', '!=', 'anulada')
            ->groupBy(
                'p.id',
                'p.user_nombre',
                'p.fecha_prestamo',
                'p.cliente_destino_id',
                'p.comprobante_tipo_codigo',
                'p.serie',
                'p.correlativo',
                'd.producto_id',
                'd.producto_nombre',
                'd.unidad_nombre',
                'd.producto_empaque'
            );

        /* =========================
        SUBCONSULTA: DEVUELTO (DD)
        ==========================*/
        $devuelto = DB::table('prestamos as pd')
            ->join('prestamo_detalles as dd', 'pd.id', '=', 'dd.prestamo_id')
            ->where('pd.movimiento_tipo', 'DD')
            ->select(
                'pd.prestamo_referencia_id',
                'dd.producto_id',
                DB::raw('SUM(dd.cantidad) AS cantidad_devuelta')
            )
            ->where('pd.estado', '!=', 'anulada')
            ->groupBy(
                'pd.prestamo_referencia_id',
                'dd.producto_id'
            );

        /* =========================
        CONSULTA FINAL
        ==========================*/
        $reportes = DB::query()
            ->fromSub($prestado, 'pa')
            ->leftJoinSub($devuelto, 'dd', function ($join) {
                $join->on('dd.prestamo_referencia_id', '=', 'pa.prestamo_id')
                    ->on('dd.producto_id', '=', 'pa.producto_id');
            })
            ->join('clientes as c', 'c.id', '=', 'pa.cliente_destino_id')
            ->where('pa.cliente_destino_id', $clienteEmpresaId)
            ->when($fechaInicio && $fechaFin, fn($q) => 
                $q->whereBetween(DB::raw('DATE(pa.fecha_prestamo)'), [$fechaInicio, $fechaFin])
            )
            ->select([
                'pa.prestamo_id as id',
                'pa.user_nombre',
                'pa.fecha_prestamo',
                'pa.cliente_destino_id',
                'c.razon_social as cliente_nombre',
                'pa.comprobante_tipo_codigo',
                'pa.serie',
                'pa.correlativo',
                'pa.producto_id',
                'pa.producto_nombre',
                'pa.unidad_nombre',
                'pa.producto_empaque',
                'pa.cantidad_prestada',
                DB::raw('COALESCE(dd.cantidad_devuelta, 0) AS cantidad_devuelta'),
                DB::raw('(pa.cantidad_prestada - COALESCE(dd.cantidad_devuelta, 0)) AS saldo')
            ])
            // ->where('pa.estado', '!=', 'anulada')
            ->orderBy('pa.fecha_prestamo', 'desc')
            ->get();

        return view('reportes.prestamos.prestamos_a', compact(
            'reportes',
            'fechaInicio',
            'fechaFin',
            'clienteEmpresaId'
        ));
    }

    public function imprimirPrestamoAPendiente(Request $request)
    {

        $clienteEmpresaId = $request->input('cliente_destino_id');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        /* =========================
        SUBCONSULTA: PRESTADO (PA)
        ==========================*/
        $prestado = DB::table('prestamo_detalles as d')
            ->join('prestamos as p', 'p.id', '=', 'd.prestamo_id')
            ->where('p.movimiento_tipo', 'PA')
            ->select(
                'p.id as prestamo_id',
                'p.user_nombre',
                'p.fecha_prestamo',
                'p.cliente_destino_id',
                'p.comprobante_tipo_codigo',
                'p.serie',
                'p.correlativo',
                'd.producto_id',
                'd.producto_nombre',
                'd.unidad_nombre',
                'd.producto_empaque',
                DB::raw('SUM(d.cantidad) AS cantidad_prestada')
            )
            ->where('p.estado', '!=', 'anulada')
            ->groupBy(
                'p.id',
                'p.user_nombre',
                'p.fecha_prestamo',
                'p.cliente_destino_id',
                'p.comprobante_tipo_codigo',
                'p.serie',
                'p.correlativo',
                'd.producto_id',
                'd.producto_nombre',
                'd.unidad_nombre',
                'd.producto_empaque'
            );

        /* =========================
        SUBCONSULTA: DEVUELTO (DD)
        ==========================*/
        $devuelto = DB::table('prestamos as pd')
            ->join('prestamo_detalles as dd', 'pd.id', '=', 'dd.prestamo_id')
            ->where('pd.movimiento_tipo', 'DD')
            ->select(
                'pd.prestamo_referencia_id',
                'dd.producto_id',
                DB::raw('SUM(dd.cantidad) AS cantidad_devuelta')
            )
            ->where('pd.estado', '!=', 'anulada')
            ->groupBy(
                'pd.prestamo_referencia_id',
                'dd.producto_id'
            );

        /* =========================
        CONSULTA FINAL
        ==========================*/
        $reportes = DB::query()
            ->fromSub($prestado, 'pa')
            ->leftJoinSub($devuelto, 'dd', function ($join) {
                $join->on('dd.prestamo_referencia_id', '=', 'pa.prestamo_id')
                    ->on('dd.producto_id', '=', 'pa.producto_id');
            })
            ->join('clientes as c', 'c.id', '=', 'pa.cliente_destino_id')
            ->where('pa.cliente_destino_id', $clienteEmpresaId)
            ->when($fechaInicio && $fechaFin, fn($q) => 
                $q->whereBetween(DB::raw('DATE(pa.fecha_prestamo)'), [$fechaInicio, $fechaFin])
            )
            ->select([
                'pa.prestamo_id as id',
                'pa.user_nombre',
                'pa.fecha_prestamo',
                'pa.cliente_destino_id',
                'c.razon_social as cliente_nombre',
                'pa.comprobante_tipo_codigo',
                'pa.serie',
                'pa.correlativo',
                'pa.producto_id',
                'pa.producto_nombre',
                'pa.unidad_nombre',
                'pa.producto_empaque',
                'pa.cantidad_prestada',
                DB::raw('COALESCE(dd.cantidad_devuelta, 0) AS cantidad_devuelta'),
                DB::raw('(pa.cantidad_prestada - COALESCE(dd.cantidad_devuelta, 0)) AS saldo')
            ])
            // ->where('pa.estado', '!=', 'anulada')
            ->orderBy('pa.fecha_prestamo', 'desc')
            ->get();
        
        $pdf = Pdf::loadView(
            'reportes.prestamos.prestamos_a_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin', 'clienteEmpresaId')
        )->setPaper('letter', 'portrait')
        ->setOptions([
            'defaultFont' => 'Courier',
        ]);

        return $pdf->stream('prestamos_a'.$clienteEmpresaId.'_fecha_' . now()->format('Ymd_His') . '.pdf');
    }

    public function prestamoDePendiente(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $clienteEmpresaId = $request->input('cliente_origen_id');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        /* =========================
        SUBCONSULTA: PRESTADO (PD)
        ==========================*/
        $prestado = DB::table('prestamo_detalles as d')
            ->join('prestamos as p', 'p.id', '=', 'd.prestamo_id')
            ->where('p.movimiento_tipo', 'PD')
            ->select(
                'p.id as prestamo_id',
                'p.user_nombre',
                'p.fecha_prestamo',
                'p.cliente_origen_id',
                'p.comprobante_tipo_codigo',
                'p.serie',
                'p.correlativo',
                'd.producto_id',
                'd.producto_nombre',
                'd.unidad_nombre',
                'd.producto_empaque',
                DB::raw('SUM(d.cantidad) AS cantidad_prestada')
            )
            ->where('p.estado', '!=', 'anulada')
            ->groupBy(
                'p.id',
                'p.user_nombre',
                'p.fecha_prestamo',
                'p.cliente_origen_id',
                'p.comprobante_tipo_codigo',
                'p.serie',
                'p.correlativo',
                'd.producto_id',
                'd.producto_nombre',
                'd.unidad_nombre',
                'd.producto_empaque'
            );

        /* =========================
        SUBCONSULTA: DEVUELTO (DA)
        ==========================*/
        $devuelto = DB::table('prestamos as pd')
            ->join('prestamo_detalles as dd', 'pd.id', '=', 'dd.prestamo_id')
            ->where('pd.movimiento_tipo', 'DA')
            ->select(
                'pd.prestamo_referencia_id',
                'dd.producto_id',
                DB::raw('SUM(dd.cantidad) AS cantidad_devuelta')
            )
            ->where('pd.estado', '!=', 'anulada')
            ->groupBy(
                'pd.prestamo_referencia_id',
                'dd.producto_id'
            );

        /* =========================
        CONSULTA FINAL
        ==========================*/
        $reportes = DB::query()
            ->fromSub($prestado, 'pa')
            ->leftJoinSub($devuelto, 'dd', function ($join) {
                $join->on('dd.prestamo_referencia_id', '=', 'pa.prestamo_id')
                    ->on('dd.producto_id', '=', 'pa.producto_id');
            })
            ->join('clientes as c', 'c.id', '=', 'pa.cliente_origen_id')
            ->where('pa.cliente_origen_id', $clienteEmpresaId)
            ->when($fechaInicio && $fechaFin, fn($q) => 
                $q->whereBetween(DB::raw('DATE(pa.fecha_prestamo)'), [$fechaInicio, $fechaFin])
            )
            ->select([
                'pa.prestamo_id as id',
                'pa.user_nombre',
                'pa.fecha_prestamo',
                'pa.cliente_origen_id',
                'c.razon_social as cliente_nombre',
                'pa.comprobante_tipo_codigo',
                'pa.serie',
                'pa.correlativo',
                'pa.producto_id',
                'pa.producto_nombre',
                'pa.unidad_nombre',
                'pa.producto_empaque',
                'pa.cantidad_prestada',
                DB::raw('COALESCE(dd.cantidad_devuelta, 0) AS cantidad_devuelta'),
                DB::raw('(pa.cantidad_prestada - COALESCE(dd.cantidad_devuelta, 0)) AS saldo')
            ])
            // ->where('pa.estado', '!=', 'anulada')
            ->orderBy('pa.fecha_prestamo', 'desc')
            ->get();

        return view('reportes.prestamos.prestamos_de', compact(
            'reportes',
            'fechaInicio',
            'fechaFin',
            'clienteEmpresaId'
        ));
    }

    public function imprimirPrestamoDePendiente(Request $request)
    {

        $clienteEmpresaId = $request->input('cliente_origen_id');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

         /* =========================
        SUBCONSULTA: PRESTADO (PD)
        ==========================*/
        $prestado = DB::table('prestamo_detalles as d')
            ->join('prestamos as p', 'p.id', '=', 'd.prestamo_id')
            ->where('p.movimiento_tipo', 'PD')
            ->select(
                'p.id as prestamo_id',
                'p.user_nombre',
                'p.fecha_prestamo',
                'p.cliente_origen_id',
                'p.comprobante_tipo_codigo',
                'p.serie',
                'p.correlativo',
                'd.producto_id',
                'd.producto_nombre',
                'd.unidad_nombre',
                'd.producto_empaque',
                DB::raw('SUM(d.cantidad) AS cantidad_prestada')
            )
            ->where('p.estado', '!=', 'anulada')
            ->groupBy(
                'p.id',
                'p.user_nombre',
                'p.fecha_prestamo',
                'p.cliente_origen_id',
                'p.comprobante_tipo_codigo',
                'p.serie',
                'p.correlativo',
                'd.producto_id',
                'd.producto_nombre',
                'd.unidad_nombre',
                'd.producto_empaque'
            );

        /* =========================
        SUBCONSULTA: DEVUELTO (DA)
        ==========================*/
        $devuelto = DB::table('prestamos as pd')
            ->join('prestamo_detalles as dd', 'pd.id', '=', 'dd.prestamo_id')
            ->where('pd.movimiento_tipo', 'DA')
            ->select(
                'pd.prestamo_referencia_id',
                'dd.producto_id',
                DB::raw('SUM(dd.cantidad) AS cantidad_devuelta')
            )
            ->where('pd.estado', '!=', 'anulada')
            ->groupBy(
                'pd.prestamo_referencia_id',
                'dd.producto_id'
            );

        /* =========================
        CONSULTA FINAL
        ==========================*/
        $reportes = DB::query()
            ->fromSub($prestado, 'pa')
            ->leftJoinSub($devuelto, 'dd', function ($join) {
                $join->on('dd.prestamo_referencia_id', '=', 'pa.prestamo_id')
                    ->on('dd.producto_id', '=', 'pa.producto_id');
            })
            ->join('clientes as c', 'c.id', '=', 'pa.cliente_origen_id')
            ->where('pa.cliente_origen_id', $clienteEmpresaId)
            ->when($fechaInicio && $fechaFin, fn($q) => 
                $q->whereBetween(DB::raw('DATE(pa.fecha_prestamo)'), [$fechaInicio, $fechaFin])
            )
            ->select([
                'pa.prestamo_id as id',
                'pa.user_nombre',
                'pa.fecha_prestamo',
                'pa.cliente_origen_id',
                'c.razon_social as cliente_nombre',
                'pa.comprobante_tipo_codigo',
                'pa.serie',
                'pa.correlativo',
                'pa.producto_id',
                'pa.producto_nombre',
                'pa.unidad_nombre',
                'pa.producto_empaque',
                'pa.cantidad_prestada',
                DB::raw('COALESCE(dd.cantidad_devuelta, 0) AS cantidad_devuelta'),
                DB::raw('(pa.cantidad_prestada - COALESCE(dd.cantidad_devuelta, 0)) AS saldo')
            ])
            // ->where('pa.estado', '!=', 'anulada')
            ->orderBy('pa.fecha_prestamo', 'desc')
            ->get();
        
        $pdf = Pdf::loadView(
            'reportes.prestamos.prestamos_de_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin', 'clienteEmpresaId')
        )->setPaper('letter', 'portrait')
        ->setOptions([
            'defaultFont' => 'Courier',
        ]);

        return $pdf->stream('prestamos_de'.$clienteEmpresaId.'_fecha_' . now()->format('Ymd_His') . '.pdf');
    }
}
