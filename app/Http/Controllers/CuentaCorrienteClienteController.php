<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class CuentaCorrienteClienteController extends Controller
{
    // region Configuración y navegación

    public function __construct()
    {
        $this->middleware('can:cuenta_corriente_report')->only(['index', 'resumenCliente', 'ventasAgrupadaProductoClientePdf', 'ventasDetallePdf', 'detalleCreditosPorCobrarPdf', 'creditosPorCobrarClienteTodosPdf', 'creditosPorCobrarClienteFechasPdf', 'ventasGeneralFechasPdf', 'saldosTodosPdf', 'creditosPorCobrarIndex', 'creditosPorCobrarData', 'estadoCuentaClientePdf', 'estadoCuentaSimplificadoClientePdf']);
    }

    public function index(Request $request)
    {
        // Lógica para generar el reporte de compras
        $clientes = Cliente::select('id', 'razon_social')->get();

        return view('cuenta-cliente.index', compact('clientes'));
    }

    public function resumenCliente(Request $request)
    {
        if (! $request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $request->validate(['fecha' => ['required', 'date']]);

        $fecha = \Carbon\Carbon::parse($request->fecha)->endOfDay();

        $reportes = $this->getStockAlCorteReportes($fecha, true, false);

        return view('kardex.reportes.stock_general', compact('reportes', 'fecha'));
    }

    // endregion

    // region Reportes de ventas por cliente

    public function ventasAgrupadaProductoClientePdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'cliente_ids' => ['nullable', 'array'],
            'cliente_ids.*' => ['integer'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $clienteIds = $data['cliente_ids'] ?? [];

        $clienteNombre = null;

        if (count($clienteIds) === 1) {
            $cliente = Cliente::find($clienteIds[0]);
            $clienteNombre = $cliente?->id.' - '.$cliente?->razon_social;
        }

        $reportes = VentaDetalle::query()
            ->join('ventas as v', 'v.id', '=', 'venta_detalles.venta_id')
            ->leftJoin('productos as p', 'p.id', '=', 'venta_detalles.producto_id')
            ->select([
                'venta_detalles.venta_id',
                'v.fecha_venta',

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

                // empaques
                DB::raw('IFNULL(venta_detalles.producto_empaque,0) as empaque_detalle'),
                DB::raw('IFNULL(p.empaque,0) as empaque_producto'),

                // Documento (tipo + serie + correlativo)
                DB::raw("CONCAT(v.comprobante_tipo_codigo,' ',v.serie,'-',v.correlativo) as documento"),

                // Kg calculado según empaque del detalle
                DB::raw('
                    (venta_detalles.cantidad * 
                        CASE 
                            WHEN IFNULL(venta_detalles.producto_empaque,0) > 0 THEN venta_detalles.producto_empaque
                            ELSE IFNULL(p.empaque,0)
                        END
                    ) as kg_detalle
                '),

                // Cantidad convertida (a empaque del producto)
                DB::raw('
                    CASE
                        WHEN IFNULL(p.empaque,0) > 0 AND IFNULL(venta_detalles.producto_empaque,0) > 0
                            THEN (venta_detalles.cantidad * (venta_detalles.producto_empaque / p.empaque))
                        ELSE venta_detalles.cantidad
                    END as cantidad_convertida
                '),
            ])
            ->whereBetween('v.fecha_venta', [$ini, $fin])
            ->when(! empty($clienteIds), fn ($q) => $q->whereIn('v.cliente_id', $clienteIds))
            ->where('v.estado', '!=', 'anulada') // ✅ excluir anuladas
            ->orderBy('venta_detalles.producto_nombre')   // primero por producto
            ->orderBy('v.fecha_venta')                   // luego por fecha
            ->orderBy('venta_detalles.id')
            ->get();

        // Para header (puedes mostrar rango también si quieres)
        $fecha = $fin;

        $pdf = Pdf::loadView('cuenta-cliente.reportes.ventas_agrupadas_producto', compact('reportes', 'fecha', 'ini', 'fin', 'clienteNombre'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('reporte_cliente_ventas_producto.pdf');
    }

    public function ventasDetallePdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'cliente_ids' => ['nullable', 'array'],
            'cliente_ids.*' => ['integer'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $clienteIds = $data['cliente_ids'] ?? [];

        $clienteNombre = null;

        if (count($clienteIds) === 1) {
            $cliente = Cliente::find($clienteIds[0]);
            $clienteNombre = $cliente ? ($cliente->id.' - '.$cliente->razon_social) : null;
        }

        $reportes = VentaDetalle::query()
            ->join('ventas as v', 'v.id', '=', 'venta_detalles.venta_id')
            ->leftJoin('productos as p', 'p.id', '=', 'venta_detalles.producto_id')
            ->select([
                'venta_detalles.venta_id',
                'v.fecha_venta',

                'v.comprobante_tipo_codigo',
                'v.serie',
                'v.correlativo',

                'v.cliente_id',
                'v.cliente_nombre',

                // ✅ NUEVO: por venta
                'v.abonos',
                'v.saldo',

                'venta_detalles.producto_id',
                'venta_detalles.producto_nombre',

                'venta_detalles.cantidad',
                'venta_detalles.precio_unitario',
                'venta_detalles.total',

                // empaques
                DB::raw('IFNULL(venta_detalles.producto_empaque,0) as empaque_detalle'),
                DB::raw('IFNULL(p.empaque,0) as empaque_producto'),

                // Documento
                DB::raw("CONCAT(v.comprobante_tipo_codigo,' ',v.serie,'-',v.correlativo) as documento"),

                // Kg = cantidad * empaque_detalle (fallback empaque_producto)
                DB::raw('
                    (venta_detalles.cantidad *
                        CASE
                            WHEN IFNULL(venta_detalles.producto_empaque,0) > 0 THEN venta_detalles.producto_empaque
                            ELSE IFNULL(p.empaque,0)
                        END
                    ) as kg_detalle
                '),

                // Cantidad convertida (a empaque del producto)
                DB::raw('
                    CASE
                        WHEN IFNULL(p.empaque,0) > 0 AND IFNULL(venta_detalles.producto_empaque,0) > 0
                            THEN (venta_detalles.cantidad * (venta_detalles.producto_empaque / p.empaque))
                        ELSE venta_detalles.cantidad
                    END as cantidad_convertida
                '),
            ])
            ->whereBetween('v.fecha_venta', [$ini, $fin])
            ->when(! empty($clienteIds), fn ($q) => $q->whereIn('v.cliente_id', $clienteIds))
            ->where('v.estado', '!=', 'anulada') // ✅ excluir anuladas
            ->orderBy('v.fecha_venta')
            ->orderBy('venta_detalles.venta_id')
            ->orderBy('venta_detalles.id')
            ->get();

        $fecha = $fin;

        $pdf = Pdf::loadView(
            'cuenta-cliente.reportes.ventas_detalle',
            compact('reportes', 'fecha', 'ini', 'fin', 'clienteNombre')
        )->setPaper('a4', 'portrait');

        return $pdf->stream('reporte_cliente_ventas_detalle.pdf');
    }

    // endregion

    // region Reportes de rentabilidad

    public function rentabilidadClienteFechas(Request $request)
    {
        if (! $request->ajax()) {
            abort(403, 'Acceso no autorizado');
        }

        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $clienteId = (int) $data['cliente_id'];

        $cliente = Cliente::find($clienteId);
        $clienteNombre = $cliente ? ($cliente->id.' - '.$cliente->razon_social) : null;

        $reportes = VentaDetalle::query()
            ->join('ventas as v', 'v.id', '=', 'venta_detalles.venta_id')
            ->leftJoin('productos as p', 'p.id', '=', 'venta_detalles.producto_id')
            ->leftJoin('lineas as l', 'l.id', '=', 'p.linea_id')
            ->select([
                'venta_detalles.venta_id',
                'v.fecha_venta',

                'v.comprobante_tipo_codigo',
                'v.serie',
                'v.correlativo',

                'v.cliente_id',
                'v.cliente_nombre',

                'venta_detalles.producto_id',
                'venta_detalles.producto_nombre',
                'l.nombre as linea_nombre',

                'venta_detalles.cantidad',
                'venta_detalles.precio_unitario',
                'venta_detalles.total',

                DB::raw("CONCAT(v.comprobante_tipo_codigo,' ',v.serie,'-',v.correlativo) as documento"),

                DB::raw('IFNULL(venta_detalles.producto_empaque,0) as empaque_detalle'),
                DB::raw('IFNULL(p.empaque,0) as empaque_producto'),

                DB::raw('(venta_detalles.cantidad * IFNULL(venta_detalles.producto_empaque, IFNULL(p.empaque,1))) as kg_detalle'),

                DB::raw('venta_detalles.costo_unitario'),
                DB::raw('venta_detalles.costo_total'),

                DB::raw('(venta_detalles.total - venta_detalles.costo_total) as rentabilidad'),
            ])
            ->whereBetween('v.fecha_venta', [$ini, $fin])
            ->where('v.cliente_id', $clienteId)
            ->where('v.estado', '!=', 'anulada')
            ->orderBy('v.fecha_venta')
            ->orderBy('venta_detalles.venta_id')
            ->orderBy('venta_detalles.id')
            ->get();

        return view('cuenta-cliente.reportes.rentabilidad_cliente_fechas', compact('reportes', 'ini', 'fin', 'clienteNombre'));
    }

    public function rentabilidadClienteFechasPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $clienteId = (int) $data['cliente_id'];

        $cliente = Cliente::find($clienteId);
        $clienteNombre = $cliente ? ($cliente->id.' - '.$cliente->razon_social) : null;

        $reportes = VentaDetalle::query()
            ->join('ventas as v', 'v.id', '=', 'venta_detalles.venta_id')
            ->leftJoin('productos as p', 'p.id', '=', 'venta_detalles.producto_id')
            ->leftJoin('lineas as l', 'l.id', '=', 'p.linea_id')
            ->select([
                'venta_detalles.venta_id',
                'v.fecha_venta',

                'v.comprobante_tipo_codigo',
                'v.serie',
                'v.correlativo',

                'v.cliente_id',
                'v.cliente_nombre',

                'venta_detalles.producto_id',
                'venta_detalles.producto_nombre',
                'l.nombre as linea_nombre',

                'venta_detalles.cantidad',
                'venta_detalles.precio_unitario',
                'venta_detalles.total',

                DB::raw("CONCAT(v.comprobante_tipo_codigo,' ',v.serie,'-',v.correlativo) as documento"),

                DB::raw('IFNULL(venta_detalles.producto_empaque,0) as empaque_detalle'),
                DB::raw('IFNULL(p.empaque,0) as empaque_producto'),

                DB::raw('(venta_detalles.cantidad * IFNULL(venta_detalles.producto_empaque, IFNULL(p.empaque,1))) as kg_detalle'),

                DB::raw('venta_detalles.costo_unitario'),
                DB::raw('venta_detalles.costo_total'),

                DB::raw('(venta_detalles.total - venta_detalles.costo_total) as rentabilidad'),
            ])
            ->whereBetween('v.fecha_venta', [$ini, $fin])
            ->where('v.cliente_id', $clienteId)
            ->where('v.estado', '!=', 'anulada')
            ->orderBy('v.fecha_venta')
            ->orderBy('venta_detalles.venta_id')
            ->orderBy('venta_detalles.id')
            ->get();

        $pdf = Pdf::loadView(
            'cuenta-cliente.reportes.rentabilidad_cliente_fechas_pdf',
            compact('reportes', 'ini', 'fin', 'clienteNombre')
        )->setPaper('letter', 'landscape');

        return $pdf->stream('reporte_rentabilidad_cliente.pdf');
    }

    public function rentabilidadTodosClientesFechas(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $reportes = VentaDetalle::query()
            ->join('ventas as v', 'v.id', '=', 'venta_detalles.venta_id')
            ->select([
                'v.cliente_id',
                'v.cliente_nombre',
                DB::raw('SUM(venta_detalles.total) as total_importe'),
                DB::raw('SUM(venta_detalles.costo_total) as total_costo'),
                DB::raw('SUM(venta_detalles.total - venta_detalles.costo_total) as total_rentabilidad'),
            ])
            ->whereBetween('v.fecha_venta', [$ini, $fin])
            ->where('v.estado', '!=', 'anulada')
            ->groupBy('v.cliente_id')
            ->groupBy('v.cliente_nombre')
            ->orderBy('v.cliente_nombre')
            ->get();

        $totImporte = $reportes->sum('total_importe');
        $totCosto = $reportes->sum('total_costo');
        $totRentabilidad = $reportes->sum('total_rentabilidad');

        return view('cuenta-cliente.reportes-general.rentabilidad_todos_clientes_fechas_view', compact('reportes', 'ini', 'fin', 'totImporte', 'totCosto', 'totRentabilidad'));
    }

    public function rentabilidadTodosClientesFechasPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $reportes = VentaDetalle::query()
            ->join('ventas as v', 'v.id', '=', 'venta_detalles.venta_id')
            ->select([
                'v.cliente_id',
                'v.cliente_nombre',
                DB::raw('SUM(venta_detalles.total) as total_importe'),
                DB::raw('SUM(venta_detalles.costo_total) as total_costo'),
                DB::raw('SUM(venta_detalles.total - venta_detalles.costo_total) as total_rentabilidad'),
            ])
            ->whereBetween('v.fecha_venta', [$ini, $fin])
            ->where('v.estado', '!=', 'anulada')
            ->groupBy('v.cliente_id')
            ->groupBy('v.cliente_nombre')
            ->orderBy('v.cliente_nombre')
            ->get();

        $totImporte = $reportes->sum('total_importe');
        $totCosto = $reportes->sum('total_costo');
        $totRentabilidad = $reportes->sum('total_rentabilidad');

        $pdf = Pdf::loadView(
            'cuenta-cliente.reportes-general.rentabilidad_todos_clientes_fechas',
            compact('reportes', 'ini', 'fin', 'totImporte', 'totCosto', 'totRentabilidad')
        )->setPaper('letter', 'landscape');

        return $pdf->stream('reporte_rentabilidad_todos_clientes.pdf');
    }

    // endregion

    // region Créditos por cobrar del cliente

    public function detalleCreditosPorCobrarPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'cliente_ids' => ['nullable', 'array'],
            'cliente_ids.*' => ['integer'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $clienteIds = $data['cliente_ids'] ?? [];

        $clienteNombre = null;
        if (count($clienteIds) === 1) {
            $cliente = Cliente::find($clienteIds[0]);
            $clienteNombre = $cliente ? ($cliente->id.' - '.$cliente->razon_social) : null;
        }

        // ✅ Detalle por venta + producto (producto_id/nombre/empaque desde venta_detalles)
        $reportes = VentaDetalle::query()
            ->join('ventas as v', 'v.id', '=', 'venta_detalles.venta_id')
            ->selectRaw('
                v.id as venta_id,
                v.fecha_venta,
                v.pago_forma_nombre,
                v.abonos,
                v.saldo,

                CONCAT(v.comprobante_tipo_codigo," ",v.serie,"-",v.correlativo) as documento,

                venta_detalles.producto_id as producto_id,
                venta_detalles.producto_nombre as producto_nombre,
                IFNULL(venta_detalles.producto_empaque,0) as producto_empaque,

                SUM(venta_detalles.cantidad) as cantidad,

                -- KG = cantidad * producto_empaque
                SUM(venta_detalles.cantidad * IFNULL(venta_detalles.producto_empaque,0)) as kg,

                AVG(venta_detalles.precio_unitario) as precio,

                SUM(venta_detalles.total) as importe
            ')
            ->whereBetween('v.fecha_venta', [$ini, $fin])
            ->when(! empty($clienteIds), fn ($q) => $q->whereIn('v.cliente_id', $clienteIds))
            ->where('v.estado', '!=', 'anulada') // ✅ excluir anuladas

            // ✅ créditos por cobrar
            ->where('v.saldo', '>', 0)

            ->groupBy(
                'v.id',
                'v.fecha_venta',
                'v.pago_forma_nombre',
                'v.abonos',
                'v.saldo',
                'v.comprobante_tipo_codigo',
                'v.serie',
                'v.correlativo',
                'venta_detalles.producto_id',
                'venta_detalles.producto_nombre',
                'venta_detalles.producto_empaque'
            )
            ->orderBy('v.fecha_venta')
            ->orderBy('v.id')
            ->orderBy('venta_detalles.producto_nombre')
            ->get();

        $pdf = Pdf::loadView('cuenta-cliente.reportes.detalle_creditos_por_cobrar_detalles', compact(
            'reportes', 'ini', 'fin', 'clienteNombre'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('detalle_creditos_por_cobrar.pdf');
    }

    // region Créditos por Cobrar del Cliente

    public function creditosPorCobrarClienteTodosPdf(
        Request $request
    ) {
        $data = $request->validate([
            'cliente_ids' => [
                'required',
                'array',
                'size:1',
            ],
            'cliente_ids.0' => [
                'required',
                'integer',
            ],
        ]);

        $clienteId = (int) $data['cliente_ids'][0];

        $cliente = Cliente::query()
            ->select('id', 'razon_social')
            ->findOrFail($clienteId);

        $clienteNombre = $cliente->id
            .' - '
            .$cliente->razon_social;

        $fechaCorte = now()->endOfDay();

        // region Ventas con saldo pendiente

        $reportes = $this->consultaVentasPendientesCliente(
            $clienteId,
            $fechaCorte
        )
            ->selectRaw('
            ventas.id,
            ventas.fecha_venta,
            ventas.fecha_vencimiento,

            CONCAT(
                ventas.comprobante_tipo_codigo,
                " ",
                ventas.serie,
                "-",
                ventas.correlativo
            ) AS documento,

            ventas.pago_forma_nombre AS tipo_venta,

            COALESCE(ventas.total, 0) AS total,
            COALESCE(ventas.acuenta, 0) AS acuenta,
            COALESCE(ventas.abonos, 0) AS abonos,
            COALESCE(ventas.saldo, 0) AS saldo,

            ventas.cliente_nombre,
            ventas.user_nombre
        ')
            ->withCount([
                'detalles as items',
            ])
            ->orderBy('ventas.fecha_venta')
            ->orderBy('ventas.id')
            ->get();

        // endregion

        // region Abonos sin venta asociada

        $aplicadoPorProvisional = DB::table(
            'venta_provisional_detalles as detalle'
        )
            ->selectRaw('
            detalle.venta_provisional_id,
            COALESCE(
                SUM(
                    CASE
                        WHEN detalle.venta_id IS NOT NULL
                        THEN detalle.monto
                        ELSE 0
                    END
                ),
                0
            ) AS monto_aplicado
        ')
            ->groupBy('detalle.venta_provisional_id');

        $abonosSueltos = DB::table('venta_provisionales as vp')
            ->leftJoinSub(
                $aplicadoPorProvisional,
                'aplicado',
                function ($join) {
                    $join->on(
                        'aplicado.venta_provisional_id',
                        '=',
                        'vp.id'
                    );
                }
            )
            ->selectRaw('
            vp.id,
            vp.fecha_provisional,
            vp.numero_recibo,
            CASE
                WHEN (
                    COALESCE(vp.monto, 0)
                    - COALESCE(aplicado.monto_aplicado, 0)
                ) > 0
                THEN (
                    COALESCE(vp.monto, 0)
                    - COALESCE(aplicado.monto_aplicado, 0)
                )
                ELSE 0
            END AS monto
        ')
            ->where('vp.cliente_id', $clienteId)
            ->where(
                'vp.fecha_provisional',
                '<=',
                $fechaCorte
            )
            ->whereRaw('
            (
                COALESCE(vp.monto, 0)
                - COALESCE(aplicado.monto_aplicado, 0)
            ) > 0
        ')
            ->orderBy('vp.fecha_provisional')
            ->orderBy('vp.id')
            ->get();

        // endregion

        // region Totales

        $totTotal = round(
            (float) $reportes->sum('total'),
            2
        );

        $totAcuenta = round(
            (float) $reportes->sum('acuenta'),
            2
        );

        $totAbonos = round(
            (float) $reportes->sum('abonos'),
            2
        );

        $totSaldo = round(
            (float) $reportes->sum('saldo'),
            2
        );

        $totItems = (int) $reportes->sum('items');

        // endregion

        $pdf = Pdf::loadView(
            'cuenta-cliente.reportes.creditos_por_cobrar_cliente_todos',
            compact(
                'reportes',
                'clienteNombre',
                'totTotal',
                'totAcuenta',
                'totAbonos',
                'totSaldo',
                'totItems',
                'abonosSueltos'
            )
        )->setPaper('a4', 'portrait');

        $nombreSanitizado = preg_replace(
            '/[^a-zA-Z0-9\s\-]/',
            '',
            $clienteNombre
        );

        $nombreSanitizado = preg_replace(
            '/\s+/',
            '_',
            trim($nombreSanitizado)
        );

        return $pdf->stream(
            'creditos_por_cobrar_'
            .$nombreSanitizado
            .'.pdf'
        );
    }

    // endregion

    public function creditosPorCobrarIndex(Request $request)
    {
        return view('cuenta-cliente.reportes.creditos_por_cobrar');
    }

    public function creditosPorCobrarData(Request $request)
    {
        $query = Venta::query()
            ->select([
                'id',
                'fecha_venta',
                'comprobante_tipo_codigo',
                'serie',
                'correlativo',
                'total',
                'abonos',
                'saldo',
                'estado',
                'cliente_nombre',
            ])
            ->where('saldo', '>', 0)
            ->where('estado', '!=', 'anulada')
            ->orderBy('fecha_venta', 'asc');

        if ($request->has('cliente_ids') && is_array($request->cliente_ids) && count($request->cliente_ids) > 0) {
            $query->whereIn('cliente_id', $request->cliente_ids);
        }

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $verUrl = route('ventas.ver', $row->id);
                $ticketUrl = route('ventas.imprimir', $row->id);

                return '<div class="btn-group">
                    <button class="btn btn-sm btn-primary btn-view-venta" data-id="'.$row->id.'">
                        <i class="bi bi-eye"></i>
                    </button>
                    <a href="'.$ticketUrl.'" target="_blank" class="btn btn-sm btn-secondary">
                        <i class="bi bi-printer"></i>
                    </a>
                </div>';
            })
            ->addColumn('documento', function ($row) {
                return trim(($row->comprobante_tipo_codigo ? $row->comprobante_tipo_codigo.' ' : '').$row->serie.'-'.$row->correlativo);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function creditosPorCobrarClienteFechasPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'cliente_ids' => ['nullable', 'array'],
            'cliente_ids.*' => ['integer'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $clienteIds = $data['cliente_ids'] ?? [];

        // header: si es 1 cliente, mostramos su razon social
        $clienteNombre = null;
        if (count($clienteIds) === 1) {
            $cliente = Cliente::find($clienteIds[0]);
            $clienteNombre = $cliente ? ($cliente->id.' - '.$cliente->razon_social) : null;
        }

        // Si no filtra (o filtra varios), mostramos columna cliente en tabla
        $mostrarColCliente = (count($clienteIds) !== 1);

        $reportes = Venta::query()
            ->leftJoin('venta_detalles as vd', 'vd.venta_id', '=', 'ventas.id')
            ->selectRaw('
                ventas.id,
                ventas.fecha_venta,
                ventas.fecha_vencimiento,
                CONCAT(ventas.comprobante_tipo_codigo," ",ventas.serie,"-",ventas.correlativo) as documento,
                ventas.pago_forma_nombre as tipo_venta,

                ventas.total,
                ventas.acuenta,
                ventas.abonos,
                ventas.saldo,

                COUNT(vd.id) as items,

                ventas.cliente_id,
                ventas.cliente_nombre,
                ventas.user_nombre
            ')
            ->whereBetween('ventas.fecha_venta', [$ini, $fin])
            ->when(! empty($clienteIds), fn ($q) => $q->whereIn('ventas.cliente_id', $clienteIds))
            ->where('ventas.estado', '!=', 'anulada')
            ->where('ventas.saldo', '>', 0) // créditos por cobrar
            ->groupBy(
                'ventas.id',
                'ventas.fecha_venta',
                'ventas.fecha_vencimiento',
                'ventas.comprobante_tipo_codigo',
                'ventas.serie',
                'ventas.correlativo',
                'ventas.pago_forma_nombre',
                'ventas.total',
                'ventas.acuenta',
                'ventas.abonos',
                'ventas.saldo',
                'ventas.cliente_id',
                'ventas.cliente_nombre',
                'ventas.user_nombre'
            )
            ->orderBy('ventas.fecha_venta')
            ->get();

        // Totales generales
        $totTotal = (float) $reportes->sum('total');
        $totAcuenta = (float) $reportes->sum('acuenta');
        $totAbonos = (float) $reportes->sum('abonos');
        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        $pdf = Pdf::loadView('cuenta-cliente.reportes.creditos_por_cobrar_cliente_fechas', compact(
            'reportes',
            'ini',
            'fin',
            'clienteNombre',
            'mostrarColCliente',
            'totTotal',
            'totAcuenta',
            'totAbonos',
            'totSaldo',
            'totItems'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('creditos_por_cobrar_cliente_fechas.pdf');
    }

    // endregion

    // region Ventas generales y saldos por cliente

    public function ventasGeneralFechasPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'cliente_ids' => ['nullable', 'array'],
            'cliente_ids.*' => ['integer'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $clienteIds = $data['cliente_ids'] ?? [];

        // header: si es 1 cliente, mostramos su razon social
        $clienteNombre = null;
        if (count($clienteIds) === 1) {
            $cliente = Cliente::find($clienteIds[0]);
            $clienteNombre = $cliente ? ($cliente->id.' - '.$cliente->razon_social) : null;
        }

        // Si no filtra (o filtra varios), mostramos columna cliente en tabla
        $mostrarColCliente = (count($clienteIds) !== 1);

        $reportes = Venta::query()
            ->leftJoin('venta_detalles as vd', 'vd.venta_id', '=', 'ventas.id')
            ->selectRaw('
                ventas.id,
                ventas.fecha_venta,
                CONCAT(ventas.comprobante_tipo_codigo," ",ventas.serie,"-",ventas.correlativo) as documento,
                ventas.pago_forma_nombre as tipo_venta,

                ventas.total,
                ventas.acuenta,
                ventas.abonos,
                ventas.saldo,

                COUNT(vd.id) as items,

                ventas.cliente_id,
                ventas.cliente_nombre,
                ventas.user_nombre
            ')
            ->whereBetween('ventas.fecha_venta', [$ini, $fin])
            ->when(! empty($clienteIds), fn ($q) => $q->whereIn('ventas.cliente_id', $clienteIds))
            ->where('ventas.estado', '!=', 'anulada')
            // ->where('ventas.saldo', '>', 0) // créditos por cobrar
            ->groupBy(
                'ventas.id',
                'ventas.fecha_venta',
                'ventas.comprobante_tipo_codigo',
                'ventas.serie',
                'ventas.correlativo',
                'ventas.pago_forma_nombre',
                'ventas.total',
                'ventas.acuenta',
                'ventas.abonos',
                'ventas.saldo',
                'ventas.cliente_id',
                'ventas.cliente_nombre',
                'ventas.user_nombre'
            )
            ->orderBy('ventas.fecha_venta')
            ->get();

        // Totales generales
        $totTotal = (float) $reportes->sum('total');
        $totAcuenta = (float) $reportes->sum('acuenta');
        $totAbonos = (float) $reportes->sum('abonos');
        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        $pdf = Pdf::loadView('cuenta-cliente.reportes.ventas_general_fechas', compact(
            'reportes',
            'ini',
            'fin',
            'clienteNombre',
            'mostrarColCliente',
            'totTotal',
            'totAcuenta',
            'totAbonos',
            'totSaldo',
            'totItems'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('creditos_por_cobrar_fechas.pdf');
    }

    public function saldosTodosPdf(Request $request)
    {
        $data = $request->validate([
            'cliente_ids' => ['required', 'array', 'min:1'],
            'cliente_ids.*' => ['integer'],
        ]);

        $clienteIds = $data['cliente_ids'];

        // Este reporte solo admite 1 cliente
        if (count($clienteIds) !== 1) {
            return back()->with('error', 'Debe seleccionar exactamente un cliente.');
        }

        $clienteId = (int) $clienteIds[0];

        $cliente = Cliente::find($clienteId);
        $clienteNombre = $cliente ? ($cliente->id.' - '.$cliente->razon_social) : null;

        $reportes = Venta::query()
            ->leftJoin('venta_detalles as vd', 'vd.venta_id', '=', 'ventas.id')
            ->selectRaw('
                ventas.id,
                ventas.fecha_venta,
                CONCAT(ventas.comprobante_tipo_codigo," ",ventas.serie,"-",ventas.correlativo) as documento,
                ventas.pago_forma_nombre as tipo_venta,

                ventas.total,
                ventas.acuenta,
                ventas.abonos,
                ventas.saldo,

                COUNT(vd.id) as items,

                ventas.cliente_nombre,
                ventas.user_nombre
            ')
            ->where('ventas.cliente_id', $clienteId)
            ->where('ventas.estado', '!=', 'anulada')
            ->where('ventas.saldo', '>', 0)
            ->groupBy(
                'ventas.id',
                'ventas.fecha_venta',
                'ventas.comprobante_tipo_codigo',
                'ventas.serie',
                'ventas.correlativo',
                'ventas.pago_forma_nombre',
                'ventas.total',
                'ventas.acuenta',
                'ventas.abonos',
                'ventas.saldo',
                'ventas.cliente_nombre',
                'ventas.user_nombre'
            )
            ->orderBy('ventas.fecha_venta')
            ->get();

        // Totales generales
        $totTotal = (float) $reportes->sum('total');
        $totAcuenta = (float) $reportes->sum('acuenta');
        $totAbonos = (float) $reportes->sum('abonos');
        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        $empresa = (object) [
            'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
            'direccion' => 'Carretera Pomalca KM 3'."\n".'A espaldas de Ferretería Herrera',
            'ruc' => '20538937321',
            'celular' => '967984895 - 978431737 - 915177079',
        ];

        $pdf = Pdf::loadView('cuenta-cliente.reportes.saldos', compact(
            'reportes',
            'clienteNombre',
            'totTotal',
            'totAcuenta',
            'totAbonos',
            'totSaldo',
            'totItems', 'empresa'
        ))->setPaper([0, 0, 226.77, 600], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $nombreSanitizado = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $clienteNombre ?? '');
        $nombreSanitizado = preg_replace('/\s+/', '_', trim($nombreSanitizado));
        $partes = explode('_', $nombreSanitizado);
        $idPart = count($partes) > 0 ? array_shift($partes) : '';
        $nombreLimpio = implode('_', $partes);
        $nombreArchivo = $nombreLimpio.'_'.$idPart.'.pdf';

        return $pdf->stream('saldos_'.$nombreArchivo);
    }

    // endregion

    // region Reportes generales de cuentas por cobrar

    public function creditosPorCobrarFechasPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);
        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $reportes = Venta::query()
            ->leftJoin('venta_detalles as vd', 'vd.venta_id', '=', 'ventas.id')
            ->selectRaw('
                ventas.id,
                ventas.fecha_venta,
                ventas.fecha_vencimiento,
                CONCAT(ventas.comprobante_tipo_codigo," ",ventas.serie,"-",ventas.correlativo) as documento,
                ventas.pago_forma_nombre as tipo_venta,

                ventas.total,
                ventas.acuenta,
                ventas.abonos,
                ventas.saldo,

                COUNT(vd.id) as items,

                ventas.cliente_nombre,
                ventas.user_nombre
            ')
            ->where('ventas.estado', '!=', 'anulada')
            ->where('ventas.saldo', '>', 0)
            ->whereBetween('ventas.fecha_venta', [$ini, $fin])
            ->groupBy(
                'ventas.id',
                'ventas.fecha_venta',
                'ventas.fecha_vencimiento',
                'ventas.comprobante_tipo_codigo',
                'ventas.serie',
                'ventas.correlativo',
                'ventas.pago_forma_nombre',
                'ventas.total',
                'ventas.acuenta',
                'ventas.abonos',
                'ventas.saldo',
                'ventas.cliente_nombre',
                'ventas.user_nombre'
            )
            ->orderBy('ventas.fecha_venta')
            ->get();

        // Totales generales
        $totTotal = (float) $reportes->sum('total');
        $totAcuenta = (float) $reportes->sum('acuenta');
        $totAbonos = (float) $reportes->sum('abonos');
        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        $pdf = Pdf::loadView('cuenta-cliente.reportes-general.creditos_por_cobrar_fechas', compact(
            'reportes',
            'totTotal',
            'totAcuenta',
            'totAbonos',
            'totSaldo',
            'totItems',
            'ini',
            'fin'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream('creditos_por_cobrar_fechas.pdf');
    }

    public function creditosPorCobrarDiasPdf(Request $request)
    {
        $data = $request->validate([
            'dias' => ['required', 'integer', 'min:1'],
        ]);

        $dias = (int) $data['dias'];

        $reportes = Venta::query()
            ->leftJoin('venta_detalles as vd', 'vd.venta_id', '=', 'ventas.id')
            ->selectRaw('
                ventas.id,
                ventas.fecha_venta,
                ventas.fecha_vencimiento,
                CONCAT(ventas.comprobante_tipo_codigo," ",ventas.serie,"-",ventas.correlativo) as documento,
                ventas.pago_forma_nombre as tipo_venta,

                ventas.total,
                ventas.acuenta,
                ventas.abonos,
                ventas.saldo,

                COUNT(vd.id) as items,

                ventas.cliente_nombre,
                ventas.user_nombre
            ')
            ->where('ventas.estado', '!=', 'anulada')
            ->where('ventas.saldo', '>', 0)
            ->whereNotNull('ventas.fecha_vencimiento')
            ->whereRaw('DATEDIFF(CURDATE(), ventas.fecha_venta) >= ?', [$dias])
            ->groupBy(
                'ventas.id',
                'ventas.fecha_venta',
                'ventas.fecha_vencimiento',
                'ventas.comprobante_tipo_codigo',
                'ventas.serie',
                'ventas.correlativo',
                'ventas.pago_forma_nombre',
                'ventas.total',
                'ventas.acuenta',
                'ventas.abonos',
                'ventas.saldo',
                'ventas.cliente_nombre',
                'ventas.user_nombre'
            )
            ->orderBy('ventas.fecha_venta')
            ->get();

        // Totales generales
        $totTotal = (float) $reportes->sum('total');
        $totAcuenta = (float) $reportes->sum('acuenta');
        $totAbonos = (float) $reportes->sum('abonos');
        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        $pdf = Pdf::loadView('cuenta-cliente.reportes-general.creditos_por_cobrar_dias', compact(
            'reportes',
            'totTotal',
            'totAcuenta',
            'totAbonos',
            'totSaldo',
            'totItems',
            'dias'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream('creditos_por_cobrar_dias.pdf');
    }

    public function creditosPorCobrarDiasAgrupadoClientePdf(Request $request)
    {
        $data = $request->validate([
            'dias' => ['required', 'integer', 'min:1'],
        ]);

        $dias = (int) $data['dias'];

        $reportes = Venta::query()
            ->selectRaw('
                ventas.id,
                ventas.fecha_venta,
                ventas.fecha_vencimiento,

                DATE_FORMAT(ventas.fecha_venta, "%d/%m/%Y") as fecha_venta_fmt,
                DATE_FORMAT(ventas.fecha_vencimiento, "%d/%m/%Y") as fecha_vencimiento_fmt,

                CONCAT(ventas.comprobante_tipo_codigo," ",ventas.serie,"-",ventas.correlativo) as documento,
                ventas.pago_forma_nombre as tipo_venta,

                ventas.total,
                ventas.acuenta,
                ventas.abonos,
                ventas.saldo,

                ventas.cliente_id,
                ventas.cliente_nombre,
                ventas.user_nombre
            ')
            ->withCount(['detalles as items'])
            ->where('ventas.estado', '!=', 'anulada')
            ->where('ventas.saldo', '>', 0)
            ->whereNotNull('ventas.fecha_vencimiento')
            // equivalente a >= días, pero sin funciones sobre la columna
            ->whereDate('ventas.fecha_venta', '<=', Carbon::today()->subDays($dias)->toDateString())
            ->orderBy('ventas.cliente_nombre')
            ->orderBy('ventas.fecha_venta')
            ->get();

        // Agrupar por cliente_id + subtotales
        $grupos = $reportes->groupBy('cliente_id')->map(function ($rows) {
            $first = $rows->first();

            return [
                'cliente_id' => $first->cliente_id,
                'cliente_nombre' => $first->cliente_nombre,
                'ventas' => $rows,

                'totTotal' => (float) $rows->sum('total'),
                'totAcuenta' => (float) $rows->sum('acuenta'),
                'totAbonos' => (float) $rows->sum('abonos'),
                'totSaldo' => (float) $rows->sum('saldo'),
                'totItems' => (int) $rows->sum('items'),
            ];
        })->values();

        // Totales generales
        $totTotal = (float) $reportes->sum('total');
        $totAcuenta = (float) $reportes->sum('acuenta');
        $totAbonos = (float) $reportes->sum('abonos');
        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        $pdf = Pdf::loadView(
            'cuenta-cliente.reportes-general.creditos_por_cobrar_dias_agrupado_cliente',
            compact('grupos', 'totTotal', 'totAcuenta', 'totAbonos', 'totSaldo', 'totItems', 'dias')
        )->setPaper('a4', 'landscape');

        return $pdf->stream('creditos_por_cobrar_dias_agrupado_cliente.pdf');
    }

    public function saldosAcumuladosClienteDiasPdf(Request $request)
    {
        $data = $request->validate([
            'dias' => ['required', 'integer', 'min:1'],
        ]);

        $dias = (int) $data['dias'];

        $reportes = DB::table('ventas')
            ->join('clientes', 'clientes.id', '=', 'ventas.cliente_id')
            ->selectRaw('
                ventas.cliente_id,
                ventas.cliente_nombre,
                SUM(ventas.saldo) as saldo,
                COUNT(ventas.id) as items,
                clientes.direccion as domicilio,
                clientes.telefono as telefono
            ')
            // si quieres SOLO créditos por cobrar:
            ->where('ventas.estado', '!=', 'anulada')
            ->where('ventas.saldo', '>', 0)
            // >= 30 días de antigüedad desde la fecha_venta:
            ->whereDate('ventas.fecha_venta', '<=', Carbon::today()->subDays($dias)->toDateString())
            ->groupBy(
                'ventas.cliente_id',
                'ventas.cliente_nombre',
                'clientes.direccion',
                'clientes.telefono'
            )
            ->orderBy('ventas.cliente_nombre', 'asc')
            ->get();

        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        $pdf = Pdf::loadView(
            'cuenta-cliente.reportes-general.saldos_acumulados_cliente_dias',
            compact('reportes', 'totSaldo', 'totItems', 'dias')
        )->setPaper('a4', 'portrait'); // o landscape si prefieres

        return $pdf->stream('saldos_acumulados_cliente_dias.pdf');
    }

    public function saldosAcumuladosClienteFechasPdf(Request $request)
    {

        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $reportes = DB::table('ventas as v')
            ->join('clientes as c', 'c.id', '=', 'v.cliente_id')
            ->selectRaw('
                v.cliente_id,
                MAX(c.razon_social) as cliente_nombre,
                SUM(v.saldo) as saldo,
                COUNT(v.id) as items,
                MAX(c.direccion) as domicilio,
                MAX(c.telefono) as telefono
            ')
            ->where('v.estado', '!=', 'anulada')
            ->where('v.saldo', '>', 0)
            ->whereBetween('v.fecha_venta', [$ini, $fin])
            ->groupBy('v.cliente_id')
            ->orderBy('cliente_nombre', 'asc')
            ->get();

        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        $pdf = Pdf::loadView(
            'cuenta-cliente.reportes-general.saldos_acumulados_cliente_fechas',
            compact('reportes', 'totSaldo', 'totItems', 'ini', 'fin')
        )->setPaper('a4', 'portrait'); // o landscape si prefieres

        return $pdf->stream('saldos_acumulados_cliente_fechas.pdf');
    }

    public function saldoFechaSolicitadaPdf(Request $request)
    {
        $data = $request->validate([
            'fecha' => ['required', 'date'],
        ]);

        // Si quieres evitar Carbon aquí también, puedes dejarlo como string:
        $fecha = Carbon::parse($data['fecha'])->startOfDay();

        $mostrarColCliente = true;

        $reportes = Venta::query()
            ->selectRaw('
                ventas.id,
                ventas.fecha_venta,
                ventas.fecha_vencimiento,
                DATE_FORMAT(ventas.fecha_venta, "%d/%m/%Y") as fecha_venta_fmt,
                DATE_FORMAT(ventas.fecha_vencimiento, "%d/%m/%Y") as fecha_vencimiento_fmt,
                CONCAT(ventas.comprobante_tipo_codigo," ",ventas.serie,"-",ventas.correlativo) as documento,
                ventas.pago_forma_nombre as tipo_venta,

                ventas.total,
                ventas.acuenta,
                ventas.abonos,
                ventas.saldo,

                ventas.cliente_id,
                ventas.cliente_nombre,
                ventas.user_nombre
            ')
            ->withCount(['detalles as items'])
            ->whereDate('ventas.fecha_venta', '<=', $fecha)
            ->where('ventas.estado', '!=', 'anulada')
            ->where('ventas.saldo', '>', 0)
            ->orderBy('ventas.cliente_nombre', 'asc')
            ->orderBy('ventas.fecha_venta', 'asc')
            ->get();

        $totTotal = (float) $reportes->sum('total');
        $totAcuenta = (float) $reportes->sum('acuenta');
        $totAbonos = (float) $reportes->sum('abonos');
        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        $pdf = Pdf::loadView('cuenta-cliente.reportes-general.saldo_fecha_solicitada', compact(
            'reportes',
            'fecha',
            'mostrarColCliente',
            'totTotal',
            'totAcuenta',
            'totAbonos',
            'totSaldo',
            'totItems'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream('saldo_fecha_seleccionada.pdf');
    }

    /*
    public function ventasAcumuladaPorProductoPdf(Request $request)
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
            ->whereBetween('v.fecha_venta', [$ini, $fin]) // puedes dejarlo
            ->groupBy('venta_detalles.producto_id', 'venta_detalles.producto_nombre', 'p.empaque')
            ->orderBy('venta_detalles.producto_nombre')
            ->get();

        // Totales generales (si quieres al final del PDF)
        $totCantidad = (float) $reportes->sum('cantidad_total');
        $totKg       = (float) $reportes->sum('kg_total');
        $totImporte  = (float) $reportes->sum('importe_total');

        $pdf = Pdf::loadView(
            'cuenta-cliente.reportes-general.ventas_acumuladas_producto',
            compact('reportes', 'ini', 'fin', 'totCantidad', 'totKg', 'totImporte')
        )->setPaper('a4', 'portrait');

        return $pdf->stream('ventas_acumuladas_producto.pdf');
    }
    */

    public function resumenCreditosPorCobrarPdf()
    {
        $reportes = DB::table('ventas as v')
            ->selectRaw('
                v.user_nombre as vendedor,

                SUM(CASE WHEN DATEDIFF(CURDATE(), v.fecha_venta) <= 3 
                    THEN v.saldo ELSE 0 END) as d_3,

                SUM(CASE WHEN DATEDIFF(CURDATE(), v.fecha_venta) BETWEEN 4 AND 7 
                    THEN v.saldo ELSE 0 END) as d_7,

                SUM(CASE WHEN DATEDIFF(CURDATE(), v.fecha_venta) BETWEEN 8 AND 15 
                    THEN v.saldo ELSE 0 END) as d_15,

                SUM(CASE WHEN DATEDIFF(CURDATE(), v.fecha_venta) BETWEEN 16 AND 30 
                    THEN v.saldo ELSE 0 END) as d_30,

                SUM(CASE WHEN DATEDIFF(CURDATE(), v.fecha_venta) >= 31 
                    THEN v.saldo ELSE 0 END) as d_31,

                SUM(v.saldo) as acumulado,

                COUNT(CASE WHEN v.saldo > 0 THEN 1 END) as total_docs
            ')
            ->where('v.saldo', '>', 0)
            ->where('v.estado', '!=', 'anulada') // ✅ excluir anuladas
            ->groupBy('v.user_nombre')
            ->orderBy('v.user_nombre')
            ->get();

        $pdf = Pdf::loadView(
            'cuenta-cliente.reportes-general.resumen_creditos_por_cobrar',
            compact('reportes')
        )->setPaper('a4', 'landscape');

        return $pdf->stream('resumen_creditos_por_cobrar.pdf');
    }

    // endregion

    // region Estado de cuenta del cliente

    /**
     * Genera el estado de cuenta detallado.
     *
     * El saldo se reconstruye históricamente con las ventas y los cobros
     * registrados hasta cada fecha de corte. No se utiliza ventas.saldo como
     * saldo histórico, porque ese campo representa el saldo actual.
     */
    // region Estado de Cuenta Detallado

    public function estadoCuentaClientePdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => [
                'required',
                'date',
                'after_or_equal:fecha_inicio',
            ],
            'cliente_ids' => [
                'required',
                'array',
                'size:1',
            ],
            'cliente_ids.0' => [
                'required',
                'integer',
            ],
        ]);

        $ini = Carbon::parse(
            $data['fecha_inicio']
        )->startOfDay();

        $fin = Carbon::parse(
            $data['fecha_fin']
        )->endOfDay();

        $clienteId = (int) $data['cliente_ids'][0];

        $clienteData = Cliente::query()
            ->select(
                'id',
                'razon_social',
                'direccion',
                'telefono'
            )
            ->findOrFail($clienteId);

        $clienteNombre = $clienteData->id
            .' - '
            .$clienteData->razon_social;

        $datosReporte = $this->prepararDatosEstadoCuentaCliente(
            $clienteId,
            $ini,
            $fin
        );

        $pdf = Pdf::loadView(
            'cuenta-cliente.reportes.estado_cuenta',
            array_merge(
                [
                    'ini' => $ini,
                    'fin' => $fin,
                    'clienteId' => $clienteId,
                    'clienteNombre' => $clienteNombre,
                    'clienteData' => $clienteData,
                ],
                $datosReporte
            )
        )->setPaper('a4', 'portrait');

        $nombreSanitizado = preg_replace(
            '/[^a-zA-Z0-9\s\-]/',
            '',
            $clienteNombre
        );

        $nombreSanitizado = preg_replace(
            '/\s+/',
            '_',
            trim($nombreSanitizado)
        );

        return $pdf->stream(
            'estado_cuenta_'
            .$nombreSanitizado
            .'.pdf'
        );
    }

    // endregion

    /**
     * Genera el estado de cuenta simplificado utilizando exactamente la misma
     * información y los mismos saldos que el reporte detallado.
     */
    // region Estado de Cuenta Simplificado

    public function estadoCuentaSimplificadoClientePdf(
        Request $request
    ) {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => [
                'required',
                'date',
                'after_or_equal:fecha_inicio',
            ],
            'cliente_ids' => [
                'required',
                'array',
                'size:1',
            ],
            'cliente_ids.0' => [
                'required',
                'integer',
            ],
        ]);

        $ini = Carbon::parse(
            $data['fecha_inicio']
        )->startOfDay();

        $fin = Carbon::parse(
            $data['fecha_fin']
        )->endOfDay();

        $clienteId = (int) $data['cliente_ids'][0];

        $clienteData = Cliente::query()
            ->select(
                'id',
                'razon_social',
                'direccion',
                'telefono'
            )
            ->findOrFail($clienteId);

        $clienteNombre = $clienteData->id
            .' - '
            .$clienteData->razon_social;

        /*
         * El simplificado usa exactamente la misma información
         * que el estado de cuenta detallado.
         */
        $datosReporte = $this->prepararDatosEstadoCuentaCliente(
            $clienteId,
            $ini,
            $fin
        );

        $pdf = Pdf::loadView(
            'cuenta-cliente.reportes.estado_cuenta_simplificado',
            array_merge(
                [
                    'ini' => $ini,
                    'fin' => $fin,
                    'clienteId' => $clienteId,
                    'clienteNombre' => $clienteNombre,
                    'clienteData' => $clienteData,
                ],
                $datosReporte
            )
        )->setPaper('a4', 'portrait');

        $nombreSanitizado = preg_replace(
            '/[^a-zA-Z0-9\s\-]/',
            '',
            $clienteNombre
        );

        $nombreSanitizado = preg_replace(
            '/\s+/',
            '_',
            trim($nombreSanitizado)
        );

        return $pdf->stream(
            'estado_cuenta_simplificado_'
            .$nombreSanitizado
            .'.pdf'
        );
    }

    // endregion

    // endregion

    // region Consultas auxiliares del estado de cuenta

    /**
     * Consulta única de documentos que mantienen deuda actual.
     */
    private function consultaVentasPendientesCliente(
        int $clienteId,
        ?Carbon $fechaCorte = null
    ): Builder {
        return Venta::query()
            ->where('ventas.cliente_id', $clienteId)
            ->when(
                $fechaCorte,
                fn (Builder $query, Carbon $corte) => $query->where(
                    'ventas.fecha_venta',
                    '<=',
                    $corte
                )
            )
            ->whereRaw("
                LOWER(TRIM(COALESCE(ventas.estado, '')))
                <> 'anulada'
            ")
            ->where('ventas.saldo', '>', 0);
    }

    /**
     * Retorna el saldo ACTUAL registrado en ventas.saldo.
     *
     * No vuelve a sumar el total histórico de todas las ventas. Solo considera
     * ventas vigentes, no anuladas y cuyo saldo actual sea mayor que cero.
     */
    private function calcularSaldoActualCliente(int $clienteId): float
    {
        return round(
            (float) $this->consultaVentasPendientesCliente(
                $clienteId,
                now()->endOfDay()
            )->sum('ventas.saldo'),
            2
        );
    }

    /**
     * Fuente única para el estado de cuenta detallado y simplificado.
     *
     * Reglas:
     * - Las ventas anuladas no aparecen ni suman.
     * - El saldo final es el saldo ACTUAL de ventas.saldo.
     * - Las ventas totalmente pagadas no suman al saldo final.
     * - Los pagos del rango se muestran y se descuentan movimiento por movimiento.
     */
    private function prepararDatosEstadoCuentaCliente(
        int $clienteId,
        Carbon $ini,
        Carbon $fin
    ): array {
        // region Pagos aplicados dentro del rango

        $provisionales = DB::table('venta_provisional_detalles as vpd')
            ->join(
                'venta_provisionales as vp',
                'vp.id',
                '=',
                'vpd.venta_provisional_id'
            )
            ->leftJoin('ventas as venta', 'venta.id', '=', 'vpd.venta_id')
            ->selectRaw('
                vpd.id AS detalle_id,
                vpd.venta_id,
                vp.id AS provisional_id,
                vp.fecha_provisional,
                vpd.monto,
                vpd.comentario,
                vp.numero_recibo,
                vpd.comprobante_tipo_codigo,
                vpd.serie,
                vpd.correlativo
            ')
            ->where('vp.cliente_id', $clienteId)
            ->whereBetween('vp.fecha_provisional', [$ini, $fin])
            ->where(function ($query) {
                $query->whereNull('vpd.venta_id')
                    ->orWhereRaw("LOWER(TRIM(COALESCE(venta.estado, ''))) <> 'anulada'");
            })
            ->orderBy('vp.fecha_provisional')
            ->orderBy('vp.id')
            ->orderBy('vpd.id')
            ->get();

        $provConVenta = $provisionales
            ->whereNotNull('venta_id')
            ->values();

        $provSinVenta = $provisionales
            ->whereNull('venta_id')
            ->values();

        $pagosPorVenta = $provConVenta
            ->groupBy('venta_id')
            ->map(fn ($pagos) => $pagos->values());

        $ventaIdsPagadasEnRango = $provConVenta
            ->pluck('venta_id')
            ->unique()
            ->values()
            ->all();

        // endregion

        // region Ventas visibles

        $ventas = DB::table('ventas as venta')
            ->select([
                'venta.id',
                'venta.fecha_venta',
                'venta.fecha_vencimiento',
                'venta.comprobante_tipo_codigo',
                'venta.pago_forma_nombre',
                'venta.serie',
                'venta.correlativo',
                'venta.total',
                'venta.acuenta',
                'venta.abonos',
                'venta.saldo',
            ])
            ->where('venta.cliente_id', $clienteId)
            ->whereRaw("LOWER(TRIM(COALESCE(venta.estado, ''))) <> 'anulada'")
            ->where('venta.fecha_venta', '<=', $fin)
            ->where(function ($query) use ($ini, $fin, $ventaIdsPagadasEnRango) {
                // Ventas del rango que todavía tienen deuda.
                $query->where(function ($subQuery) use ($ini, $fin) {
                    $subQuery->whereBetween('venta.fecha_venta', [$ini, $fin])
                        ->where('venta.saldo', '>', 0);
                });

                // Ventas anteriores que aún tienen deuda.
                $query->orWhere(function ($subQuery) use ($ini) {
                    $subQuery->where('venta.fecha_venta', '<', $ini)
                        ->where('venta.saldo', '>', 0);
                });

                // Ventas que recibieron pagos en el rango, aunque ya quedaron pagadas.
                if (! empty($ventaIdsPagadasEnRango)) {
                    $query->orWhereIn('venta.id', $ventaIdsPagadasEnRango);
                }
            })
            ->orderBy('venta.fecha_venta')
            ->orderBy('venta.id')
            ->get();

        // endregion

        // region Reconstrucción visual de cada movimiento

        $ventas = $ventas->map(function ($venta) use ($pagosPorVenta) {
            $venta->documento = trim(
                (! empty($venta->comprobante_tipo_codigo)
                    ? $venta->comprobante_tipo_codigo.' '
                    : '')
                .($venta->serie ?? '')
                .'-'
                .($venta->correlativo ?? '')
            );

            $venta->credito_base = round(
                max(
                    (float) ($venta->total ?? 0)
                    - (float) ($venta->acuenta ?? 0),
                    0
                ),
                2
            );

            $venta->saldo_actual = round(
                max((float) ($venta->saldo ?? 0), 0),
                2
            );

            $pagos = $pagosPorVenta
                ->get($venta->id, collect())
                ->sortBy(fn ($pago) => sprintf(
                    '%s-%012d',
                    $pago->fecha_provisional ?? '',
                    (int) ($pago->detalle_id ?? 0)
                ))
                ->values();

            $totalPagadoRango = round((float) $pagos->sum('monto'), 2);

            /*
             * Partimos del saldo actual más los pagos hechos en el rango.
             * Al restar cada cobranza se llega exactamente a ventas.saldo.
             */
            $saldoMovimiento = round(
                $venta->saldo_actual + $totalPagadoRango,
                2
            );

            $venta->saldo_inicio_rango = $saldoMovimiento;
            $venta->pagos_antes_ini = 0.0;

            $venta->pagos = $pagos->map(
                function ($pago) use (&$saldoMovimiento) {
                    $saldoMovimiento = round(
                        $saldoMovimiento - (float) ($pago->monto ?? 0),
                        2
                    );

                    $pago->saldo_despues = max($saldoMovimiento, 0);

                    return $pago;
                }
            );

            // El saldo final de la fila siempre coincide con el saldo actual guardado.
            $venta->saldo_final_rango = $venta->saldo_actual;

            return $venta;
        })->values();

        // endregion

        // region Saldos

        /*
         * Saldo anterior visible: deuda de ventas anteriores al rango antes de
         * descontar los pagos realizados dentro del rango.
         */
        $saldoInicial = round(
            (float) $ventas
                ->filter(fn ($venta) => Carbon::parse($venta->fecha_venta)->lt($ini))
                ->sum('saldo_inicio_rango'),
            2
        );

        /*
         * Saldo final ACTUAL: únicamente ventas vigentes con saldo pendiente.
         * Este total debe coincidir con Créditos por cobrar.
         */
        $saldoFinal = $this->calcularSaldoActualCliente($clienteId);

        // endregion

        // region Adelantos sin venta asociada

        $aplicadoPorProvisional = DB::table('venta_provisional_detalles as detalle')
            ->selectRaw('
                detalle.venta_provisional_id,
                COALESCE(
                    SUM(
                        CASE
                            WHEN detalle.venta_id IS NOT NULL THEN detalle.monto
                            ELSE 0
                        END
                    ),
                    0
                ) AS monto_aplicado
            ')
            ->groupBy('detalle.venta_provisional_id');

        $abonosSueltos = DB::table('venta_provisionales as vp')
            ->leftJoinSub(
                $aplicadoPorProvisional,
                'aplicado',
                function ($join) {
                    $join->on(
                        'aplicado.venta_provisional_id',
                        '=',
                        'vp.id'
                    );
                }
            )
            ->selectRaw('
                vp.id,
                vp.fecha_provisional,
                vp.numero_recibo,
                CASE
                    WHEN (
                        COALESCE(vp.monto, 0)
                        - COALESCE(aplicado.monto_aplicado, 0)
                    ) > 0
                    THEN (
                        COALESCE(vp.monto, 0)
                        - COALESCE(aplicado.monto_aplicado, 0)
                    )
                    ELSE 0
                END AS monto
            ')
            ->where('vp.cliente_id', $clienteId)
            ->whereBetween('vp.fecha_provisional', [$ini, $fin])
            ->whereRaw('
                (
                    COALESCE(vp.monto, 0)
                    - COALESCE(aplicado.monto_aplicado, 0)
                ) > 0
            ')
            ->orderBy('vp.fecha_provisional')
            ->orderBy('vp.id')
            ->get();

        // endregion

        // region Totales

        $totCreditoMostrado = round(
            (float) $ventas
                ->filter(function ($venta) use ($ini, $fin) {
                    $fechaVenta = Carbon::parse($venta->fecha_venta);

                    return $fechaVenta->betweenIncluded($ini, $fin);
                })
                ->sum('credito_base'),
            2
        );

        $totPagoVentas = round((float) $provConVenta->sum('monto'), 2);
        $totPagoRango = $totPagoVentas;
        $totAbonoSuelto = round((float) $abonosSueltos->sum('monto'), 2);

        // endregion

        return [
            'saldoInicial' => $saldoInicial,
            'saldoFinal' => $saldoFinal,
            'ventas' => $ventas,
            'provSinVenta' => $provSinVenta,
            'abonosSueltos' => $abonosSueltos,
            'totCreditoMostrado' => $totCreditoMostrado,
            'totPagoRango' => $totPagoRango,
            'totPagoVentas' => $totPagoVentas,
            'totAbonoSuelto' => $totAbonoSuelto,
        ];
    }

    // endregion
}
