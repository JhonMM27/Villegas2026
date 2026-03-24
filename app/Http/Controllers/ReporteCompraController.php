<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CompraDetalle;
use App\Models\Compra;
use Carbon\Carbon;
use App\Exports\ComprasAcumuladasProductoExport;
use App\Exports\ComprasDetalladasProductoExport;
use App\Exports\ComprasDetalladasProveedorExport;
use App\Exports\ComprasDetalladasFechaExport;
use App\Exports\ComprasFechaExport;
use App\Exports\ComprasProveedorExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteCompraController extends Controller
{
    public function __construct(){
        $this->middleware('can:compras_report')->only(['index','comprasAcumuladasProducto','comprasPorFecha','exportarComprasAcumuladasProducto','exportarComprasFecha','comprasPorProveedor','exportarComprasProveedor',
        'comprasDetalladasProducto','exportarComprasDetalladasProducto','comprasDetalladasProveedor','exportarComprasDetalladasProveedor',
        'comprasDetalladasFecha','exportarComprasDetalladasFecha', 'imprimirComprasAcumuladasProducto',
        'imprimirComprasFecha','imprimirComprasDetalladasFecha', 'imprimirComprasDetalladasProducto','imprimirComprasDetalladasProveedor','imprimirComprasProveedor']);
        
        $this->middleware('can:dashboard_estadisticas')->only(['topProductosMes','comprasUltimos15','comprasUltimos15Continuo']);
    }

    public function index(Request $request)
    {
        // Lógica para generar el reporte de compras
        return view('reportes.compras');
    }

    public function comprasAcumuladasProducto(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = CompraDetalle::query()
        ->selectRaw('
            compra_detalles.producto_id,
            compra_detalles.producto_nombre,
            compra_detalles.producto_empaque,
            lineas.nombre as linea,
            SUM(compra_detalles.cantidad) as cantidad_total,
            AVG(compra_detalles.costo_unitario) as costo_unitario,
            SUM(compra_detalles.total) as importe_total,
            SUM(compra_detalles.cantidad * compra_detalles.producto_empaque) as kg_total
        ')
        ->join('compras', 'compra_detalles.compra_id', '=', 'compras.id')
        ->join('productos', 'compra_detalles.producto_id', '=', 'productos.id')
        ->join('lineas', 'productos.linea_id', '=', 'lineas.id')
        ->where('compras.estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        $reportes = $query
            ->groupBy('producto_id', 'producto_nombre', 'lineas.nombre','producto_empaque')
            ->orderBy('lineas.nombre')
            ->orderBy('compra_detalles.producto_nombre')
            ->get();

        return view('reportes.compras.compras_acumuladas_producto', compact('reportes', 'fechaInicio', 'fechaFin'));
    }

    public function exportarComprasAcumuladasProducto(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $fileName = 'compras_acumuladas_producto_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new ComprasAcumuladasProductoExport($fechaInicio, $fechaFin),
            $fileName
        );
    }

    public function imprimirComprasAcumuladasProducto(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = CompraDetalle::query()
        ->selectRaw('
            compra_detalles.producto_id,
            compra_detalles.producto_nombre,
            compra_detalles.producto_empaque,
            lineas.nombre as linea,
            SUM(compra_detalles.cantidad) as cantidad_total,
            AVG(compra_detalles.costo_unitario) as costo_unitario,
            SUM(compra_detalles.total) as importe_total,
            SUM(compra_detalles.cantidad * compra_detalles.producto_empaque) as kg_total
        ')
        ->join('compras', 'compra_detalles.compra_id', '=', 'compras.id')
        ->join('productos', 'compra_detalles.producto_id', '=', 'productos.id')
        ->join('lineas', 'productos.linea_id', '=', 'lineas.id')
        ->where('compras.estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        $reportes = $query
            ->groupBy('producto_id', 'producto_nombre', 'lineas.nombre','producto_empaque')
            ->orderBy('lineas.nombre')
            ->orderBy('compra_detalles.producto_nombre')
            ->get();

        $pdf = Pdf::loadView(
            'reportes.compras.compras_acumuladas_producto_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'portrait')
        ->setOptions([
            'defaultFont' => 'Courier',
        ]);

        return $pdf->stream('compras_acumuladas_producto.pdf');
    }

    public function comprasPorFecha(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = Compra::query()
            ->selectRaw('
                compras.id,
                compras.fecha_compra,
                compras.comprobante_tipo_codigo,
                compras.serie,
                compras.correlativo,
                compras.pago_forma_codigo,
                compras.total,
                proveedores.id as proveedor_id,
                proveedores.razon_social,
                COUNT(compra_detalles.id) as total_items
            ')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->join('compra_detalles', 'compra_detalles.compra_id', '=', 'compras.id')
            ->where('compras.estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        $reportes = $query
            ->groupBy(
                'compras.id',
                'compras.fecha_compra',
                'compras.comprobante_tipo_codigo',
                'compras.serie',
                'compras.correlativo',
                'compras.pago_forma_codigo',
                'compras.total',
                'proveedores.id',
                'proveedores.razon_social'
            )
            ->orderBy('compras.fecha_compra', 'asc')   // primero por fecha
            ->orderBy('compras.correlativo', 'asc')    // luego correlativo
            ->get();

        return view('reportes.compras.compras_fecha', compact('reportes', 'fechaInicio', 'fechaFin'));
    }

    public function exportarComprasFecha(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $fileName = 'compras_fecha_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new ComprasFechaExport($fechaInicio, $fechaFin),
            $fileName
        );
    }

    public function imprimirComprasFecha(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = Compra::query()
            ->selectRaw('
                compras.id,
                compras.fecha_compra,
                compras.comprobante_tipo_codigo,
                compras.serie,
                compras.correlativo,
                compras.pago_forma_codigo,
                compras.total,
                proveedores.id as proveedor_id,
                proveedores.razon_social,
                COUNT(compra_detalles.id) as total_items
            ')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->join('compra_detalles', 'compra_detalles.compra_id', '=', 'compras.id')
            ->where('compras.estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        $reportes = $query
            ->groupBy(
                'compras.id',
                'compras.fecha_compra',
                'compras.comprobante_tipo_codigo',
                'compras.serie',
                'compras.correlativo',
                'compras.pago_forma_codigo',
                'compras.total',
                'proveedores.id',
                'proveedores.razon_social'
            )
            ->orderBy('compras.fecha_compra', 'asc')   // primero por fecha
            ->orderBy('compras.correlativo', 'asc')    // luego correlativo
            ->get();

        $pdf = Pdf::loadView(
            'reportes.compras.compras_fecha_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'portrait')
        ->setOptions([
            'defaultFont' => 'Courier',
        ]);

        return $pdf->stream('compras_fecha.pdf');
    }

    public function comprasPorProveedor(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = Compra::query()
            ->selectRaw('
                compras.id,
                compras.fecha_compra,
                compras.comprobante_tipo_codigo,
                compras.serie,
                compras.correlativo,
                compras.pago_forma_codigo,
                compras.total,
                proveedores.id as proveedor_id,
                proveedores.razon_social,
                COUNT(compra_detalles.id) as total_items
            ')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->join('compra_detalles', 'compra_detalles.compra_id', '=', 'compras.id')
            ->where('compras.estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        $reportes = $query
            ->groupBy(
                'compras.id',
                'compras.fecha_compra',
                'compras.comprobante_tipo_codigo',
                'compras.serie',
                'compras.correlativo',
                'compras.pago_forma_codigo',
                'compras.total',
                'proveedores.id',
                'proveedores.razon_social'
            )
            ->orderBy('proveedores.razon_social', 'asc') // primero por proveedor
            ->orderBy('compras.fecha_compra', 'asc')     // luego por fecha
            ->orderBy('compras.correlativo', 'asc') 
            ->get();

        return view('reportes.compras.compras_proveedor', compact('reportes', 'fechaInicio', 'fechaFin'));
    }

    public function exportarComprasProveedor(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $fileName = 'compras_proveedor_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new ComprasProveedorExport($fechaInicio, $fechaFin),
            $fileName
        );
    }

    public function imprimirComprasProveedor(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = Compra::query()
            ->selectRaw('
                compras.id,
                compras.fecha_compra,
                compras.comprobante_tipo_codigo,
                compras.serie,
                compras.correlativo,
                compras.pago_forma_codigo,
                compras.total,
                proveedores.id as proveedor_id,
                proveedores.razon_social,
                COUNT(compra_detalles.id) as total_items
            ')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->join('compra_detalles', 'compra_detalles.compra_id', '=', 'compras.id')
            ->where('compras.estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        $reportes = $query
            ->groupBy(
                'compras.id',
                'compras.fecha_compra',
                'compras.comprobante_tipo_codigo',
                'compras.serie',
                'compras.correlativo',
                'compras.pago_forma_codigo',
                'compras.total',
                'proveedores.id',
                'proveedores.razon_social'
            )
            ->orderBy('proveedores.razon_social', 'asc') // primero por proveedor
            ->orderBy('compras.fecha_compra', 'asc')     // luego por fecha
            ->orderBy('compras.correlativo', 'asc') 
            ->get();

        $pdf = Pdf::loadView(
            'reportes.compras.compras_proveedor_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'portrait')
        ->setOptions([
            'defaultFont' => 'Courier',
        ]);

        return $pdf->stream('compras_proveedor.pdf');
    }

    public function comprasDetalladasProducto(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = CompraDetalle::query()
            ->selectRaw('
                compra_detalles.producto_id,
                compra_detalles.producto_nombre,
                compra_detalles.producto_empaque,
                compras.proveedor_nombre,
                compras.fecha_compra,
                compra_detalles.cantidad,
                (compra_detalles.cantidad * compra_detalles.producto_empaque) as kg_total,
                compra_detalles.costo_unitario as precio,
                compra_detalles.total as total,
                compras.comprobante_tipo_codigo,
                compras.serie,
                compras.correlativo,
                proveedores.id as proveedor_id,
                proveedores.razon_social
            ')
            ->join('compras', 'compra_detalles.compra_id', '=', 'compras.id')
            ->join('productos', 'compra_detalles.producto_id', '=', 'productos.id')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->where('compras.estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        $reportes = $query
            ->orderBy('producto_nombre')      // orden por producto
            ->orderBy('compras.fecha_compra') // luego por fecha
            ->get();

        return view('reportes.compras.compras_detalladas_producto', compact('reportes', 'fechaInicio', 'fechaFin'));
    }

    public function exportarComprasDetalladasProducto(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $fileName = 'compras_detalladas_producto_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new ComprasDetalladasProductoExport($fechaInicio, $fechaFin),
            $fileName
        );
    }

    public function imprimirComprasDetalladasProducto(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = CompraDetalle::query()
            ->selectRaw('
                compra_detalles.producto_id,
                compra_detalles.producto_nombre,
                compra_detalles.producto_empaque,
                compras.proveedor_nombre,
                compras.fecha_compra,
                compra_detalles.cantidad,
                (compra_detalles.cantidad * compra_detalles.producto_empaque) as kg_total,
                compra_detalles.costo_unitario as precio,
                compra_detalles.total as total,
                compras.comprobante_tipo_codigo,
                compras.serie,
                compras.correlativo,
                proveedores.id as proveedor_id,
                proveedores.razon_social
            ')
            ->join('compras', 'compra_detalles.compra_id', '=', 'compras.id')
            ->join('productos', 'compra_detalles.producto_id', '=', 'productos.id')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->where('compras.estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        $reportes = $query
            ->orderBy('producto_nombre')      // orden por producto
            ->orderBy('compras.fecha_compra') // luego por fecha
            ->get();

        $pdf = Pdf::loadView(
            'reportes.compras.compras_detalladas_producto_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'portrait')
        ->setOptions([
            'defaultFont' => 'Courier',
        ]);

        return $pdf->stream('compras_detalladas_producto.pdf');
    }

    public function comprasDetalladasProveedor(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = CompraDetalle::query()
            ->selectRaw('
                proveedores.id as proveedor_id,
                proveedores.razon_social as proveedor_nombre,
                compras.fecha_compra,
                compras.comprobante_tipo_codigo,
                compras.serie,
                compras.correlativo,
                compra_detalles.producto_id,
                compra_detalles.producto_nombre,
                (compra_detalles.cantidad * compra_detalles.producto_empaque) as ing_kg,
                compra_detalles.cantidad,
                compra_detalles.costo_unitario as p_lista,
                compra_detalles.total as importe
            ')
            ->join('compras', 'compra_detalles.compra_id', '=', 'compras.id')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->where('compras.estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        $reportes = $query
            ->orderBy('proveedores.razon_social')  // orden por proveedor
            ->orderBy('compras.fecha_compra')       // luego por fecha
            ->get();

        return view('reportes.compras.compras_detalladas_proveedor', compact('reportes', 'fechaInicio', 'fechaFin'));
    }

    public function exportarComprasDetalladasProveedor(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $fileName = 'compras_detalladas_proveedor_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new ComprasDetalladasProveedorExport($fechaInicio, $fechaFin),
            $fileName
        );
    }

    public function imprimirComprasDetalladasProveedor(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = CompraDetalle::query()
            ->selectRaw('
                proveedores.id as proveedor_id,
                proveedores.razon_social as proveedor_nombre,
                compras.fecha_compra,
                compras.comprobante_tipo_codigo,
                compras.serie,
                compras.correlativo,
                compra_detalles.producto_id,
                compra_detalles.producto_nombre,
                (compra_detalles.cantidad * compra_detalles.producto_empaque) as ing_kg,
                compra_detalles.cantidad,
                compra_detalles.costo_unitario as p_lista,
                compra_detalles.total as importe
            ')
            ->join('compras', 'compra_detalles.compra_id', '=', 'compras.id')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->where('compras.estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        $reportes = $query
            ->orderBy('proveedores.razon_social')  // orden por proveedor
            ->orderBy('compras.fecha_compra')       // luego por fecha
            ->get();

        $pdf = Pdf::loadView(
            'reportes.compras.compras_detalladas_proveedor_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'landscape')
        ->setOptions([
            'defaultFont' => 'Courier',
        ]);

        return $pdf->stream('compras_detalladas_proveedor.pdf');
    }

    public function comprasDetalladasFecha(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = CompraDetalle::query()
            ->selectRaw('
                proveedores.id as proveedor_id,
                proveedores.razon_social as proveedor_nombre,
                compras.fecha_compra,
                compras.comprobante_tipo_codigo,
                compras.serie,
                compras.correlativo,
                compra_detalles.producto_id,
                compra_detalles.producto_nombre,
                (compra_detalles.cantidad * compra_detalles.producto_empaque) as ing_kg,
                compra_detalles.cantidad,
                compra_detalles.costo_unitario as p_lista,
                compra_detalles.total as importe
            ')
            ->join('compras', 'compra_detalles.compra_id', '=', 'compras.id')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->where('compras.estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        // Orden correcto: primero fecha, luego proveedor, luego producto si quieres
        $reportes = $query
            ->orderBy('compras.fecha_compra')
            ->orderBy('proveedores.razon_social')
            ->orderBy('compra_detalles.producto_nombre')
            ->get();

        return view('reportes.compras.compras_detalladas_fecha', compact(
            'reportes',
            'fechaInicio',
            'fechaFin'
        ));
    }

    public function exportarComprasDetalladasFecha(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $fileName = 'compras_detalladas_fecha_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new ComprasDetalladasFechaExport($fechaInicio, $fechaFin),
            $fileName
        );
    }

    public function imprimirComprasDetalladasFecha(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $query = CompraDetalle::query()
            ->selectRaw('
                proveedores.id as proveedor_id,
                proveedores.razon_social as proveedor_nombre,
                compras.fecha_compra,
                compras.comprobante_tipo_codigo,
                compras.serie,
                compras.correlativo,
                compra_detalles.producto_id,
                compra_detalles.producto_nombre,
                (compra_detalles.cantidad * compra_detalles.producto_empaque) as ing_kg,
                compra_detalles.cantidad,
                compra_detalles.costo_unitario as p_lista,
                compra_detalles.total as importe
            ')
            ->join('compras', 'compra_detalles.compra_id', '=', 'compras.id')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->where('compras.estado', '!=', 'anulada');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('compras.fecha_compra', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        // Orden correcto: primero fecha, luego proveedor, luego producto si quieres
        $reportes = $query
            ->orderBy('compras.fecha_compra')
            ->orderBy('proveedores.razon_social')
            ->orderBy('compra_detalles.producto_nombre')
            ->get();

        $pdf = Pdf::loadView(
            'reportes.compras.compras_detalladas_fecha_pdf',
            compact('reportes', 'fechaInicio', 'fechaFin')
        )->setPaper('letter', 'portrait')
        ->setOptions([
            'defaultFont' => 'Courier',
        ]);

        return $pdf->stream('compras__detalladas_fecha.pdf');
    }
    /// desde aca para abajo no tiene estado ->where('compras.estado', '!=', 'anulada');
    public function topProductosMes(Request $request)
    {  
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();

        $top = CompraDetalle::selectRaw("
                producto_nombre,
                SUM(cantidad) as total_cantidad,
                SUM(cantidad * producto_empaque) as total_kg
            ")
            ->join('compras', 'compra_detalles.compra_id', '=', 'compras.id')
            ->whereBetween('compras.fecha_compra', [$inicioMes, $finMes])
            ->where('compras.estado', '!=', 'anulada')
            ->groupBy('producto_nombre')
            ->orderByDesc('total_cantidad')
            ->limit(10)
            ->get();

        return response()->json($top);
    }

    public function comprasUltimos15(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }
        // Buscar en los últimos 30 días, no 15
        $inicioFiltro = now()->subDays(60)->toDateString();
        $finFiltro = now()->toDateString();

        // Obtener compras agrupadas por día
        $totales = Compra::selectRaw("
                DATE(fecha_compra) as fecha,
                SUM(total) as total_dia
            ")
            ->whereDate('fecha_compra', '>=', $inicioFiltro)
            ->whereDate('fecha_compra', '<=', $finFiltro)
            ->where('compras.estado', '!=', 'anulada')
            ->groupByRaw('DATE(fecha_compra)')
            ->orderBy('fecha', 'desc') // primero ordenamos descendente
            ->limit(15)               // tomamos los últimos 15 días reales con compras
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

    public function comprasUltimos15Continuo(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }
        $hoy = now()->toDateString();
        $inicio = now()->subDays(15)->toDateString();

        // Obtener totales por día
        $totales = Compra::selectRaw("DATE(fecha_compra) as fecha, SUM(total) as total_dia")
            ->whereDate('fecha_compra', '>=', $inicio)
            ->whereDate('fecha_compra', '<=', $hoy)
            ->where('compras.estado', '!=', 'anulada')
            ->groupByRaw('DATE(fecha_compra)')
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
