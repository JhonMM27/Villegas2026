<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class CuentaCorrienteClienteController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:cuenta_corriente_report')->only(['index', 'resumenCliente', 'ventasAgrupadaProductoClientePdf', 'ventasDetallePdf', 'detalleCreditosPorCobrarPdf', 'creditosPorCobrarClienteTodosPdf', 'creditosPorCobrarClienteFechasPdf', 'ventasGeneralFechasPdf', 'saldosTodosPdf', 'creditosPorCobrarIndex', 'creditosPorCobrarData']);
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

    public function creditosPorCobrarClienteTodosPdf(Request $request)
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

        // Abonos sueltos (adelantos sin venta asociada)
        $abonosSueltos = DB::table('venta_provisionales as v')
            ->selectRaw('
                v.id,
                v.fecha_provisional,
                v.numero_recibo,
                v.monto
            ')
            ->where('v.cliente_id', $clienteId)
            ->where('v.tipo', 'ADELANTO')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('venta_provisional_detalles as vpd')
                    ->whereColumn('vpd.venta_provisional_id', 'v.id')
                    ->whereNotNull('vpd.venta_id');
            })
            ->orderBy('v.fecha_provisional')
            ->get();

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
            ->where('ventas.cliente_id', $clienteId)
            ->where('ventas.estado', '!=', 'anulada')
            ->where('ventas.saldo', '>', 0)
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

        $pdf = Pdf::loadView('cuenta-cliente.reportes.creditos_por_cobrar_cliente_todos', compact(
            'reportes',
            'clienteNombre',
            'totTotal',
            'totAcuenta',
            'totAbonos',
            'totSaldo',
            'totItems',
            'abonosSueltos'
        ))->setPaper('a4', 'portrait');

        $nombreSanitizado = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $clienteNombre ?? '');
        $nombreSanitizado = preg_replace('/\s+/', '_', trim($nombreSanitizado));
        $partes = explode('_', $nombreSanitizado);
        $idPart = count($partes) > 0 ? array_shift($partes) : '';
        $nombreLimpio = implode('_', $partes);
        $nombreArchivo = $nombreLimpio.'_'.$idPart.'.pdf';

        return $pdf->stream('creditos_por_cobrar_'.$nombreArchivo);
    }

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

    /* ***GENERAL */
    public function creditosPorCobrarTodosPdf(Request $request)
    {
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

        $pdf = Pdf::loadView('cuenta-cliente.reportes-general.creditos_por_cobrar_todos', compact(
            'reportes',
            'totTotal',
            'totAcuenta',
            'totAbonos',
            'totSaldo',
            'totItems'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream('creditos_por_cobrar_todos.pdf');
    }

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
            ->whereDate('ventas.fecha_venta', '<=', DB::raw('DATE_SUB(CURDATE(), INTERVAL ? DAY)'))
            ->addBinding($dias, 'where')
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
            ->whereDate('ventas.fecha_venta', '<=', DB::raw('DATE_SUB(CURDATE(), INTERVAL ? DAY)'))
            ->addBinding($dias, 'where')
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
            ->groupBy('v.user_nombre')
            ->orderBy('v.user_nombre')
            ->get();

        $pdf = Pdf::loadView(
            'cuenta-cliente.reportes-general.resumen_creditos_por_cobrar',
            compact('reportes')
        )->setPaper('a4', 'landscape');

        return $pdf->stream('resumen_creditos_por_cobrar.pdf');
    }

    public function estadoCuentaClientePdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'cliente_ids' => ['required', 'array', 'size:1'],
            'cliente_ids.0' => ['required', 'integer'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $clienteId = (int) $data['cliente_ids'][0];

        $clienteData = Cliente::select('id', 'razon_social', 'direccion', 'telefono')
            ->find($clienteId);

        $clienteNombre = $clienteData
            ? ($clienteData->id.' - '.$clienteData->razon_social)
            : (string) $clienteId;

        // =========================
        // SALDO INICIAL / FINAL (según tu regla: solo ventas.saldo)
        // =========================
        $saldoInicial = (float) DB::table('ventas')
            ->where('cliente_id', $clienteId)
            ->where('saldo', '>', 0)
            ->where('fecha_venta', '<', $ini)
            ->sum('saldo');

        $saldoFinal = (float) DB::table('ventas')
            ->where('cliente_id', $clienteId)
            ->where('saldo', '>', 0)
            ->where('fecha_venta', '<=', $fin)
            ->sum('saldo');

        // =========================
        // 1) Provisionales del rango (linked y unlinked)
        // =========================
        $provisionales = DB::table('venta_provisional_detalles as vp')
            ->join('venta_provisionales as v', 'v.id', '=', 'vp.venta_provisional_id')
            ->selectRaw('
                vp.id,
                vp.venta_id,
                v.fecha_provisional,
                vp.monto,
                vp.comentario,
                v.numero_recibo,
                vp.comprobante_tipo_codigo,
                vp.serie,
                vp.correlativo
            ')
            ->where('v.cliente_id', $clienteId)
            ->whereBetween('v.fecha_provisional', [$ini, $fin])
            ->orderBy('v.fecha_provisional')
            ->orderBy('vp.id')
            ->get();

        $provConVenta = $provisionales->whereNotNull('venta_id');
        $provSinVenta = $provisionales->whereNull('venta_id')->values();

        $ventaIdsPagadasEnRango = $provConVenta->pluck('venta_id')->unique()->values()->all();

        // =========================
        // 2) Ventas a mostrar:
        // A) ventas en rango con saldo>0
        // B) ventas con pagos en rango (aunque venta esté fuera)
        // =========================
        $ventas = DB::table('ventas')
            ->selectRaw('
                id,
                fecha_venta,
                fecha_vencimiento,
                comprobante_tipo_codigo,
                serie,
                correlativo,
                total,
                acuenta,
                abonos,
                saldo
            ')
            ->where('cliente_id', $clienteId)
            ->where('estado', '!=', 'anulada')
            ->where(function ($q) use ($ini, $fin, $ventaIdsPagadasEnRango) {
                $q->where(function ($q2) use ($ini, $fin) {
                    $q2->whereBetween('fecha_venta', [$ini, $fin])
                        ->where('saldo', '>', 0);
                });

                if (! empty($ventaIdsPagadasEnRango)) {
                    $q->orWhereIn('id', $ventaIdsPagadasEnRango);
                }
            })
            ->orderBy('fecha_venta')
            ->orderBy('id')
            ->get();

        // Agrupar provisionales (del rango) por venta
        $pagosPorVenta = $provConVenta->groupBy('venta_id')->map->values();

        // =========================
        // 3) Pagos ANTES del rango por venta (para saldo correcto en cada pago)
        // =========================
        $ventaIds = $ventas->pluck('id')->unique()->values()->all();

        $pagosAntesIniPorVenta = collect();

        if (! empty($ventaIds)) {
            $pagosAntesIniPorVenta = DB::table('venta_provisional_detalles as vp')
                ->join('venta_provisionales as v', 'v.id', '=', 'vp.venta_provisional_id')
                ->selectRaw('vp.venta_id, COALESCE(SUM(vp.monto),0) as total')
                ->where('v.cliente_id', $clienteId)
                ->whereNotNull('vp.venta_id')
                ->whereIn('vp.venta_id', $ventaIds)
                ->where('v.fecha_provisional', '<', $ini)
                ->groupBy('vp.venta_id')
                ->pluck('total', 'vp.venta_id');
        }

        // =========================
        // 4) Armar jerarquía + calcular:
        // credito_base = total - acuenta
        // saldo_inicio_rango = credito_base - pagos_antes_ini
        // saldo_despues_pago (por cada pago en rango) con acumulado
        // =========================
        $ventas = $ventas->map(function ($v) use ($pagosPorVenta, $pagosAntesIniPorVenta) {
            $v->documento = trim(($v->comprobante_tipo_codigo ? $v->comprobante_tipo_codigo.' ' : '').($v->serie ?? '').'-'.($v->correlativo ?? ''));

            $total = (float) $v->total;
            $acuenta = (float) $v->acuenta;

            $v->credito_base = $total - $acuenta;

            $pagosAntes = (float) ($pagosAntesIniPorVenta[$v->id] ?? 0);
            $v->pagos_antes_ini = $pagosAntes;

            $v->saldo_inicio_rango = $v->credito_base - $pagosAntes;

            $pagos = $pagosPorVenta->get($v->id, collect())->values();

            $acum = 0.0;
            $pagos = $pagos->map(function ($p) use (&$acum, $v) {
                $acum += (float) $p->monto;
                $p->saldo_despues = $v->credito_base - ($v->pagos_antes_ini + $acum);

                return $p;
            });

            $v->pagos = $pagos;

            return $v;
        });

        // total adelantos
        // =========================
        // ADELANTOS / ABONOS SUELTOS (cabecera sin detalles)
        // =========================
        $abonosSueltos = DB::table('venta_provisionales as v')
            ->selectRaw('
                v.id,
                v.fecha_provisional,
                v.numero_recibo,
                v.monto
            ')
            ->where('v.cliente_id', $clienteId)
            ->whereBetween('v.fecha_provisional', [$ini, $fin])
            ->where('v.tipo', 'ADELANTO') // ✅ el criterio correcto
            ->orderBy('v.fecha_provisional')
            ->orderBy('v.id')
            ->get();

        $totAdelantos = (float) $abonosSueltos->sum('monto');

        // Totales (para pie)
        $totPagoRango = (float) $provisionales->sum('monto');
        $totPagoVentas = (float) $provConVenta->sum('monto');
        $totAbonoSuelto = $totAdelantos;
        $totCreditoMostrado = (float) $ventas->sum('credito_base');

        $pdf = PDF::loadView('cuenta-cliente.reportes.estado_cuenta', [
            'ini' => $ini,
            'fin' => $fin,
            'clienteId' => $clienteId,
            'clienteNombre' => $clienteNombre,
            'clienteData' => $clienteData,
            'saldoInicial' => $saldoInicial,
            'saldoFinal' => $saldoFinal,

            'ventas' => $ventas,
            'provSinVenta' => $provSinVenta,

            'totCreditoMostrado' => $totCreditoMostrado,
            'totPagoRango' => $totPagoRango,
            'totPagoVentas' => $totPagoVentas,
            'totAbonoSuelto' => $totAbonoSuelto,
            'abonosSueltos' => $abonosSueltos,
        ])->setPaper('a4', 'portrait');

        $nombreSanitizado = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $clienteNombre ?? '');
        $nombreSanitizado = preg_replace('/\s+/', '_', trim($nombreSanitizado));
        $partes = explode('_', $nombreSanitizado);
        $idPart = count($partes) > 0 ? array_shift($partes) : '';
        $nombreLimpio = implode('_', $partes);
        $nombreArchivo = $nombreLimpio.'_'.$idPart.'.pdf';

        return $pdf->stream('estado_cuenta_'.$nombreArchivo);
    }

    public function estadoCuentaSimplificadoClientePdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'cliente_ids' => ['required', 'array', 'size:1'],
            'cliente_ids.0' => ['required', 'integer'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $clienteId = (int) $data['cliente_ids'][0];

        $clienteData = Cliente::select('id', 'razon_social', 'direccion', 'telefono')
            ->find($clienteId);

        $clienteNombre = $clienteData
            ? ($clienteData->id.' - '.$clienteData->razon_social)
            : (string) $clienteId;

        // =========================
        // SALDO INICIAL / FINAL (según tu regla: solo ventas.saldo)
        // =========================
        $saldoInicial = (float) DB::table('ventas')
            ->where('cliente_id', $clienteId)
            ->where('saldo', '>', 0)
            ->where('fecha_venta', '<', $ini)
            ->sum('saldo');

        $saldoFinal = (float) DB::table('ventas')
            ->where('cliente_id', $clienteId)
            ->where('saldo', '>', 0)
            ->where('fecha_venta', '<=', $fin)
            ->sum('saldo');

        // =========================
        // 1) Provisionales del rango (linked y unlinked)
        // =========================
        $provisionales = DB::table('venta_provisional_detalles as vp')
            ->join('venta_provisionales as v', 'v.id', '=', 'vp.venta_provisional_id')
            ->selectRaw('
                vp.id,
                vp.venta_id,
                v.fecha_provisional,
                vp.monto,
                vp.comentario,
                v.numero_recibo,
                vp.comprobante_tipo_codigo,
                vp.serie,
                vp.correlativo
            ')
            ->where('v.cliente_id', $clienteId)
            ->whereBetween('v.fecha_provisional', [$ini, $fin])
            ->orderBy('v.fecha_provisional')
            ->orderBy('vp.id')
            ->get();

        $provConVenta = $provisionales->whereNotNull('venta_id');
        $provSinVenta = $provisionales->whereNull('venta_id')->values();

        $ventaIdsPagadasEnRango = $provConVenta->pluck('venta_id')->unique()->values()->all();

        // =========================
        // 2) Ventas a mostrar:
        // A) ventas en rango con saldo>0
        // B) ventas con pagos en rango (aunque venta esté fuera)
        // =========================
        $ventas = DB::table('ventas')
            ->selectRaw('
                id,
                fecha_venta,
                fecha_vencimiento,
                comprobante_tipo_codigo,
                pago_forma_nombre,
                serie,
                correlativo,
                total,
                acuenta,
                abonos,
                saldo
            ')
            ->where('cliente_id', $clienteId)
            ->where(function ($q) use ($ini, $fin, $ventaIdsPagadasEnRango) {
                $q->where(function ($q2) use ($ini, $fin) {
                    $q2->whereBetween('fecha_venta', [$ini, $fin])
                        ->where('saldo', '>', 0);
                });

                if (! empty($ventaIdsPagadasEnRango)) {
                    $q->orWhereIn('id', $ventaIdsPagadasEnRango);
                }
            })
            ->orderBy('fecha_venta')
            ->orderBy('id')
            ->get();

        // Agrupar provisionales (del rango) por venta
        $pagosPorVenta = $provConVenta->groupBy('venta_id')->map->values();

        // =========================
        // 3) Pagos ANTES del rango por venta (para saldo correcto en cada pago)
        // =========================
        $ventaIds = $ventas->pluck('id')->unique()->values()->all();

        $pagosAntesIniPorVenta = collect();

        if (! empty($ventaIds)) {
            $pagosAntesIniPorVenta = DB::table('venta_provisional_detalles as vp')
                ->join('venta_provisionales as v', 'v.id', '=', 'vp.venta_provisional_id')
                ->selectRaw('vp.venta_id, COALESCE(SUM(vp.monto),0) as total')
                ->where('v.cliente_id', $clienteId)
                ->whereNotNull('vp.venta_id')
                ->whereIn('vp.venta_id', $ventaIds)
                ->where('v.fecha_provisional', '<', $ini)
                ->groupBy('vp.venta_id')
                ->pluck('total', 'vp.venta_id');
        }

        // =========================
        // 4) Armar jerarquía + calcular:
        // credito_base = total - acuenta
        // saldo_inicio_rango = credito_base - pagos_antes_ini
        // saldo_despues_pago (por cada pago en rango) con acumulado
        // =========================
        $ventas = $ventas->map(function ($v) use ($pagosPorVenta, $pagosAntesIniPorVenta) {
            $v->documento = trim(($v->comprobante_tipo_codigo ? $v->comprobante_tipo_codigo.' ' : '').($v->serie ?? '').'-'.($v->correlativo ?? ''));

            $total = (float) $v->total;
            $acuenta = (float) $v->acuenta;

            $v->credito_base = $total - $acuenta;

            $pagosAntes = (float) ($pagosAntesIniPorVenta[$v->id] ?? 0);
            $v->pagos_antes_ini = $pagosAntes;

            $v->saldo_inicio_rango = $v->credito_base - $pagosAntes;

            $pagos = $pagosPorVenta->get($v->id, collect())->values();

            $acum = 0.0;
            $pagos = $pagos->map(function ($p) use (&$acum, $v) {
                $acum += (float) $p->monto;
                $p->saldo_despues = $v->credito_base - ($v->pagos_antes_ini + $acum);

                return $p;
            });

            $v->pagos = $pagos;

            return $v;
        });

        // total adelantos
        // =========================
        // ADELANTOS / ABONOS SUELTOS (cabecera sin detalles)
        // =========================
        $abonosSueltos = DB::table('venta_provisionales as v')
            ->selectRaw('
                v.id,
                v.fecha_provisional,
                v.numero_recibo,
                v.monto
            ')
            ->where('v.cliente_id', $clienteId)
            ->whereBetween('v.fecha_provisional', [$ini, $fin])
            ->where('v.tipo', 'ADELANTO') // ✅ el criterio correcto
            ->orderBy('v.fecha_provisional')
            ->orderBy('v.id')
            ->get();

        $totAdelantos = (float) $abonosSueltos->sum('monto');

        // Totales (para pie)
        $totPagoRango = (float) $provisionales->sum('monto');
        $totPagoVentas = (float) $provConVenta->sum('monto');
        $totAbonoSuelto = $totAdelantos;
        $totCreditoMostrado = (float) $ventas->sum('credito_base');

        $pdf = PDF::loadView('cuenta-cliente.reportes.estado_cuenta_simplificado', [
            'ini' => $ini,
            'fin' => $fin,
            'clienteId' => $clienteId,
            'clienteNombre' => $clienteNombre,
            'clienteData' => $clienteData,
            'saldoInicial' => $saldoInicial,
            'saldoFinal' => $saldoFinal,

            'ventas' => $ventas,
            'provSinVenta' => $provSinVenta,

            'totCreditoMostrado' => $totCreditoMostrado,
            'totPagoRango' => $totPagoRango,
            'totPagoVentas' => $totPagoVentas,
            'totAbonoSuelto' => $totAbonoSuelto,
            'abonosSueltos' => $abonosSueltos,
        ])->setPaper('a4', 'portrait');

        $nombreSanitizado = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $clienteNombre ?? '');
        $nombreSanitizado = preg_replace('/\s+/', '_', trim($nombreSanitizado));
        $partes = explode('_', $nombreSanitizado);
        $idPart = count($partes) > 0 ? array_shift($partes) : '';
        $nombreLimpio = implode('_', $partes);
        $nombreArchivo = $nombreLimpio.'_'.$idPart.'.pdf';

        return $pdf->stream('estado_cuenta_simplificado_'.$nombreArchivo);
    }

    /*
    public function estadoCuentaSimplificadoClientePdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio'  => ['required', 'date'],
            'fecha_fin'     => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'cliente_ids'   => ['required', 'array', 'size:1'],
            'cliente_ids.0' => ['required', 'integer'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $clienteId = (int) $data['cliente_ids'][0];

        $clienteData = Cliente::select('id', 'razon_social', 'direccion', 'telefono')->find($clienteId);
        $clienteNombre = $clienteData ? ($clienteData->id.' - '.$clienteData->razon_social) : (string)$clienteId;

        $saldoInicial = (float) DB::table('ventas')
            ->where('cliente_id', $clienteId)
            ->where('saldo', '>', 0)
            ->where('fecha_venta', '<', $ini)
            ->sum('saldo');

        // =========================
        // MISMO BLOQUE QUE EL DETALLADO
        // =========================
        $provisionales = DB::table('venta_provisional_detalles as vp')
            ->join('venta_provisionales as v', 'v.id', '=', 'vp.venta_provisional_id')
            ->selectRaw("
                vp.id,
                vp.venta_id,
                v.fecha_provisional,
                vp.monto,
                vp.comentario,
                v.numero_recibo,
                vp.comprobante_tipo_codigo,
                vp.serie,
                vp.correlativo
            ")
            ->where('v.cliente_id', $clienteId)
            ->whereBetween('v.fecha_provisional', [$ini, $fin])
            ->orderBy('v.fecha_provisional')
            ->orderBy('vp.id')
            ->get();

        $provConVenta = $provisionales->whereNotNull('venta_id');
        $provSinVenta = $provisionales->whereNull('venta_id')->values();

        $ventaIdsPagadasEnRango = $provConVenta->pluck('venta_id')->unique()->values()->all();

        $ventas = DB::table('ventas')
            ->selectRaw("
                id,
                fecha_venta,
                fecha_vencimiento,
                comprobante_tipo_codigo,
                serie,
                correlativo,
                pago_forma_nombre,
                total,
                acuenta,
                abonos,
                saldo
            ")
            ->where('cliente_id', $clienteId)
            ->where(function ($q) use ($ini, $fin, $ventaIdsPagadasEnRango) {
                $q->where(function ($q2) use ($ini, $fin) {
                    $q2->whereBetween('fecha_venta', [$ini, $fin])
                    ->where('saldo', '>', 0);
                });

                if (!empty($ventaIdsPagadasEnRango)) {
                    $q->orWhereIn('id', $ventaIdsPagadasEnRango);
                }
            })
            ->orderBy('fecha_venta')
            ->orderBy('id')
            ->get();

        $pagosPorVenta = $provConVenta->groupBy('venta_id')->map->values();
        $ventaIds = $ventas->pluck('id')->unique()->values()->all();

        $pagosAntesIniPorVenta = collect();
        if (!empty($ventaIds)) {
            $pagosAntesIniPorVenta = DB::table('venta_provisional_detalles as vp')
                ->join('venta_provisionales as v', 'v.id', '=', 'vp.venta_provisional_id')
                ->selectRaw('vp.venta_id, COALESCE(SUM(vp.monto),0) as total')
                ->where('v.cliente_id', $clienteId)
                ->whereNotNull('vp.venta_id')
                ->whereIn('vp.venta_id', $ventaIds)
                ->where('v.fecha_provisional', '<', $ini)
                ->groupBy('vp.venta_id')
                ->pluck('total', 'vp.venta_id');
        }

        $ventas = $ventas->map(function ($v) use ($pagosPorVenta, $pagosAntesIniPorVenta) {
            $v->documento = trim(($v->comprobante_tipo_codigo ? $v->comprobante_tipo_codigo.' ' : '') . ($v->serie ?? '') . '-' . ($v->correlativo ?? ''));

            $total   = (float) $v->total;
            $acuenta = (float) $v->acuenta;

            $v->credito_base = $total - $acuenta;

            $pagosAntes = (float) ($pagosAntesIniPorVenta[$v->id] ?? 0);
            $v->pagos_antes_ini = $pagosAntes;

            $v->saldo_inicio_rango = $v->credito_base - $pagosAntes;

            $pagos = $pagosPorVenta->get($v->id, collect())->values();

            $acum = 0.0;
            $pagos = $pagos->map(function ($p) use (&$acum, $v) {
                $acum += (float) $p->monto;
                $p->saldo_despues = $v->credito_base - ($v->pagos_antes_ini + $acum);
                return $p;
            });

            $v->pagos = $pagos;

            // ✅ saldo final real de esa venta dentro del rango
            $v->saldo_final_rango = $pagos->count()
                ? (float) $pagos->last()->saldo_despues
                : (float) $v->saldo_inicio_rango;

            return $v;
        });

        $adelantos = DB::table('venta_provisionales as v')
            ->selectRaw("v.id, v.fecha_provisional, v.numero_recibo, v.monto")
            ->where('v.cliente_id', $clienteId)
            ->whereBetween('v.fecha_provisional', [$ini, $fin])
            ->where('v.tipo', 'ADELANTO')
            ->orderBy('v.fecha_provisional')
            ->orderBy('v.id')
            ->get();

        $totAdelantos = (float) $adelantos->sum('monto');

        $totCreditoMostrado = (float) $ventas->sum('credito_base');
        $totPagoRango       = (float) $provisionales->sum('monto');
        $totPagoVentas      = (float) $provConVenta->sum('monto');
        $totAbonoSuelto     = $totAdelantos;

        $saldoFinal = (float) $ventas->sum('saldo_final_rango');

        $pdf = PDF::loadView('cuenta-cliente.reportes.estado_cuenta_simplificado', [
            'ini'                => $ini,
            'fin'                => $fin,
            'clienteId'          => $clienteId,
            'clienteNombre'      => $clienteNombre,
            'clienteData'        => $clienteData,
            'saldoInicial'       => $saldoInicial,
            'saldoFinal'         => $saldoFinal,

            'ventas'             => $ventas,
            'provSinVenta'       => $provSinVenta,
            'adelantos'          => $adelantos,

            'totCreditoMostrado' => $totCreditoMostrado,
            'totPagoRango'       => $totPagoRango,
            'totPagoVentas'      => $totPagoVentas,
            'totAbonoSuelto'     => $totAbonoSuelto,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("estado_cuenta_cliente_{$clienteId}.pdf");
    }
    */

}
