<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteRentabilidadController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:rentabilidad_report')->only(['index', 'rentabilidadVentasFechas', 'imprimirRentabilidadVentasFechas', 'rentabilidadDetalleVentasFechas', 'imprimirRentabilidadDetalleVentasFechas', 'rentabilidadProductoVentasFechas', 'imprimirRentabilidadProductoVentasFechas', 'rentabilidadProductosFechas', 'imprimirRentabilidadProductosFechas']);
    }

    public function index(Request $request)
    {
        // Lógica para generar el reporte de compras
        return view('reportes.rentabilidad');
    }

    public function rentabilidadVentasFechas(Request $request)
    {
        if (! $request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $fechaInicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fechaFin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $query = Venta::query()
            ->select([
                'ventas.id',
                'ventas.fecha_venta',
                'ventas.comprobante_tipo_codigo',
                'ventas.serie',
                'ventas.correlativo',
                'ventas.total',
                'ventas.acuenta',
                'ventas.rentabilidad',
                'ventas.items',
            ])
            ->where('estado', '!=', 'anulada')
            ->whereBetween('ventas.fecha_venta', [$fechaInicio, $fechaFin])
            ->with(['detalles' => function ($q) {
                $q->select([
                    'id',
                    'venta_id', // ✅ obligatorio para relacionar
                    'producto_id',
                    'producto_nombre',
                    'cantidad',
                    'precio_unitario',
                    'total as importe',
                    'costo_unitario',
                    'costo_total as valor',
                    'rentabilidad',
                ]);
            },
                'detalles.producto:id,linea_id,nombre',
                'detalles.producto.linea:id,nombre',
            ]);

        $reportes = $query->get();

        return view('reportes.rentabilidad.ventas_fechas', compact('reportes', 'fechaInicio', 'fechaFin'));
    }

    public function imprimirRentabilidadVentasFechas(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $fechaInicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fechaFin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $query = Venta::query()
            ->select([
                'ventas.id',
                'ventas.fecha_venta',
                'ventas.comprobante_tipo_codigo',
                'ventas.serie',
                'ventas.correlativo',
                'ventas.total',
                'ventas.acuenta',
                'ventas.rentabilidad',
                'ventas.items',
            ])
            ->where('estado', '!=', 'anulada')
            ->whereBetween('ventas.fecha_venta', [$fechaInicio, $fechaFin])
            ->with(['detalles' => function ($q) {
                $q->select([
                    'id',
                    'venta_id', // ✅ obligatorio para relacionar
                    'producto_id',
                    'producto_nombre',
                    'cantidad',
                    'precio_unitario',
                    'costo_unitario',
                    'costo_total',
                    'rentabilidad',
                ]);
            },
                'detalles.producto:id,linea_id,nombre',
                'detalles.producto.linea:id,nombre',
            ]);

        $reportes = $query->get();

        $pdf = Pdf::loadView(
            'reportes.rentabilidad.ventas_fechas_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'landscape')
            ->setOptions([
                'defaultFont' => 'Courier',
            ]);

        return $pdf->stream('rentabilidad_ventas_fechas.pdf');
    }

    public function rentabilidadDetalleVentasFechas(Request $request)
    {
        if (! $request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $fechaInicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fechaFin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $query = Venta::query()
            ->select([
                'ventas.id',
                'ventas.fecha_venta',
                'ventas.comprobante_tipo_codigo',
                'ventas.serie',
                'ventas.correlativo',
                'ventas.total',
                'ventas.acuenta',
                'ventas.rentabilidad',
                'ventas.items',
            ])
            ->where('estado', '!=', 'anulada')
            ->whereBetween('ventas.fecha_venta', [$fechaInicio, $fechaFin])
            ->with(['detalles' => function ($q) {
                $q->select([
                    'id',
                    'venta_id', // ✅ obligatorio para relacionar
                    'producto_id',
                    'unidad_codigo',
                    'producto_nombre',
                    'cantidad',
                    'precio_unitario',
                    'costo_unitario',
                    'costo_total',
                    'rentabilidad',
                ]);
            },
                'detalles.producto:id,linea_id,nombre',
                'detalles.producto.linea:id,nombre',
            ]);

        $reportes = $query->get();

        return view('reportes.rentabilidad.ventas_detalles_fechas', compact('reportes', 'fechaInicio', 'fechaFin'));
    }

    public function imprimirRentabilidadDetalleVentasFechas(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $fechaInicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fechaFin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $query = Venta::query()
            ->select([
                'ventas.id',
                'ventas.fecha_venta',
                'ventas.comprobante_tipo_codigo',
                'ventas.serie',
                'ventas.correlativo',
                'ventas.total',
                'ventas.acuenta',
                'ventas.rentabilidad',
                'ventas.items',
            ])
            ->where('estado', '!=', 'anulada')
            ->whereBetween('ventas.fecha_venta', [$fechaInicio, $fechaFin])
            ->with(['detalles' => function ($q) {
                $q->select([
                    'id',
                    'venta_id', // ✅ obligatorio para relacionar
                    'producto_id',
                    'unidad_codigo',
                    'producto_nombre',
                    'cantidad',
                    'precio_unitario',
                    'total as importe',
                    'costo_unitario',
                    'costo_total',
                    'rentabilidad',
                ]);
            },
                'detalles.producto:id,linea_id,nombre',
                'detalles.producto.linea:id,nombre',
            ]);

        $reportes = $query->get();

        $pdf = Pdf::loadView(
            'reportes.rentabilidad.ventas_detalles_fechas_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'landscape')
            ->setOptions([
                'defaultFont' => 'Courier',
            ]);

        return $pdf->stream('rentabilidad_ventas_fechas.pdf');
    }

    public function rentabilidadProductosFechas(Request $request)
    {
        if (! $request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $fechaInicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fechaFin = Carbon::parse($data['fecha_fin'])->endOfDay();

        // costo total ajustado por empaque (misma lógica que tu calculateDetail)
        $costoAjustadoExpr = '
            ROUND(
                (
                    (COALESCE(p.costo_unitario, 0) / COALESCE(NULLIF(p.empaque, 0), 1))
                    * COALESCE(NULLIF(vd.producto_empaque, 0), 1)
                )
                * COALESCE(vd.cantidad, 0)
            , 2)
        ';

        $reportes = DB::table('ventas as v')
            ->join('venta_detalles as vd', 'vd.venta_id', '=', 'v.id')
            ->join('productos as p', 'p.id', '=', 'vd.producto_id')
            ->join('lineas as l', 'l.id', '=', 'p.linea_id')
            ->whereBetween('v.fecha_venta', [$fechaInicio, $fechaFin])
            ->selectRaw("
                vd.producto_id,
                COALESCE(vd.producto_nombre, p.nombre) as producto_nombre,
                COALESCE(l.nombre, '') as producto_linea_nombre,

                COALESCE(NULLIF(p.empaque, 0), 1) as empaque_producto,

                SUM(COALESCE(vd.cantidad, 0)) as cantidad,
                SUM(COALESCE(vd.salida_kg, 0)) as salida_kg,
                SUM(COALESCE(vd.salida_saco, 0)) as salida_saco,

                SUM(COALESCE(vd.total, 0)) as importe,
                SUM(COALESCE(vd.costo_total, 0)) as costo,
                SUM(COALESCE(vd.rentabilidad, 0)) as valor,

                CASE
                    WHEN SUM(COALESCE(vd.total, 0)) > 0 THEN
                        (SUM(COALESCE(vd.rentabilidad, 0)) / SUM(COALESCE(vd.total, 0))) * 100
                    ELSE 0
                END as rentab_pct,

                GROUP_CONCAT(
                    DISTINCT COALESCE(NULLIF(vd.producto_empaque, 0), 1)
                    ORDER BY COALESCE(NULLIF(vd.producto_empaque, 0), 1)
                    SEPARATOR ', '
                ) as empaques_vendidos
            ")
            ->where('estado', '!=', 'anulada')
            ->groupBy([
                'vd.producto_id',
                DB::raw('COALESCE(vd.producto_nombre, p.nombre)'),
                DB::raw("COALESCE(l.nombre, '')"),
                'p.empaque',
                'p.costo_unitario',
            ])
            ->orderBy('producto_nombre')
            ->get();

        return view('reportes.rentabilidad.productos_fechas', compact('reportes', 'fechaInicio', 'fechaFin'));
    }

    public function imprimirRentabilidadProductosFechas(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $fechaInicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fechaFin = Carbon::parse($data['fecha_fin'])->endOfDay();

        // costo total ajustado por empaque (misma lógica que tu calculateDetail)
        $costoAjustadoExpr = '
            ROUND(
                (
                    (COALESCE(p.costo_unitario, 0) / COALESCE(NULLIF(p.empaque, 0), 1))
                    * COALESCE(NULLIF(vd.producto_empaque, 0), 1)
                )
                * COALESCE(vd.cantidad, 0)
            , 2)
        ';

        $reportes = DB::table('ventas as v')
            ->join('venta_detalles as vd', 'vd.venta_id', '=', 'v.id')
            ->join('productos as p', 'p.id', '=', 'vd.producto_id')
            ->join('lineas as l', 'l.id', '=', 'p.linea_id')
            ->whereBetween('v.fecha_venta', [$fechaInicio, $fechaFin])
            ->selectRaw("
                vd.producto_id,
                COALESCE(vd.producto_nombre, p.nombre) as producto_nombre,
                COALESCE(l.nombre, '') as producto_linea_nombre,

                COALESCE(NULLIF(p.empaque, 0), 1) as empaque_producto,

                SUM(COALESCE(vd.cantidad, 0)) as cantidad,
                SUM(COALESCE(vd.salida_kg, 0)) as salida_kg,
                SUM(COALESCE(vd.salida_saco, 0)) as salida_saco,

                SUM(COALESCE(vd.total, 0)) as importe,
                SUM(COALESCE(vd.costo_total, 0)) as costo,
                SUM(COALESCE(vd.rentabilidad, 0)) as valor,

                CASE
                    WHEN SUM(COALESCE(vd.total, 0)) > 0 THEN
                        (SUM(COALESCE(vd.rentabilidad, 0)) / SUM(COALESCE(vd.total, 0))) * 100
                    ELSE 0
                END as rentab_pct,

                GROUP_CONCAT(
                    DISTINCT COALESCE(NULLIF(vd.producto_empaque, 0), 1)
                    ORDER BY COALESCE(NULLIF(vd.producto_empaque, 0), 1)
                    SEPARATOR ', '
                ) as empaques_vendidos
            ")
            ->where('estado', '!=', 'anulada')
            ->groupBy([
                'vd.producto_id',
                DB::raw('COALESCE(vd.producto_nombre, p.nombre)'),
                DB::raw("COALESCE(l.nombre, '')"),
                'p.empaque',
                'p.costo_unitario',
            ])
            ->orderBy('producto_nombre')
            ->get();

        $pdf = Pdf::loadView(
            'reportes.rentabilidad.productos_fechas_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'Courier',
            ]);

        return $pdf->stream('rentabilidad_productos_fechas.pdf');
    }

    public function rentabilidadProductosVentasFechas(Request $request)
    {
        if (! $request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $fechaInicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fechaFin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $ventasFiltradas = DB::table('ventas')
            ->select('id', 'pago_forma_codigo')
            ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin])
            ->where('estado', '!=', 'anulada');

        $condContado = "vf.pago_forma_codigo = '1'";

        $reportes = DB::query()
            ->fromSub($ventasFiltradas, 'vf')
            ->join('venta_detalles as vd', 'vd.venta_id', '=', 'vf.id')
            ->join('productos as p', 'p.id', '=', 'vd.producto_id')
            ->join('lineas as l', 'l.id', '=', 'p.linea_id')
            ->selectRaw("
                p.linea_id,
                l.nombre as linea_nombre,
                p.id as producto_id,
                p.nombre as producto_nombre,
                COALESCE(NULLIF(p.empaque, 0), 1) as empaque_producto,

                SUM(CASE WHEN $condContado THEN COALESCE(vd.rentabilidad, 0) ELSE 0 END) as contado,
                SUM(CASE WHEN NOT($condContado) THEN COALESCE(vd.rentabilidad, 0) ELSE 0 END) as credito,
                SUM(COALESCE(vd.rentabilidad, 0)) as total
            ")
            ->groupBy('p.linea_id', 'l.nombre', 'p.id', 'p.nombre', 'p.empaque')
            ->orderBy('l.nombre')
            ->orderBy('p.nombre')
            ->get();

        return view('reportes.rentabilidad.productos_ventas_fechas', compact('reportes', 'fechaInicio', 'fechaFin'));
    }

    public function ImprimirRentabilidadProductosVentasFechas(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $fechaInicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fechaFin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $ventasFiltradas = DB::table('ventas')
            ->select('id', 'pago_forma_codigo')
            ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin])
            ->where('estado', '!=', 'anulada');

        $condContado = "vf.pago_forma_codigo = '1'";

        $reportes = DB::query()
            ->fromSub($ventasFiltradas, 'vf')
            ->join('venta_detalles as vd', 'vd.venta_id', '=', 'vf.id')
            ->join('productos as p', 'p.id', '=', 'vd.producto_id')
            ->join('lineas as l', 'l.id', '=', 'p.linea_id')
            ->selectRaw("
                p.linea_id,
                l.nombre as linea_nombre,
                p.id as producto_id,
                p.nombre as producto_nombre,
                COALESCE(NULLIF(p.empaque, 0), 1) as empaque_producto,

                SUM(CASE WHEN $condContado THEN COALESCE(vd.rentabilidad, 0) ELSE 0 END) as contado,
                SUM(CASE WHEN NOT($condContado) THEN COALESCE(vd.rentabilidad, 0) ELSE 0 END) as credito,
                SUM(COALESCE(vd.rentabilidad, 0)) as total
            ")
            ->groupBy('p.linea_id', 'l.nombre', 'p.id', 'p.nombre', 'p.empaque')
            ->orderBy('l.nombre')
            ->orderBy('p.nombre')
            ->get();

        $pdf = Pdf::loadView(
            'reportes.rentabilidad.productos_ventas_fechas_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'Courier',
            ]);

        return $pdf->stream('rentabilidad_productos_ventas.pdf');
    }
}
