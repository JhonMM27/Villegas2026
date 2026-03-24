<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CompraDetalle;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\Carbon;

use App\Exports\VentasDocumentosExport;
use App\Exports\VentasDocumentosTipoExport;
use App\Exports\VentasAcumuladasProductoExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class ReporteVentaController extends Controller
{
    public function __construct(){
        $this->middleware('can:ventas_report')->only(['index','ventasEmitidas','exportarVentasEmitidas','ventasTipoDocumentos','exportarVentasTipoDocumentos', 'ventasAcumuladasProducto','exportarVentasAcumuladasProducto','ventasagrupadasProducto','imprimirVentasEmitidas','imprimirVentasTipoDocumentos','imprimirVentasAcumuladasProducto','imprimirVentasAgrupadasProducto','ventasPor']);

        $this->middleware('can:dashboard_estadisticas')->only(['topProductosMes','ventasUltimos15','ventasUltimos15Continuo']);
    }

    public function index(Request $request)
    {
        // Lógica para generar el reporte de compras
        $vendedores=User::select('id','name')->where('activo',1)->get();
        return view('reportes.ventas', compact('vendedores'));
    }

    public function ventasEmitidas(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $vendedorId  = $request->input('vendedor_id');

        $query = Venta::query()
            ->selectRaw('
                ventas.id,
                ventas.fecha_venta,
                ventas.comprobante_tipo_codigo,
                ventas.serie,
                ventas.correlativo,
                ventas.pago_forma_nombre,
                ventas.total,
                ventas.acuenta,
                ventas.cliente_id,
                ventas.cliente_nombre,
                ventas.items
            ')
            ->where('estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('ventas.fecha_venta', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        if (!empty($vendedorId)) { // ✅ si no viene, es "Todos"
            $query->where('ventas.user_id', (int)$vendedorId); // usa tu campo real
        }

        $reportes = $query            
            ->orderBy('ventas.correlativo', 'asc')    // luego correlativo
            ->orderBy('ventas.fecha_venta', 'asc')   // primero por fecha
            ->get();

        return view('reportes.ventas.documentos_emitidos', compact('reportes', 'fechaInicio', 'fechaFin'));
    }

    public function exportarVentasEmitidas(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
         $vendedorId  = $request->input('vendedor_id');

        $fileName = 'ventas_documentos_fecha_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new VentasDocumentosExport($fechaInicio, $fechaFin, $vendedorId),
            $fileName
        );
    }

    public function imprimirVentasEmitidas(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $vendedorId  = $request->input('vendedor_id');

        $query = Venta::query()
            ->selectRaw('
                ventas.id,
                ventas.fecha_venta,
                ventas.comprobante_tipo_codigo,
                ventas.serie,
                ventas.correlativo,
                ventas.pago_forma_nombre,
                ventas.total,
                ventas.acuenta,
                ventas.cliente_id,
                ventas.cliente_nombre,
                ventas.items
            ')
            ->where('estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('ventas.fecha_venta', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }
        if (!empty($vendedorId)) { // ✅ si no viene, es "Todos"
            $query->where('ventas.user_id', (int)$vendedorId); // usa tu campo real
        }

        $reportes = $query            
            ->orderBy('ventas.correlativo', 'asc')    // luego correlativo
            ->orderBy('ventas.fecha_venta', 'asc')   // primero por fecha
            ->get();

        $pdf = Pdf::loadView(
            'reportes.ventas.ventas_emitidas_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'portrait')
        ->setOptions([
            'defaultFont' => 'Courier',
        ]);

        return $pdf->stream('ventas_emitidas.pdf');
    }

    public function ventasTipoDocumentos(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $tipoDoc     = $request->input('tipo_documento');

        $query = Venta::query()
            ->selectRaw('
                ventas.id,
                ventas.fecha_venta,
                ventas.comprobante_tipo_codigo,
                ventas.serie,
                ventas.correlativo,
                ventas.pago_forma_nombre,
                ventas.total,
                ventas.acuenta,
                ventas.cliente_id,
                ventas.cliente_nombre,
                ventas.items
            ')
            ->where('estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('ventas.fecha_venta', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        if (!empty($tipoDoc)) {
            $query->where('ventas.comprobante_tipo_codigo', $tipoDoc);
        }

        $reportes = $query            
            ->orderBy('ventas.correlativo', 'asc')    // luego correlativo
            ->orderBy('ventas.fecha_venta', 'asc')   // primero por fecha
            ->get();

        return view('reportes.ventas.documentos_emitidos', compact('reportes', 'fechaInicio', 'fechaFin'));
    }

    public function exportarVentasTipoDocumentos(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $tipoDoc     = $request->input('tipo_documento');

        $fileName = 'ventas_tipos_documentos_fecha_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new VentasDocumentosTipoExport($fechaInicio, $fechaFin, $tipoDoc),
            $fileName
        );
    }

    public function imprimirVentasTipoDocumentos(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $tipoDoc     = $request->input('tipo_documento');

        switch ($tipoDoc) {
            case '01':
                $nomDoc = 'Factura';
                break;
            case '03':
                $nomDoc = 'Boleta';
                break;
            case 'NP':
                $nomDoc = 'NP';
                break;
            default:
                $nomDoc = 'Todos';
        }

        $query = Venta::query()
            ->selectRaw('
                ventas.id,
                ventas.fecha_venta,
                ventas.comprobante_tipo_codigo,
                ventas.serie,
                ventas.correlativo,
                ventas.pago_forma_nombre,
                ventas.total,
                ventas.acuenta,
                ventas.cliente_id,
                ventas.cliente_nombre,
                ventas.items
            ')
            ->where('estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('ventas.fecha_venta', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        if (!empty($tipoDoc)) {
            $query->where('ventas.comprobante_tipo_codigo', $tipoDoc);
        }

        $reportes = $query            
            ->orderBy('ventas.correlativo', 'asc')    // luego correlativo
            ->orderBy('ventas.fecha_venta', 'asc')   // primero por fecha
            ->get();

        $pdf = Pdf::loadView(
            'reportes.ventas.documentos_emitidos_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin', 'nomDoc')
        )->setPaper('letter', 'portrait')
        ->setOptions([
            'defaultFont' => 'Courier',
        ]);

        return $pdf->stream('documentos_emitidos.pdf');
    }

    public function ventasAcumuladasProducto(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = VentaDetalle::query()
            ->selectRaw('
                venta_detalles.producto_id,
                venta_detalles.producto_nombre,
                venta_detalles.producto_empaque,
                lineas.nombre AS linea,
                SUM(venta_detalles.cantidad) AS cantidad_total,
                AVG(venta_detalles.precio_unitario) AS precio_unitario,
                SUM(venta_detalles.total) AS importe_total,
                SUM(venta_detalles.cantidad * venta_detalles.producto_empaque) AS kg_total
            ')
            ->join('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
            ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
            ->join('lineas', 'productos.linea_id', '=', 'lineas.id')
            ->where('estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('ventas.fecha_venta', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay(),
            ]);
        }

        $reportes = $query
            ->groupBy(
                'venta_detalles.producto_id',
                'venta_detalles.producto_nombre',
                'venta_detalles.producto_empaque',
                'lineas.nombre'
            )
            ->orderBy('lineas.nombre')
            ->orderBy('venta_detalles.producto_nombre')
            ->get();

        return view(
            'reportes.ventas.ventas_acumuladas_producto',
            compact('reportes', 'fechaInicio', 'fechaFin')
        );
    }

    public function exportarVentasAcumuladasProducto(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $fileName = 'ventas_acumuladas_producto_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new VentasAcumuladasProductoExport($fechaInicio, $fechaFin),
            $fileName
        );
    }

    public function imprimirVentasAcumuladasProducto(Request $request)
    {
       $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin'    => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();

        // Subquery: ventas del rango
        $ventasIds = DB::table('ventas')
            ->select('id')
            ->whereBetween('fecha_venta', [$ini, $fin]);

        $reportes = VentaDetalle::query()
            ->joinSub($ventasIds, 'vx', function ($join) {
                $join->on('vx.id', '=', 'venta_detalles.venta_id');
            })
            ->join('ventas as v', 'v.id', '=', 'venta_detalles.venta_id')
            ->leftJoin('productos as p', 'p.id', '=', 'venta_detalles.producto_id')
            ->select([
                'venta_detalles.producto_id',
                'venta_detalles.producto_nombre',
                DB::raw('IFNULL(p.empaque,0) as empaque_producto'),
            ])
            // Cantidad convertida acumulada
            ->selectRaw("
                SUM(
                    CASE
                        WHEN IFNULL(p.empaque,0) > 0 AND IFNULL(venta_detalles.producto_empaque,0) > 0
                            THEN (venta_detalles.cantidad * (venta_detalles.producto_empaque / p.empaque))
                        ELSE venta_detalles.cantidad
                    END
                ) as cantidad_total
            ")
            // Kg acumulado
            ->selectRaw("
                SUM(
                    (venta_detalles.cantidad *
                        CASE
                            WHEN IFNULL(venta_detalles.producto_empaque,0) > 0 THEN venta_detalles.producto_empaque
                            ELSE IFNULL(p.empaque,0)
                        END
                    )
                ) as kg_total
            ")
            // Importe acumulado
            ->selectRaw("SUM(venta_detalles.total) as importe_total")
            // (opcional) precio promedio ponderado: importe / cantidad_total
            ->selectRaw("
                (
                    SUM(venta_detalles.total) /
                    NULLIF(
                        SUM(
                            CASE
                                WHEN IFNULL(p.empaque,0) > 0 AND IFNULL(venta_detalles.producto_empaque,0) > 0
                                    THEN (venta_detalles.cantidad * (venta_detalles.producto_empaque / p.empaque))
                                ELSE venta_detalles.cantidad
                            END
                        ),
                    0)
                ) as precio_promedio
            ")
            ->where('estado', '!=', 'anulada')
            ->whereBetween('v.fecha_venta', [$ini, $fin]) // puedes dejarlo
            ->groupBy('venta_detalles.producto_id', 'venta_detalles.producto_nombre', 'p.empaque')
            ->orderBy('venta_detalles.producto_nombre')
            ->get();

        // Totales generales (si quieres al final del PDF)
        $totCantidad = (float) $reportes->sum('cantidad_total');
        $totKg       = (float) $reportes->sum('kg_total');
        $totImporte  = (float) $reportes->sum('importe_total');

        $pdf = Pdf::loadView(
            'reportes.ventas.ventas_acumuladas_producto_pdf',
            compact('reportes', 'ini', 'fin', 'totCantidad', 'totKg', 'totImporte')
        )->setPaper('a4', 'portrait');

        return $pdf->stream('ventas_acumuladas_producto.pdf');
    }

    public function ventasagrupadasProducto(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $ini = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->endOfDay();

        // 1) Subquery: solo ventas del rango (MUY importante si hay muchas ventas)
        $ventasIds = DB::table('ventas')
            ->select('id')
            ->whereBetween('fecha_venta', [$ini, $fin]);

        // 2) Traer detalles SOLO de esas ventas
        $reportes = VentaDetalle::query()
            ->joinSub($ventasIds, 'vx', function ($join) {
                $join->on('vx.id', '=', 'venta_detalles.venta_id');
            })
            ->join('ventas as v', 'v.id', '=', 'venta_detalles.venta_id')
            ->leftJoin('productos as p', 'p.id', '=', 'venta_detalles.producto_id')
            ->select([
                'venta_detalles.venta_id',
                'v.fecha_venta',
                'v.fecha_vencimiento',
                DB::raw('DATE_FORMAT(v.fecha_venta, "%d/%m/%Y") as fecha_venta_fmt'),
                'v.comprobante_tipo_codigo',
                'v.serie',
                'v.correlativo',

                'v.cliente_id',
                'v.cliente_nombre',

                'venta_detalles.producto_id',
                'venta_detalles.producto_nombre',

                'venta_detalles.cantidad',
                'venta_detalles.precio_unitario',
                'venta_detalles.total',

                DB::raw('IFNULL(venta_detalles.producto_empaque,0) as empaque_detalle'),
                DB::raw('IFNULL(p.empaque,0) as empaque_producto'),
                DB::raw("CONCAT(v.comprobante_tipo_codigo,' ',v.serie,'-',v.correlativo) as documento"),

                DB::raw("
                    (venta_detalles.cantidad *
                        CASE
                            WHEN IFNULL(venta_detalles.producto_empaque,0) > 0 THEN venta_detalles.producto_empaque
                            ELSE IFNULL(p.empaque,0)
                        END
                    ) as kg_detalle
                "),

                DB::raw("
                    CASE
                        WHEN IFNULL(p.empaque,0) > 0 AND IFNULL(venta_detalles.producto_empaque,0) > 0
                            THEN (venta_detalles.cantidad * (venta_detalles.producto_empaque / p.empaque))
                        ELSE venta_detalles.cantidad
                    END as cantidad_convertida
                "),
            ])
            ->where('estado', '!=', 'anulada')
            // OJO: ya filtramos ventas con joinSub, esto puede omitirse o dejarse por seguridad
            ->whereBetween('v.fecha_venta', [$ini, $fin])
            ->orderBy('venta_detalles.producto_id')   // más rápido que producto_nombre
            ->orderBy('venta_detalles.producto_nombre') // opcional si quieres orden “bonito”
            ->orderBy('v.fecha_venta')
            ->orderBy('venta_detalles.id')
            ->get();

        return view(
            'reportes.ventas.ventas_agrupadas_producto',
            compact('reportes', 'fechaInicio', 'fechaFin')
        );
    }

    public function imprimirVentasAgrupadasProducto(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin'    => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();

        // 1) Subquery: solo ventas del rango (MUY importante si hay muchas ventas)
        $ventasIds = DB::table('ventas')
            ->select('id')
            ->whereBetween('fecha_venta', [$ini, $fin]);

        // 2) Traer detalles SOLO de esas ventas
        $reportes = VentaDetalle::query()
            ->joinSub($ventasIds, 'vx', function ($join) {
                $join->on('vx.id', '=', 'venta_detalles.venta_id');
            })
            ->join('ventas as v', 'v.id', '=', 'venta_detalles.venta_id')
            ->leftJoin('productos as p', 'p.id', '=', 'venta_detalles.producto_id')
            ->select([
                'venta_detalles.venta_id',
                'v.fecha_venta',
                'v.fecha_vencimiento',
                DB::raw('DATE_FORMAT(v.fecha_venta, "%d/%m/%Y") as fecha_venta_fmt'),
                'v.comprobante_tipo_codigo',
                'v.serie',
                'v.correlativo',

                'v.cliente_id',
                'v.cliente_nombre',

                'venta_detalles.producto_id',
                'venta_detalles.producto_nombre',

                'venta_detalles.cantidad',
                'venta_detalles.precio_unitario',
                'venta_detalles.total',

                DB::raw('IFNULL(venta_detalles.producto_empaque,0) as empaque_detalle'),
                DB::raw('IFNULL(p.empaque,0) as empaque_producto'),
                DB::raw("CONCAT(v.comprobante_tipo_codigo,' ',v.serie,'-',v.correlativo) as documento"),

                DB::raw("
                    (venta_detalles.cantidad *
                        CASE
                            WHEN IFNULL(venta_detalles.producto_empaque,0) > 0 THEN venta_detalles.producto_empaque
                            ELSE IFNULL(p.empaque,0)
                        END
                    ) as kg_detalle
                "),

                DB::raw("
                    CASE
                        WHEN IFNULL(p.empaque,0) > 0 AND IFNULL(venta_detalles.producto_empaque,0) > 0
                            THEN (venta_detalles.cantidad * (venta_detalles.producto_empaque / p.empaque))
                        ELSE venta_detalles.cantidad
                    END as cantidad_convertida
                "),
            ])
            ->where('estado', '!=', 'anulada')
            // OJO: ya filtramos ventas con joinSub, esto puede omitirse o dejarse por seguridad
            ->whereBetween('v.fecha_venta', [$ini, $fin])
            ->orderBy('venta_detalles.producto_id')   // más rápido que producto_nombre
            ->orderBy('venta_detalles.producto_nombre') // opcional si quieres orden “bonito”
            ->orderBy('v.fecha_venta')
            ->orderBy('venta_detalles.id')
            ->get();

        $pdf = Pdf::loadView(
            'reportes.ventas.ventas_agrupada_producto_pdf',
            compact('reportes', 'ini', 'fin')
        )->setPaper('a4', 'portrait');

        return $pdf->stream('reporte_ventas_agrupadas_producto.pdf');
    }

    public function ventasPorEntregar(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

       $fechaInicio = Carbon::parse($request->input('fecha_inicio'))->startOfDay();
        $fechaFin    = Carbon::parse($request->input('fecha_fin'))->endOfDay();
        $vendedorId  = $request->input('vendedor_id');

        $query = DB::table('ventas as v')
            ->join('venta_detalles as d', 'd.venta_id', '=', 'v.id')
            ->whereBetween('v.fecha_venta', [$fechaInicio, $fechaFin])
            // ✅ Solo las líneas con faltante
            ->whereRaw('COALESCE(d.entregado,0) < COALESCE(d.cantidad,0)')
            // ✅ Filtro por vendedor (si viene)
            ->when(!empty($vendedorId), function ($q) use ($vendedorId) {
                $q->where('v.user_id', (int)$vendedorId);
            })
            ->selectRaw("
                v.id,
                v.fecha_venta,
                v.user_nombre,
                v.cliente_nombre,
                TRIM(CONCAT(COALESCE(v.comprobante_tipo_codigo,''), ' ', COALESCE(v.serie,''), '-', COALESCE(v.correlativo,''))) AS documento,
                v.total AS monto,
                GROUP_CONCAT(
                    CONCAT(
                        COALESCE(d.producto_nombre,''),
                        ' (', FORMAT((COALESCE(d.cantidad,0) - COALESCE(d.entregado,0)), 2), ')'
                    )
                    ORDER BY d.producto_nombre
                    SEPARATOR ' | '
                ) AS productos_por_entregar
            ")
            ->groupBy(
                'v.id',
                'v.fecha_venta',
                'v.user_nombre',
                'v.cliente_nombre',
                'v.comprobante_tipo_codigo',
                'v.serie',
                'v.correlativo',
                'v.total'
            )
            ->where('estado', '!=', 'anulada')
            ->orderByDesc('v.fecha_venta')
            ->orderByDesc('v.id');

        $reportes = $query->get();

        return view('reportes.ventas.ventas_por_entregar', compact(
            'reportes',
            'fechaInicio',
            'fechaFin',
            'vendedorId'
        ));
    }

    public function imprimirVentasPorEntregar(Request $request)
    {
        $fechaInicio = Carbon::parse($request->input('fecha_inicio'))->startOfDay();
        $fechaFin    = Carbon::parse($request->input('fecha_fin'))->endOfDay();
        $vendedorId  = $request->input('vendedor_id');

        $query = DB::table('ventas as v')
            ->join('venta_detalles as d', 'd.venta_id', '=', 'v.id')
            ->whereBetween('v.fecha_venta', [$fechaInicio, $fechaFin])
            // ✅ Solo las líneas con faltante
            ->whereRaw('COALESCE(d.entregado,0) < COALESCE(d.cantidad,0)')
            // ✅ Filtro por vendedor (si viene)
            ->when(!empty($vendedorId), function ($q) use ($vendedorId) {
                $q->where('v.user_id', (int)$vendedorId);
            })
            ->selectRaw("
                v.id,
                v.fecha_venta,
                v.user_nombre,
                v.cliente_nombre,
                TRIM(CONCAT(COALESCE(v.comprobante_tipo_codigo,''), ' ', COALESCE(v.serie,''), '-', COALESCE(v.correlativo,''))) AS documento,
                v.total AS monto,
                GROUP_CONCAT(
                    CONCAT(
                        COALESCE(d.producto_nombre,''),
                        ' (', FORMAT((COALESCE(d.cantidad,0) - COALESCE(d.entregado,0)), 2), ')'
                    )
                    ORDER BY d.producto_nombre
                    SEPARATOR ' | '
                ) AS productos_por_entregar
            ")
            ->where('estado', '!=', 'anulada')
            ->groupBy(
                'v.id',
                'v.fecha_venta',
                'v.user_nombre',
                'v.cliente_nombre',
                'v.comprobante_tipo_codigo',
                'v.serie',
                'v.correlativo',
                'v.total'
            )
            ->orderByDesc('v.fecha_venta')
            ->orderByDesc('v.id');

        $reportes = $query->get();

        $pdf = Pdf::loadView(
            'reportes.ventas.ventas_por_entregar_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('a4', 'portrait');

        return $pdf->stream('reporte_ventas_por_entregar.pdf');
    }

    public function topProductosMes(Request $request)
    {        
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();

        $top = VentaDetalle::selectRaw("
                producto_nombre,
                SUM(cantidad) as total_cantidad,
                SUM(cantidad * producto_empaque) as total_kg
            ")
            ->join('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
            ->whereBetween('ventas.fecha_venta', [$inicioMes, $finMes])
            ->where('estado', '!=', 'anulada')
            ->groupBy('producto_nombre')
            ->orderByDesc('total_cantidad')
            ->limit(10)
            ->get();

        return response()->json($top);
    }
    
    public function ventasUltimos15(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }
        // Buscar en los últimos 30 días, no 15
        $inicioFiltro = now()->subDays(60)->toDateString();
        $finFiltro = now()->toDateString();

        // Obtener ventas agrupadas por día
        $totales = Venta::selectRaw("
                DATE(fecha_venta) as fecha,
                SUM(total) as total_dia
            ")
            ->where('estado', '!=', 'anulada')
            ->whereDate('fecha_venta', '>=', $inicioFiltro)
            ->whereDate('fecha_venta', '<=', $finFiltro)
            ->groupByRaw('DATE(fecha_venta)')
            ->orderBy('fecha', 'desc') // primero ordenamos descendente
            ->limit(15)               // tomamos los últimos 15 días reales con ventas
            ->get()
            ->sortBy('fecha')         // luego los ordenamos ascendente para el gráfico
            ->values();

        //return response()->json($totales);
        $totalesFormateados = $totales->map(function($item) {
            return [
                'fecha' => \Carbon\Carbon::parse($item->fecha)->format('d-m'),
                'total_dia' => $item->total_dia
            ];
        });

        return response()->json($totalesFormateados);
    }
    
    public function ventasUltimos15Continuo(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }
        $hoy = now()->toDateString();
        $inicio = now()->subDays(15)->toDateString();

        // Obtener totales por día
        $totales = Venta::selectRaw("DATE(fecha_venta) as fecha, SUM(total) as total_dia")
            ->where('estado', '!=', 'anulada')
            ->whereDate('fecha_venta', '>=', $inicio)
            ->whereDate('fecha_venta', '<=', $hoy)
            ->groupByRaw('DATE(fecha_venta)')
            ->orderBy('fecha')
            ->pluck('total_dia', 'fecha'); // clave = fecha, valor = total

        // Generar rango de fechas
        $fechas = [];
        $current = now()->subDays(15);
        while ($current->lte(now())) {
            $fechas[] = $current->toDateString();
            $current->addDay();
        }

        // Combinar totales reales con fechas vacías
        $data = [];
        foreach ($fechas as $fecha) {
            $data[] = [
                'fecha' => $fecha,
                'total_dia' => $totales->get($fecha, 0) // si no existe, 0
            ];
        }

        return response()->json($data);
    }
    
}
