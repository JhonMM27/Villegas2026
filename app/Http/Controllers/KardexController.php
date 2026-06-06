<?php

namespace App\Http\Controllers;

use App\Exports\KardexFechasProductosExport;
use App\Exports\MovimientosExport;
use App\Exports\StockAlCorteExport;
use App\Models\Movimiento;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class KardexController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:kardex_report')->only([
            'index',
            'stockGeneral',
            'exportarStockGeneral',
            'imprimirStockGeneral',
            'kardexFechasProductos',
            'kardexFechasProductosPdf',
            'kardexFechasProductosExcel',
            'reporteMovimientos',
            'exportarMovimientosExcel',
            'imprimirMovimientosPdf',
        ]);
    }

    public function index(Request $request)
    {
        $productos = Producto::select('id', 'nombre')->get();

        return view('kardex.index', compact('productos'));
    }

    public function stockGeneral(Request $request)
    {
        if (! $request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $request->validate([
            'fecha' => ['required', 'date'],
        ]);

        $fecha = Carbon::parse($request->fecha)->endOfDay();

        $reportes = $this->getStockAlCorteReportes($fecha, true, false);

        return view('kardex.reportes.stock_general', compact('reportes', 'fecha'));
    }

    public function exportarStockGeneral(Request $request)
    {
        $request->validate([
            'fecha' => ['required', 'date'],
        ]);

        $fecha = Carbon::parse($request->fecha)->endOfDay();

        $reportes = $this->getStockAlCorteReportes($fecha, true, false);

        $fileName = 'stock_alcorte_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new StockAlCorteExport($reportes),
            $fileName
        );
    }

    public function imprimirStockGeneral(Request $request)
    {
        $request->validate([
            'fecha' => ['required', 'date'],
        ]);

        $fecha = Carbon::parse($request->fecha)->endOfDay();

        $reportes = $this->getStockAlCorteReportes($fecha, true, false);

        $pdf = Pdf::loadView('kardex.reportes.stock_general_pdf', [
            'reportes' => $reportes,
            'fecha' => $fecha,
        ])->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'Courier',
            ]);

        return $pdf->stream('stock_general.pdf');
    }

    public function kardexFechasProductos(Request $request)
    {
        if (! $request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'producto_ids' => ['array'],
            'producto_ids.*' => ['integer'],
            'operaciones' => ['array'],
            'operaciones.*' => ['string'],
        ]);

        $data = $this->getKardexFechasProductosReportes(
            $request->input('fecha_inicio'),
            $request->input('fecha_fin'),
            (array) $request->input('producto_ids', []),
            (array) $request->input('operaciones', [])
        );

        $reportes = $data['reportes'];

        return view('kardex.reportes.fechas_productos', compact('reportes'));
    }

    public function kardexFechasProductosPdf(Request $request)
    {
        $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'producto_ids' => ['array'],
            'producto_ids.*' => ['integer'],
            'operaciones' => ['array'],
            'operaciones.*' => ['string'],
        ]);

        $data = $this->getKardexFechasProductosReportes(
            $request->input('fecha_inicio'),
            $request->input('fecha_fin'),
            (array) $request->input('producto_ids', []),
            (array) $request->input('operaciones', [])
        );

        $pdf = Pdf::loadView('kardex.reportes.fechas_productos_pdf', [
            'reportes' => $data['reportes'],
            'fechaInicio' => $data['ini'],
            'fechaFin' => $data['fin'],
            'operaciones' => $data['operaciones'],
        ])->setPaper('a4', 'landscape');

        return $pdf->stream("kardex_{$data['ini']->format('Ymd')}_{$data['fin']->format('Ymd')}.pdf");
    }

    public function kardexFechasProductosExcel(Request $request)
    {
        $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'producto_ids' => ['array'],
            'producto_ids.*' => ['integer'],
            'operaciones' => ['array'],
            'operaciones.*' => ['string'],
        ]);

        $data = $this->getKardexFechasProductosReportes(
            $request->input('fecha_inicio'),
            $request->input('fecha_fin'),
            (array) $request->input('producto_ids', []),
            (array) $request->input('operaciones', [])
        );

        $name = "kardex_{$data['ini']->format('Ymd')}_{$data['fin']->format('Ymd')}.xlsx";

        return Excel::download(new KardexFechasProductosExport($data['reportes']), $name);
    }

    public function reporteMovimientos(Request $request)
    {
        if (! $request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'producto_ids' => ['array'],
            'producto_ids.*' => ['integer'],
            'tipos' => ['array'],
            'tipos.*' => ['string'],
        ]);

        $reportes = $this->getMovimientosData(
            $request->input('fecha_inicio'),
            $request->input('fecha_fin'),
            (array) $request->input('producto_ids', []),
            (array) $request->input('tipos', [])
        );

        return view('kardex.reportes.movimientos', compact('reportes'));
    }

    public function exportarMovimientosExcel(Request $request)
    {
        $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'producto_ids' => ['array'],
            'producto_ids.*' => ['integer'],
            'tipos' => ['array'],
            'tipos.*' => ['string'],
        ]);

        $reportes = $this->getMovimientosData(
            $request->input('fecha_inicio'),
            $request->input('fecha_fin'),
            (array) $request->input('producto_ids', []),
            (array) $request->input('tipos', [])
        );

        $fileName = 'movimientos_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new MovimientosExport($reportes), $fileName);
    }

    public function imprimirMovimientosPdf(Request $request)
    {
        $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'producto_ids' => ['array'],
            'producto_ids.*' => ['integer'],
            'tipos' => ['array'],
            'tipos.*' => ['string'],
        ]);

        $reportes = $this->getMovimientosData(
            $request->input('fecha_inicio'),
            $request->input('fecha_fin'),
            (array) $request->input('producto_ids', []),
            (array) $request->input('tipos', [])
        );

        $pdf = Pdf::loadView('kardex.reportes.movimientos_pdf', [
            'reportes' => $reportes,
            'fechaInicio' => Carbon::parse($request->fecha_inicio),
            'fechaFin' => Carbon::parse($request->fecha_fin),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('reporte_movimientos.pdf');
    }

    private function getMovimientosData(string $fechaInicio, string $fechaFin, array $productoIds = [], array $tipos = [])
    {
        $ini = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->endOfDay();

        return Movimiento::with('producto')
            ->whereBetween('fecha', [$ini, $fin])
            ->when(! empty($productoIds), fn ($q) => $q->whereIn('producto_id', $productoIds))
            ->when(! empty($tipos), fn ($q) => $q->whereIn('tipo', $tipos))
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Movimientos detallados para el reporte de kardex.
     * Devuelve cada línea con entrada_und y salida_und.
     */
    private function movimientosKardexUnion(array $productoIds = [], ?Carbon $ini = null, ?Carbon $hasta = null)
    {
        $ini = $ini ?: Carbon::now()->subYears(5)->startOfDay();
        $hasta = $hasta ?: Carbon::now()->endOfDay();

        $compra = DB::table('compra_detalles as cd')
            ->join('compras as c', 'c.id', '=', 'cd.compra_id')
            ->join('productos as p', 'p.id', '=', 'cd.producto_id')
            ->leftJoin('lineas as l', 'l.id', '=', 'p.linea_id')
            ->whereBetween('c.fecha_compra', [$ini, $hasta])
            ->where('cd.cantidad', '>', 0)
            ->when(! empty($productoIds), fn ($q) => $q->whereIn('p.id', $productoIds))
            ->selectRaw("
                c.fecha_compra as fecha,
                'COMPRA' as operacion,
                c.id as op_id,
                cd.id as det_id,
                p.id as producto_id,
                p.nombre as producto,
                p.empaque as empaque,
                l.nombre as linea,
                COALESCE(cd.unidad_codigo, p.unidad_codigo) as unidad,
                CONCAT(c.comprobante_tipo_codigo,' ',c.serie,'-',c.correlativo) as documento,
                (COALESCE(cd.cantidad,0) * (COALESCE(NULLIF(cd.producto_empaque,0), p.empaque) / NULLIF(p.empaque,0))) as entrada_und,
                0 as salida_und,
                COALESCE(cd.costo_unitario, 0) as precio_unitario,
                c.proveedor_nombre as referencia,
                10 as sort
            ")
            ->where('c.estado', '!=', 'anulada');

        $venta = DB::table('venta_detalles as vd')
            ->join('ventas as v', 'v.id', '=', 'vd.venta_id')
            ->join('productos as p', 'p.id', '=', 'vd.producto_id')
            ->leftJoin('lineas as l', 'l.id', '=', 'p.linea_id')
            ->whereBetween('v.fecha_venta', [$ini, $hasta])
            ->where('v.estado', '!=', 'anulada')
            ->where(function ($q) {
                $q->where('vd.salida_kg', '>', 0)
                    ->orWhere('vd.cantidad', '>', 0);
            })
            ->when(! empty($productoIds), fn ($q) => $q->whereIn('p.id', $productoIds))
            ->selectRaw("
                v.fecha_venta as fecha,
                'VENTA' as operacion,
                v.id as op_id,
                vd.id as det_id,
                p.id as producto_id,
                p.nombre as producto,
                p.empaque as empaque,
                l.nombre as linea,
                COALESCE(vd.unidad_codigo, p.unidad_codigo) as unidad,
                CONCAT(v.comprobante_tipo_codigo,' ',v.serie,'-',v.correlativo) as documento,
                0 as entrada_und,
                CASE
                    WHEN COALESCE(vd.salida_kg,0) > 0
                        THEN (COALESCE(vd.salida_kg,0) / NULLIF(p.empaque,0))
                    ELSE (COALESCE(vd.cantidad,0) * (COALESCE(NULLIF(vd.producto_empaque,0), p.empaque) / NULLIF(p.empaque,0)))
                END as salida_und,
                COALESCE(vd.precio_unitario, 0) as precio_unitario,
                v.cliente_nombre as referencia,
                20 as sort
            ");

        $prepIn = DB::table('preparadas as pr')
            ->join('productos as p', 'p.id', '=', 'pr.producto_id')
            ->leftJoin('lineas as l', 'l.id', '=', 'p.linea_id')
            ->whereBetween('pr.fecha', [$ini, $hasta])
            ->where('pr.ingreso_saco', '>', 0)
            ->where('pr.estado', '!=', 'anulada')
            ->when(! empty($productoIds), fn ($q) => $q->whereIn('p.id', $productoIds))
            ->selectRaw("
                pr.fecha as fecha,
                'PREPARADA' as operacion,
                pr.id as op_id,
                0 as det_id,
                p.id as producto_id,
                p.nombre as producto,
                p.empaque as empaque,
                l.nombre as linea,
                p.unidad_codigo as unidad,
                CONCAT('INT ', pr.numero_interno) as documento,
                (COALESCE(pr.ingreso_saco,0) * (COALESCE(NULLIF(pr.producto_empaque,0), p.empaque) / NULLIF(p.empaque,0))) as entrada_und,
                0 as salida_und,
                COALESCE(pr.costo_unitario, 0) as precio_unitario,
                CONCAT('PREPARADA-', p.nombre) as referencia,
                30 as sort
            ");

        $prepOut = DB::table('preparada_detalles as pd')
            ->join('preparadas as pr', 'pr.id', '=', 'pd.preparada_id')
            ->join('productos as p', 'p.id', '=', 'pd.producto_id')
            ->join('productos as pp', 'pp.id', '=', 'pr.producto_id')
            ->leftJoin('lineas as l', 'l.id', '=', 'p.linea_id')
            ->whereBetween('pr.fecha', [$ini, $hasta])
            ->where('pd.salida_kg', '>', 0)
            ->where('pr.estado', '!=', 'anulada')
            ->when(! empty($productoIds), fn ($q) => $q->whereIn('p.id', $productoIds))
            ->selectRaw("
                pr.fecha as fecha,
                'PREPARADA' as operacion,
                pr.id as op_id,
                pd.id as det_id,
                p.id as producto_id,
                p.nombre as producto,
                p.empaque as empaque,
                l.nombre as linea,
                p.unidad_codigo as unidad,
                CONCAT('INT ', pr.numero_interno) as documento,
                0 as entrada_und,
                (COALESCE(pd.salida_kg,0) / NULLIF(p.empaque,0)) as salida_und,
                COALESCE(pd.precio_unitario, 0) as precio_unitario,
                CONCAT('PREPARADA-', pp.nombre) as referencia,
                31 as sort
            ");

        $npIn = DB::table('nucleo_preparadas as np')
            ->join('productos as p', 'p.id', '=', 'np.nucleo_id')
            ->leftJoin('lineas as l', 'l.id', '=', 'p.linea_id')
            ->whereBetween('np.fecha', [$ini, $hasta])
            ->where('np.ingreso_saco', '>', 0)
            // ->where('np.estado', '!=', 'anulada')
            ->when(! empty($productoIds), fn ($q) => $q->whereIn('p.id', $productoIds))
            ->selectRaw("
                np.fecha as fecha,
                'PREPARADA_NUCLEO' as operacion,
                np.id as op_id,
                0 as det_id,
                p.id as producto_id,
                p.nombre as producto,
                p.empaque as empaque,
                l.nombre as linea,
                p.unidad_codigo as unidad,
                CONCAT('INT ', np.numero_interno) as documento,
                (COALESCE(np.ingreso_saco,0) * (COALESCE(NULLIF(np.producto_empaque,0), p.empaque) / NULLIF(p.empaque,0))) as entrada_und,
                0 as salida_und,
                COALESCE(np.costo_unitario, 0) as precio_unitario,
                CONCAT('NUCLEO PREPARADA-', p.nombre) as referencia,
                40 as sort
            ");

        $npOut = DB::table('nucleo_preparada_detalles as nd')
            ->join('nucleo_preparadas as np', 'np.id', '=', 'nd.nucleo_preparada_id')
            ->join('productos as p', 'p.id', '=', 'nd.producto_id')
            ->join('productos as npp', 'npp.id', '=', 'np.nucleo_id')
            ->leftJoin('lineas as l', 'l.id', '=', 'p.linea_id')
            ->whereBetween('np.fecha', [$ini, $hasta])
            ->where('nd.salida_kg', '>', 0)
            // ->where('estado', '!=', 'anulada')
            ->when(! empty($productoIds), fn ($q) => $q->whereIn('p.id', $productoIds))
            ->selectRaw("
                np.fecha as fecha,
                'PREPARADA_NUCLEO' as operacion,
                np.id as op_id,
                nd.id as det_id,
                p.id as producto_id,
                p.nombre as producto,
                p.empaque as empaque,
                l.nombre as linea,
                COALESCE(nd.unidad_codigo, p.unidad_codigo) as unidad,
                CONCAT('INT ', np.numero_interno) as documento,
                0 as entrada_und,
                (COALESCE(nd.salida_kg,0) / NULLIF(p.empaque,0)) as salida_und,
                COALESCE(nd.costo_unitario, 0) as precio_unitario,
                CONCAT('NUCLEO PREPARADA-', npp.nombre) as referencia,
                41 as sort
            ");

        $prestamo = DB::table('prestamo_detalles as ptd')
            ->join('prestamos as pr', 'pr.id', '=', 'ptd.prestamo_id')
            ->join('productos as p', 'p.id', '=', 'ptd.producto_id')
            ->leftJoin('lineas as l', 'l.id', '=', 'p.linea_id')
            ->whereBetween('pr.fecha_prestamo', [$ini, $hasta])
            ->where('ptd.cantidad_kgm', '>', 0)
            ->where('pr.estado', '!=', 'anulada')
            ->when(! empty($productoIds), fn ($q) => $q->whereIn('p.id', $productoIds))
            ->selectRaw("
                pr.fecha_prestamo as fecha,
                'PRESTAMO' as operacion,
                pr.id as op_id,
                ptd.id as det_id,
                p.id as producto_id,
                p.nombre as producto,
                p.empaque as empaque,
                l.nombre as linea,
                COALESCE(ptd.unidad_codigo, p.unidad_codigo) as unidad,
                CONCAT(pr.comprobante_tipo_codigo,' ',pr.serie,'-',pr.correlativo) as documento,
                CASE
                    WHEN pr.movimiento_tipo IN ('DD','PD')
                        THEN (COALESCE(ptd.cantidad_kgm,0) / NULLIF(p.empaque,0))
                    ELSE 0
                END as entrada_und,
                CASE
                    WHEN pr.movimiento_tipo IN ('PA','DA')
                        THEN (COALESCE(ptd.cantidad_kgm,0) / NULLIF(p.empaque,0))
                    ELSE 0
                END as salida_und,
                COALESCE(ptd.valor_unitario, 0) as precio_unitario,
                CONCAT(
                    'Tipo ', pr.movimiento_tipo,
                    ' | Origen ', COALESCE(pr.cliente_origen_id,'-'),
                    ' → Destino ', COALESCE(pr.cliente_destino_id,'-')
                ) as referencia,
                50 as sort
            ");

        return $compra
            ->unionAll($venta)
            ->unionAll($prepIn)
            ->unionAll($prepOut)
            ->unionAll($npIn)
            ->unionAll($npOut)
            ->unionAll($prestamo);
    }

    /**
     * Movimientos resumidos (delta) para cálculo de stock.
     * Igual estructura que antes, sin cambios.
     */
    private function movimientosStockUnion()
    {
        $compra = DB::table('compra_detalles as cd')
            ->join('compras as c', 'c.id', '=', 'cd.compra_id')
            ->join('productos as p', 'p.id', '=', 'cd.producto_id')
            ->where('cd.cantidad', '>', 0)
            ->where('c.estado', '!=', 'anulada')
            ->selectRaw('
                cd.producto_id,
                c.fecha_compra as fecha,
                (COALESCE(cd.cantidad,0) * (COALESCE(NULLIF(cd.producto_empaque,0), p.empaque) / NULLIF(p.empaque,0))) as delta
            ');

        $venta = DB::table('venta_detalles as vd')
            ->join('ventas as v', 'v.id', '=', 'vd.venta_id')
            ->join('productos as p', 'p.id', '=', 'vd.producto_id')
            ->where(function ($q) {
                $q->where('vd.salida_kg', '>', 0)
                    ->orWhere('vd.cantidad', '>', 0);
            })
            ->where('v.estado', '!=', 'anulada')
            ->selectRaw('
                vd.producto_id,
                v.fecha_venta as fecha,
                -(
                    CASE
                        WHEN COALESCE(vd.salida_kg,0) > 0
                            THEN (COALESCE(vd.salida_kg,0) / NULLIF(p.empaque,0))
                        ELSE (COALESCE(vd.cantidad,0) * (COALESCE(NULLIF(vd.producto_empaque,0), p.empaque) / NULLIF(p.empaque,0)))
                    END
                ) as delta
            ');

        $prepIn = DB::table('preparadas as pr')
            ->join('productos as p', 'p.id', '=', 'pr.producto_id')
            ->where('pr.ingreso_saco', '>', 0)
            ->where('pr.estado', '!=', 'anulada')
            ->selectRaw('
                pr.producto_id,
                pr.fecha as fecha,
                (COALESCE(pr.ingreso_saco,0) * (COALESCE(NULLIF(pr.producto_empaque,0), p.empaque) / NULLIF(p.empaque,0))) as delta
            ');

        $prepOut = DB::table('preparada_detalles as pd')
            ->join('preparadas as pr', 'pr.id', '=', 'pd.preparada_id')
            ->join('productos as p', 'p.id', '=', 'pd.producto_id')
            ->where('pd.salida_kg', '>', 0)
            ->where('pr.estado', '!=', 'anulada')
            ->selectRaw('
                pd.producto_id,
                pr.fecha as fecha,
                -(COALESCE(pd.salida_kg,0) / NULLIF(p.empaque,0)) as delta
            ');

        $npIn = DB::table('nucleo_preparadas as np')
            ->join('productos as p', 'p.id', '=', 'np.nucleo_id')
            ->where('np.ingreso_saco', '>', 0)
            ->where('np.estado', '!=', 'anulada')
            ->selectRaw('
                np.nucleo_id as producto_id,
                np.fecha as fecha,
                (COALESCE(np.ingreso_saco,0) * (COALESCE(NULLIF(np.producto_empaque,0), p.empaque) / NULLIF(p.empaque,0))) as delta
            ');

        $npOut = DB::table('nucleo_preparada_detalles as nd')
            ->join('nucleo_preparadas as np', 'np.id', '=', 'nd.nucleo_preparada_id')
            ->join('productos as p', 'p.id', '=', 'nd.producto_id')
            ->where('nd.salida_kg', '>', 0)
            ->where('np.estado', '!=', 'anulada')
            ->selectRaw('
                nd.producto_id,
                np.fecha as fecha,
                -(COALESCE(nd.salida_kg,0) / NULLIF(p.empaque,0)) as delta
            ');

        $prestamo = DB::table('prestamo_detalles as ptd')
            ->join('prestamos as pr', 'pr.id', '=', 'ptd.prestamo_id')
            ->join('productos as p', 'p.id', '=', 'ptd.producto_id')
            ->where('ptd.cantidad_kgm', '>', 0)
            ->where('pr.estado', '!=', 'anulada')
            ->selectRaw("
                ptd.producto_id,
                pr.fecha_prestamo as fecha,
                CASE
                    WHEN pr.movimiento_tipo IN ('DD','PD')
                        THEN (COALESCE(ptd.cantidad_kgm,0) / NULLIF(p.empaque,0))
                    WHEN pr.movimiento_tipo IN ('PA','DA')
                        THEN -(COALESCE(ptd.cantidad_kgm,0) / NULLIF(p.empaque,0))
                    ELSE 0
                END as delta
            ");

        return $compra
            ->unionAll($venta)
            ->unionAll($prepIn)
            ->unionAll($prepOut)
            ->unionAll($npIn)
            ->unionAll($npOut)
            ->unionAll($prestamo);
    }

    /**
     * Stock al corte.
     *
     * LÓGICA:
     * - El stock se calcula deshaciendo movimientos futuros: stock_almacen - delta_post
     * - El CPP (costo_unitario) se obtiene de la tabla movimientos a la fecha del corte
     */
    private function getStockAlCorteReportes(Carbon $fechaCorte, bool $soloActivos = true, bool $soloConStock = true)
    {
        $fechaCorte = $fechaCorte->copy()->endOfDay();

        // Movimientos que ocurrieron DESPUÉS del corte (los que debemos deshacer)
        $movPostCorte = DB::query()
            ->fromSub($this->movimientosStockUnion(), 'm')
            ->where('m.fecha', '>', $fechaCorte)
            ->groupBy('m.producto_id')
            ->selectRaw('
                m.producto_id,
                SUM(COALESCE(m.delta, 0)) as delta_post
            ');

        // Subconsulta para CPP: último costo_nuevo por producto a la fecha del corte
        $cppSubquery = DB::query()
            ->fromRaw("(
                SELECT producto_id, costo_nuevo,
                    ROW_NUMBER() OVER (PARTITION BY producto_id ORDER BY fecha DESC, id DESC) as rn
                FROM movimientos
                WHERE fecha <= '{$fechaCorte->toDateTimeString()}'
                    AND costo_nuevo IS NOT NULL
            ) as ranked")
            ->where('rn', 1)
            ->selectRaw('producto_id, costo_nuevo');

        $q = DB::table('productos as p')
            ->leftJoin('lineas as l', 'l.id', '=', 'p.linea_id')
            ->leftJoinSub($movPostCorte, 's', fn ($j) => $j->on('s.producto_id', '=', 'p.id'))
            ->leftJoinSub($cppSubquery, 'cpp', fn ($j) => $j->on('cpp.producto_id', '=', 'p.id'))
            ->selectRaw('
                p.id as producto_id,
                p.nombre as producto,
                p.empaque,
                l.nombre as linea,
                (COALESCE(p.stock_almacen, 0) - COALESCE(s.delta_post, 0)) as stock,
                COALESCE(cpp.costo_nuevo, p.costo_unitario, 0) as costo_unitario,
                ((COALESCE(p.stock_almacen, 0) - COALESCE(s.delta_post, 0)) * COALESCE(cpp.costo_nuevo, p.costo_unitario, 0)) as valor_total
            ');

        if ($soloActivos) {
            $q->where('p.activo', 1);
        }

        if ($soloConStock) {
            $q->havingRaw('(COALESCE(p.stock_almacen, 0) - COALESCE(s.delta_post, 0)) > 0');
        }

        return $q->orderBy('l.nombre')
            ->orderBy('p.nombre')
            ->get();
    }

    /**
     * Kardex entre fechas con stock inicial real.
     *
     * LÓGICA CORREGIDA:
     * - El stock actual real es productos.stock_almacen (hoy).
     * - Stock inicial del rango = stock_almacen - SUM(delta donde fecha >= ini)
     *   Es decir, deshacemos todos los movimientos a partir del inicio del rango.
     * - Dentro del rango mostramos cada movimiento acumulando sobre ese stock inicial.
     */
    private function getKardexFechasProductosReportes(
        string $fechaInicio,
        string $fechaFin,
        array $productoIds = [],
        array $operaciones = []
    ) {
        $todasOps = ['COMPRA', 'VENTA', 'PREPARADA', 'PREPARADA_NUCLEO', 'PRESTAMO'];

        $productoIds = array_values(array_filter((array) $productoIds));
        $operaciones = array_values(array_filter((array) $operaciones));

        if (count($operaciones) === 0) {
            $operaciones = $todasOps;
        } else {
            $operaciones = array_values(array_intersect($operaciones, $todasOps));
            if (count($operaciones) === 0) {
                $operaciones = $todasOps;
            }
        }

        $ini = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->endOfDay();

        // 1) Movimientos dentro del rango solicitado (para mostrar en el kardex)
        $movUnion = $this->movimientosKardexUnion($productoIds, $ini, $fin);

        // 2) Stock inicial del rango:
        //    stock_almacen (hoy) MENOS todos los movimientos ocurridos DESDE ini hasta hoy.
        //    Así obtenemos cuánto había justo ANTES del inicio del rango.
        $movDesdeIni = DB::query()
            ->fromSub($this->movimientosStockUnion(), 's')
            ->when(! empty($productoIds), fn ($q) => $q->whereIn('s.producto_id', $productoIds))
            ->where('s.fecha', '>=', $ini)  // Todo lo que ocurrió desde el inicio del rango en adelante
            ->groupBy('s.producto_id')
            ->selectRaw('
                s.producto_id,
                SUM(COALESCE(s.delta, 0)) as delta_desde_ini
            ');

        // Unimos con productos para obtener el stock_almacen base
        $stockInicialRango = DB::table('productos as p')
            ->leftJoinSub($movDesdeIni, 'd', fn ($j) => $j->on('d.producto_id', '=', 'p.id'))
            ->when(! empty($productoIds), fn ($q) => $q->whereIn('p.id', $productoIds))
            ->selectRaw('
                p.id as producto_id,
                -- Stock justo antes del inicio del rango
                (COALESCE(p.stock_almacen, 0) - COALESCE(d.delta_desde_ini, 0)) as base_stock
            ');

        // 3) Movimientos del rango con stock acumulado usando el stock inicial real
        $calc = DB::query()
            ->fromSub($movUnion, 'k')
            ->leftJoinSub($stockInicialRango, 'b', fn ($j) => $j->on('b.producto_id', '=', 'k.producto_id'))
            ->whereIn('k.operacion', $operaciones)
            ->selectRaw('
                k.fecha,
                k.operacion,
                k.op_id as id,
                k.det_id,
                k.producto_id,
                k.producto,
                k.empaque,
                k.linea,
                k.unidad,
                k.documento,
                k.entrada_und,
                k.salida_und,
                k.precio_unitario,
                k.referencia,
                k.sort,
                (
                    COALESCE(b.base_stock, 0)
                    + SUM(COALESCE(k.entrada_und,0) - COALESCE(k.salida_und,0)) OVER (
                        PARTITION BY k.producto_id
                        ORDER BY k.fecha, k.sort, k.operacion, k.op_id, k.det_id
                        ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
                    )
                ) AS stock_und
            ')
            ->orderBy('k.producto_id')
            ->orderBy('k.fecha')
            ->orderBy('k.sort')
            ->orderBy('k.operacion')
            ->orderBy('k.op_id')
            ->orderBy('k.det_id');

        return [
            'ini' => $ini,
            'fin' => $fin,
            'operaciones' => $operaciones,
            'productoIds' => $productoIds,
            'reportes' => $calc->get(),
        ];
    }
}
