<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Producto;
use App\Models\Proveedor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuentaCorrienteProveedorController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:cuenta_corriente_report')->only(['index', 'resumenProveedor', 'comprasAgrupadaProductoProveedorPdf', 'comprasDetallePdf', 'detalleCreditosPorPagarPdf', 'creditosPorPagarProveedorTodosPdf', 'creditosPorPagarProveedorFechasPdf', 'comprasGeneralFechasPdf', 'saldosTodosPdf']);
    }

    public function index(Request $request)
    {
        // Lógica para generar el reporte de compras
        $proveedores = Proveedor::select('id', 'razon_social')->get();

        return view('cuenta-proveedor.index', compact('proveedores'));
    }

    /*
    public function resumenProveedor(Request $request)
    {
        if (!$request->ajax()) abort(403, 'Acceso no autorizado');

        $request->validate(['fecha' => ['required','date']]);

        $fecha = \Carbon\Carbon::parse($request->fecha)->endOfDay();

        // Si existe alguna lógica de stock, mantenerla, sino adaptar.
        // En el original era getStockAlCorteReportes
        // Asumimos que no aplica a proveedor o se debe adaptar si es Kardex.
        // Por ahora lo dejaré comentado o vacío si no es relevante.

        // $reportes = $this->getStockAlCorteReportes($fecha, true, false);
        // return view('kardex.reportes.stock_general', compact('reportes', 'fecha'));

        return response()->json(['message' => 'Not implemented yet for Provider']);
    }
    */

    public function comprasAgrupadaProductoProveedorPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'proveedor_ids' => ['nullable', 'array'],
            'proveedor_ids.*' => ['integer'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $proveedorIds = $data['proveedor_ids'] ?? [];

        $proveedorNombre = null;

        if (count($proveedorIds) === 1) {
            $proveedor = Proveedor::find($proveedorIds[0]);
            $proveedorNombre = $proveedor?->id.' - '.$proveedor?->razon_social;
        }

        $reportes = CompraDetalle::query()
            ->join('compras as c', 'c.id', '=', 'compra_detalles.compra_id')
            ->leftJoin('productos as p', 'p.id', '=', 'compra_detalles.producto_id')
            ->select([
                'compra_detalles.compra_id',
                'c.fecha_compra',

                'c.comprobante_tipo_codigo',
                'c.serie',
                'c.correlativo',

                'c.proveedor_id',
                'c.proveedor_nombre',

                'compra_detalles.producto_id',
                'compra_detalles.producto_nombre',

                'compra_detalles.cantidad',
                'compra_detalles.costo_unitario', // Asumiendo costo_unitario en vez de precio_unitario
                'compra_detalles.total',

                // empaques
                DB::raw('IFNULL(compra_detalles.producto_empaque,0) as empaque_detalle'),
                DB::raw('IFNULL(p.empaque,0) as empaque_producto'),

                // Documento (tipo + serie + correlativo)
                DB::raw("CONCAT(c.comprobante_tipo_codigo,' ',c.serie,'-',c.correlativo) as documento"),

                // Kg calculado según empaque del detalle
                DB::raw('
                    (compra_detalles.cantidad * 
                        CASE 
                            WHEN IFNULL(compra_detalles.producto_empaque,0) > 0 THEN compra_detalles.producto_empaque
                            ELSE IFNULL(p.empaque,0)
                        END
                    ) as kg_detalle
                '),

                // Cantidad convertida (a empaque del producto)
                DB::raw('
                    CASE
                        WHEN IFNULL(p.empaque,0) > 0 AND IFNULL(compra_detalles.producto_empaque,0) > 0
                            THEN (compra_detalles.cantidad * (compra_detalles.producto_empaque / p.empaque))
                        ELSE compra_detalles.cantidad
                    END as cantidad_convertida
                '),
            ])
            ->whereBetween('c.fecha_compra', [$ini, $fin])
            ->when(! empty($proveedorIds), fn ($q) => $q->whereIn('c.proveedor_id', $proveedorIds))
            ->orderBy('compra_detalles.producto_nombre')   // primero por producto
            ->orderBy('c.fecha_compra')                   // luego por fecha
            ->orderBy('compra_detalles.id')
            ->get();

        // Para header
        $fecha = $fin;

        $pdf = Pdf::loadView('cuenta-proveedor.reportes.compras_agrupadas_producto', compact('reportes', 'fecha', 'ini', 'fin', 'proveedorNombre'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('reporte_proveedor_compras_producto.pdf');
    }

    /*
    public function comprasDetallePdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio'   => ['required', 'date'],
            'fecha_fin'      => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'proveedor_ids'  => ['nullable', 'array'],
            'proveedor_ids.*'=> ['integer'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $proveedorIds = $data['proveedor_ids'] ?? [];

        $proveedorNombre = null;

        if (count($proveedorIds) === 1) {
            $proveedor = Proveedor::find($proveedorIds[0]);
            $proveedorNombre = $proveedor ? ($proveedor->id . ' - ' . $proveedor->razon_social) : null;
        }

        $reportes = CompraDetalle::query()
            ->join('compras as c', 'c.id', '=', 'compra_detalles.compra_id')
            ->leftJoin('productos as p', 'p.id', '=', 'compra_detalles.producto_id')
            ->select([
                'compra_detalles.compra_id',
                'c.fecha_compra',

                'c.comprobante_tipo_codigo',
                'c.serie',
                'c.correlativo',

                'c.proveedor_id',
                'c.proveedor_nombre',

                // ✅ NUEVO: por compra
                'c.abonos',
                'c.saldo',

                'compra_detalles.producto_id',
                'compra_detalles.producto_nombre',

                'compra_detalles.cantidad',
                'compra_detalles.costo_unitario', // Asumiendo costo_unitario
                'compra_detalles.total',

                // empaques
                DB::raw('IFNULL(compra_detalles.producto_empaque,0) as empaque_detalle'),
                DB::raw('IFNULL(p.empaque,0) as empaque_producto'),

                // Documento
                DB::raw("CONCAT(c.comprobante_tipo_codigo,' ',c.serie,'-',c.correlativo) as documento"),

                // Kg = cantidad * empaque_detalle (fallback empaque_producto)
                DB::raw("
                    (compra_detalles.cantidad *
                        CASE
                            WHEN IFNULL(compra_detalles.producto_empaque,0) > 0 THEN compra_detalles.producto_empaque
                            ELSE IFNULL(p.empaque,0)
                        END
                    ) as kg_detalle
                "),

                // Cantidad convertida (a empaque del producto)
                DB::raw("
                    CASE
                        WHEN IFNULL(p.empaque,0) > 0 AND IFNULL(compra_detalles.producto_empaque,0) > 0
                            THEN (compra_detalles.cantidad * (compra_detalles.producto_empaque / p.empaque))
                        ELSE compra_detalles.cantidad
                    END as cantidad_convertida
                "),
            ])
            ->whereBetween('c.fecha_compra', [$ini, $fin])
            ->when(!empty($proveedorIds), fn ($q) => $q->whereIn('c.proveedor_id', $proveedorIds))
            ->orderBy('c.fecha_compra')
            ->orderBy('compra_detalles.compra_id')
            ->orderBy('compra_detalles.id')
            ->get();

        $fecha = $fin;

        $pdf = Pdf::loadView(
            'cuenta-proveedor.reportes.compras_detalle',
            compact('reportes', 'fecha', 'ini', 'fin', 'proveedorNombre')
        )->setPaper('a4', 'portrait');

        return $pdf->stream('reporte_proveedor_compras_detalle.pdf');
    }
    */

    public function detalleCreditosPorPagarPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'proveedor_ids' => ['nullable', 'array'],
            'proveedor_ids.*' => ['integer'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $proveedorIds = $data['proveedor_ids'] ?? [];

        $proveedorNombre = null;
        if (count($proveedorIds) === 1) {
            $proveedor = Proveedor::find($proveedorIds[0]);
            $proveedorNombre = $proveedor ? ($proveedor->id.' - '.$proveedor->razon_social) : null;
        }

        // ✅ Detalle por compra + producto (producto_id/nombre/empaque desde compra_detalles)
        $reportes = CompraDetalle::query()
            ->join('compras as c', 'c.id', '=', 'compra_detalles.compra_id')
            ->selectRaw('
                c.id as compra_id,
                c.fecha_compra,
                c.pago_forma_nombre,
                c.abonos,
                c.saldo,

                CONCAT(c.comprobante_tipo_codigo," ",c.serie,"-",c.correlativo) as documento,

                compra_detalles.producto_id as producto_id,
                compra_detalles.producto_nombre as producto_nombre,
                IFNULL(compra_detalles.producto_empaque,0) as producto_empaque,

                SUM(compra_detalles.cantidad) as cantidad,

                -- KG = cantidad * producto_empaque
                SUM(compra_detalles.cantidad * IFNULL(compra_detalles.producto_empaque,0)) as kg,

                AVG(compra_detalles.costo_unitario) as precio,

                SUM(compra_detalles.total) as importe
            ')
            ->whereBetween('c.fecha_compra', [$ini, $fin])
            ->when(! empty($proveedorIds), fn ($q) => $q->whereIn('c.proveedor_id', $proveedorIds))

            // ✅ créditos por pagar
            ->where('c.saldo', '>', 0)

            ->groupBy(
                'c.id',
                'c.fecha_compra',
                'c.pago_forma_nombre',
                'c.abonos',
                'c.saldo',
                'c.comprobante_tipo_codigo',
                'c.serie',
                'c.correlativo',
                'compra_detalles.producto_id',
                'compra_detalles.producto_nombre',
                'compra_detalles.producto_empaque'
            )
            ->orderBy('c.fecha_compra')
            ->orderBy('c.id')
            ->orderBy('compra_detalles.producto_nombre')
            ->get();

        $pdf = Pdf::loadView('cuenta-proveedor.reportes.detalle_creditos_por_pagar_detalles', compact(
            'reportes', 'ini', 'fin', 'proveedorNombre'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('detalle_creditos_por_pagar.pdf');
    }

    public function creditosPorPagarProveedorTodosPdf(Request $request)
    {
        $data = $request->validate([
            'proveedor_ids' => ['required', 'array', 'min:1'],
            'proveedor_ids.*' => ['integer'],
        ]);

        $proveedorIds = $data['proveedor_ids'];

        if (count($proveedorIds) !== 1) {
            return back()->with('error', 'Debe seleccionar exactamente un proveedor.');
        }

        $proveedorId = (int) $proveedorIds[0];

        $proveedor = Proveedor::find($proveedorId);
        $proveedorNombre = $proveedor ? ($proveedor->id.' - '.$proveedor->razon_social) : null;

        $reportes = Compra::query()
            ->leftJoin('compra_detalles as vd', 'vd.compra_id', '=', 'compras.id')
            ->selectRaw('
                compras.id,
                compras.fecha_compra,
                compras.fecha_vencimiento,
                CONCAT(compras.comprobante_tipo_codigo," ",compras.serie,"-",compras.correlativo) as documento,
                compras.pago_forma_nombre as tipo_venta,

                compras.total,
                compras.acuenta,
                compras.abonos,
                compras.saldo,

                COUNT(vd.id) as items,

                compras.proveedor_nombre,
                compras.user_nombre
            ')
            ->where('compras.proveedor_id', $proveedorId)
            ->where('compras.saldo', '>', 0)
            ->groupBy(
                'compras.id',
                'compras.fecha_compra',
                'compras.fecha_vencimiento',
                'compras.comprobante_tipo_codigo',
                'compras.serie',
                'compras.correlativo',
                'compras.pago_forma_nombre',
                'compras.total',
                'compras.acuenta',
                'compras.abonos',
                'compras.saldo',
                'compras.proveedor_nombre',
                'compras.user_nombre'
            )
            ->orderBy('compras.fecha_compra')
            ->get();

        $totTotal = (float) $reportes->sum('total');
        $totAcuenta = (float) $reportes->sum('acuenta');
        $totAbonos = (float) $reportes->sum('abonos');
        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        // Reuse 'creditos_por_pagar_proveedor_todos' logic, renamed to 'creditos_por_pagar_proveedor_todos' logic potentially?
        // Wait, I didn't rename this view in the execution step?
        // Ah, I renamed resources/views/cuenta-proveedor/reportes-general/creditos_por_pagar_todos.blade.php -> creditos_por_pagar_todos.blade.php
        // But this method `creditosPorPagarProveedorTodosPdf` seems to target a specific provider view.
        // Let's check `CuentaCorrienteProveedorController` Step 12 line 354: 'cuenta-proveedor.reportes.creditos_por_pagar_proveedor_todos'
        // I did NOT rename this file in the plan execution!
        // I renamed `resources/views/cuenta-proveedor/reportes/creditos_por_pagar_cliente_fechas.blade.php`.
        // I need to check if `creditos_por_pagar_proveedor_todos.blade.php` exists.
        // Step 20 output shows: `creditos_por_pagar_proveedor_todos.blade.php` exists.
        // So I need to use that. I will rename it to `creditos_por_pagar_proveedor_todos.blade.php` later or now.
        // I will assume I renamed it to `creditos_por_pagar_proveedor_todos` for consistency.

        $pdf = Pdf::loadView('cuenta-proveedor.reportes.creditos_por_pagar_proveedor_todos', compact(
            'reportes',
            'proveedorNombre',
            'totTotal',
            'totAcuenta',
            'totAbonos',
            'totSaldo',
            'totItems'
        ))->setPaper('a4', 'portrait');

        $nombreSanitizado = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $proveedorNombre ?? '');
        $nombreSanitizado = preg_replace('/\s+/', '_', trim($nombreSanitizado));
        $partes = explode('_', $nombreSanitizado);
        $idPart = count($partes) > 0 ? array_shift($partes) : '';
        $nombreLimpio = implode('_', $partes);
        $nombreArchivo = $nombreLimpio.'_'.$idPart.'.pdf';

        return $pdf->stream('creditos_por_pagar_'.$nombreArchivo);
    }

    public function creditosPorPagarProveedorFechasPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'proveedor_ids' => ['nullable', 'array'],
            'proveedor_ids.*' => ['integer'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $proveedorIds = $data['proveedor_ids'] ?? [];

        // header: si es 1 proveedor, mostramos su razon social
        $proveedorNombre = null;
        if (count($proveedorIds) === 1) {
            $proveedor = Proveedor::find($proveedorIds[0]);
            $proveedorNombre = $proveedor ? ($proveedor->id.' - '.$proveedor->razon_social) : null;
        }

        // Si no filtra (o filtra varios), mostramos columna proveedor en tabla
        $mostrarColProveedor = (count($proveedorIds) !== 1);

        $reportes = Compra::query()
            ->leftJoin('compra_detalles as vd', 'vd.compra_id', '=', 'compras.id')
            ->selectRaw('
                compras.id,
                compras.fecha_compra,
                compras.fecha_vencimiento,
                CONCAT(compras.comprobante_tipo_codigo," ",compras.serie,"-",compras.correlativo) as documento,
                compras.pago_forma_nombre as tipo_venta,

                compras.total,
                compras.acuenta,
                compras.abonos,
                compras.saldo,

                COUNT(vd.id) as items,

                compras.proveedor_id,
                compras.proveedor_nombre,
                compras.user_nombre
            ')
            ->whereBetween('compras.fecha_compra', [$ini, $fin])
            ->when(! empty($proveedorIds), fn ($q) => $q->whereIn('compras.proveedor_id', $proveedorIds))
            ->where('compras.saldo', '>', 0) // créditos por pagar
            ->groupBy(
                'compras.id',
                'compras.fecha_compra',
                'compras.fecha_vencimiento',
                'compras.comprobante_tipo_codigo',
                'compras.serie',
                'compras.correlativo',
                'compras.pago_forma_nombre',
                'compras.total',
                'compras.acuenta',
                'compras.abonos',
                'compras.saldo',
                'compras.proveedor_id',
                'compras.proveedor_nombre',
                'compras.user_nombre'
            )
            ->orderBy('compras.fecha_compra')
            ->get();

        // Totales generales
        $totTotal = (float) $reportes->sum('total');
        $totAcuenta = (float) $reportes->sum('acuenta');
        $totAbonos = (float) $reportes->sum('abonos');
        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        $pdf = Pdf::loadView('cuenta-proveedor.reportes.creditos_por_pagar_proveedor_fechas', compact(
            'reportes',
            'ini',
            'fin',
            'proveedorNombre',
            'mostrarColProveedor',
            'totTotal',
            'totAcuenta',
            'totAbonos',
            'totSaldo',
            'totItems'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('creditos_por_pagar_proveedor_fechas.pdf');
    }

    public function estadoCuentaProveedorPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'proveedor_ids' => ['required', 'array', 'size:1'],
            'proveedor_ids.0' => ['required', 'integer'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $proveedorId = (int) $data['proveedor_ids'][0];

        $proveedorData = Proveedor::select('id', 'razon_social', 'direccion', 'telefono')
            ->find($proveedorId);

        $proveedorNombre = $proveedorData
            ? ($proveedorData->id.' - '.$proveedorData->razon_social)
            : (string) $proveedorId;

        $saldoInicial = (float) DB::table('compras')
            ->where('proveedor_id', $proveedorId)
            ->where('saldo', '>', 0)
            ->where('fecha_compra', '<', $ini)
            ->sum('saldo');

        $saldoFinal = (float) DB::table('compras')
            ->where('proveedor_id', $proveedorId)
            ->where('saldo', '>', 0)
            ->where('fecha_compra', '<=', $fin)
            ->sum('saldo');

        $provisionales = DB::table('compra_provisional_detalles as cp')
            ->join('compra_provisionales as c', 'c.id', '=', 'cp.compra_provisional_id')
            ->selectRaw('
                cp.id,
                cp.compra_id,
                c.fecha_provisional,
                cp.monto,
                cp.comentario,
                c.numero_recibo,
                cp.comprobante_tipo_codigo,
                cp.serie,
                cp.correlativo
            ')
            ->where('c.proveedor_id', $proveedorId)
            ->whereBetween('c.fecha_provisional', [$ini, $fin])
            ->orderBy('c.fecha_provisional')
            ->orderBy('cp.id')
            ->get();

        $provConCompra = $provisionales->whereNotNull('compra_id');
        $provSinCompra = $provisionales->whereNull('compra_id')->values();

        $compraIdsPagadasEnRango = $provConCompra->pluck('compra_id')->unique()->values()->all();

        $compras = DB::table('compras')
            ->selectRaw('
                id,
                fecha_compra,
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
            ->where('proveedor_id', $proveedorId)
            ->where('estado', '!=', 'anulada')
            ->where(function ($q) use ($ini, $fin, $compraIdsPagadasEnRango) {
                $q->where(function ($q2) use ($ini, $fin) {
                    $q2->whereBetween('fecha_compra', [$ini, $fin])
                        ->where('saldo', '>', 0);
                });

                if (! empty($compraIdsPagadasEnRango)) {
                    $q->orWhereIn('id', $compraIdsPagadasEnRango);
                }
            })
            ->orderBy('fecha_compra')
            ->orderBy('id')
            ->get();

        $pagosPorCompra = $provConCompra->groupBy('compra_id')->map->values();

        $compraIds = $compras->pluck('id')->unique()->values()->all();

        $pagosAntesIniPorCompra = collect();

        if (! empty($compraIds)) {
            $pagosAntesIniPorCompra = DB::table('compra_provisional_detalles as cp')
                ->join('compra_provisionales as c', 'c.id', '=', 'cp.compra_provisional_id')
                ->selectRaw('cp.compra_id, COALESCE(SUM(cp.monto),0) as total')
                ->where('c.proveedor_id', $proveedorId)
                ->whereNotNull('cp.compra_id')
                ->whereIn('cp.compra_id', $compraIds)
                ->where('c.fecha_provisional', '<', $ini)
                ->groupBy('cp.compra_id')
                ->pluck('total', 'cp.compra_id');
        }

        $compras = $compras->map(function ($c) use ($pagosPorCompra, $pagosAntesIniPorCompra) {
            $c->documento = trim(($c->comprobante_tipo_codigo ? $c->comprobante_tipo_codigo.' ' : '').($c->serie ?? '').'-'.($c->correlativo ?? ''));

            $total = (float) $c->total;
            $acuenta = (float) $c->acuenta;

            $c->credito_base = $total - $acuenta;

            $pagosAntes = (float) ($pagosAntesIniPorCompra[$c->id] ?? 0);
            $c->pagos_antes_ini = $pagosAntes;

            $c->saldo_inicio_rango = $c->credito_base - $pagosAntes;

            $pagos = $pagosPorCompra->get($c->id, collect())->values();

            $acum = 0.0;
            $pagos = $pagos->map(function ($p) use (&$acum, $c) {
                $acum += (float) $p->monto;
                $p->saldo_despues = $c->credito_base - ($c->pagos_antes_ini + $acum);

                return $p;
            });

            $c->pagos = $pagos;

            return $c;
        });

        $abonosSueltos = DB::table('compra_provisionales as c')
            ->selectRaw('
                c.id,
                c.fecha_provisional,
                c.numero_recibo,
                c.monto
            ')
            ->where('c.proveedor_id', $proveedorId)
            ->whereBetween('c.fecha_provisional', [$ini, $fin])
            ->where('c.tipo', 'ADELANTO')
            ->orderBy('c.fecha_provisional')
            ->orderBy('c.id')
            ->get();

        $totAdelantos = (float) $abonosSueltos->sum('monto');

        $totPagoRango = (float) $provisionales->sum('monto');
        $totPagoCompras = (float) $provConCompra->sum('monto');
        $totAbonoSuelto = $totAdelantos;
        $totCreditoMostrado = (float) $compras->sum('credito_base');

        $pdf = PDF::loadView('cuenta-proveedor.reportes.estado_cuenta_simplificado', [
            'ini' => $ini,
            'fin' => $fin,
            'proveedorId' => $proveedorId,
            'proveedorNombre' => $proveedorNombre,
            'proveedorData' => $proveedorData,
            'saldoInicial' => $saldoInicial,
            'saldoFinal' => $saldoFinal,

            'compras' => $compras,
            'provSinCompra' => $provSinCompra,

            'totCreditoMostrado' => $totCreditoMostrado,
            'totPagoRango' => $totPagoRango,
            'totPagoCompras' => $totPagoCompras,
            'totAbonoSuelto' => $totAbonoSuelto,
            'abonosSueltos' => $abonosSueltos,
        ])->setPaper('a4', 'portrait');

        $nombreSanitizado = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $proveedorNombre ?? '');
        $nombreSanitizado = preg_replace('/\s+/', '_', trim($nombreSanitizado));
        $partes = explode('_', $nombreSanitizado);
        $idPart = count($partes) > 0 ? array_shift($partes) : '';
        $nombreLimpio = implode('_', $partes);
        $nombreArchivo = $nombreLimpio.'_'.$idPart.'.pdf';

        return $pdf->stream('estado_cuenta_simplificado_'.$nombreArchivo);
    }

    public function comprasGeneralFechasPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'proveedor_ids' => ['nullable', 'array'],
            'proveedor_ids.*' => ['integer'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
        $proveedorIds = $data['proveedor_ids'] ?? [];

        // header: si es 1 proveedor, mostramos su razon social
        $proveedorNombre = null;
        if (count($proveedorIds) === 1) {
            $proveedor = Proveedor::find($proveedorIds[0]);
            $proveedorNombre = $proveedor ? ($proveedor->id.' - '.$proveedor->razon_social) : null;
        }

        // Si no filtra (o filtra varios), mostramos columna proveedor en tabla
        $mostrarColProveedor = (count($proveedorIds) !== 1);

        $reportes = Compra::query()
            ->leftJoin('compra_detalles as vd', 'vd.compra_id', '=', 'compras.id')
            ->selectRaw('
                compras.id,
                compras.fecha_compra,
                CONCAT(compras.comprobante_tipo_codigo," ",compras.serie,"-",compras.correlativo) as documento,
                compras.pago_forma_nombre as tipo_venta,

                compras.total,
                compras.acuenta,
                compras.abonos,
                compras.saldo,

                COUNT(vd.id) as items,

                compras.proveedor_id,
                compras.proveedor_nombre,
                compras.user_nombre
            ')
            ->whereBetween('compras.fecha_compra', [$ini, $fin])
            ->when(! empty($proveedorIds), fn ($q) => $q->whereIn('compras.proveedor_id', $proveedorIds))
            ->groupBy(
                'compras.id',
                'compras.fecha_compra',
                'compras.comprobante_tipo_codigo',
                'compras.serie',
                'compras.correlativo',
                'compras.pago_forma_nombre',
                'compras.total',
                'compras.acuenta',
                'compras.abonos',
                'compras.saldo',
                'compras.proveedor_id',
                'compras.proveedor_nombre',
                'compras.user_nombre'
            )
            ->orderBy('compras.fecha_compra')
            ->get();

        // Totales generales
        $totTotal = (float) $reportes->sum('total');
        $totAcuenta = (float) $reportes->sum('acuenta');
        $totAbonos = (float) $reportes->sum('abonos');
        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        $pdf = Pdf::loadView('cuenta-proveedor.reportes.compras_general_fechas', compact(
            'reportes',
            'ini',
            'fin',
            'proveedorNombre',
            'mostrarColProveedor',
            'totTotal',
            'totAcuenta',
            'totAbonos',
            'totSaldo',
            'totItems'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('creditos_por_pagar_fechas.pdf');
    }

    /*
    public function saldosTodosPdf(Request $request)
    {
        $data = $request->validate([
            'proveedor_ids'   => ['required', 'array', 'min:1'],
            'proveedor_ids.*' => ['integer'],
        ]);

        $proveedorIds = $data['proveedor_ids'];

        //Este reporte solo admite 1 proveedor
        if (count($proveedorIds) !== 1) {
            return back()->with('error', 'Debe seleccionar exactamente un proveedor.');
        }

        $proveedorId = (int)$proveedorIds[0];

        $proveedor = Proveedor::find($proveedorId);
        $proveedorNombre = $proveedor ? ($proveedor->id.' - '.$proveedor->razon_social) : null;

        $reportes = Compra::query()
            ->leftJoin('compra_detalles as vd', 'vd.compra_id', '=', 'compras.id')
            ->selectRaw('
                compras.id,
                compras.fecha_compra,
                CONCAT(compras.comprobante_tipo_codigo," ",compras.serie,"-",compras.correlativo) as documento,
                compras.pago_forma_nombre as tipo_venta,

                compras.total,
                compras.acuenta,
                compras.abonos,
                compras.saldo,

                COUNT(vd.id) as items,

                compras.proveedor_nombre,
                compras.user_nombre
            ')
            ->where('compras.proveedor_id', $proveedorId)
            ->where('compras.saldo', '>', 0)
            ->groupBy(
                'compras.id',
                'compras.fecha_compra',
                'compras.comprobante_tipo_codigo',
                'compras.serie',
                'compras.correlativo',
                'compras.pago_forma_nombre',
                'compras.total',
                'compras.acuenta',
                'compras.abonos',
                'compras.saldo',
                'compras.proveedor_nombre',
                'compras.user_nombre'
            )
            ->orderBy('compras.fecha_compra')
            ->get();

        // Totales generales
        $totTotal   = (float)$reportes->sum('total');
        $totAcuenta = (float)$reportes->sum('acuenta');
        $totAbonos  = (float)$reportes->sum('abonos');
        $totSaldo   = (float)$reportes->sum('saldo');
        $totItems   = (int)$reportes->sum('items');

        $empresa = (object)[
            'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
            'direccion' => 'Carretera Pomalca KM 3' . "\n" . 'A espaldas de Ferretería Herrera',
            'ruc' => '20538937321',
            'celular'=>'967984895 - 978431737 - 915177079',
        ];

        $pdf = Pdf::loadView('cuenta-proveedor.reportes.saldos', compact(
            'reportes',
            'proveedorNombre',
            'totTotal',
            'totAcuenta',
            'totAbonos',
            'totSaldo',
            'totItems','empresa'
        ))->setPaper([0, 0, 226.77, 600], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream('creditos_por_pagar_todos.pdf');
    }
    */

    /* ***GENERAL */
    public function creditosPorPagarTodosPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $reportes = DB::table('compras')
            ->join('proveedores', 'proveedores.id', '=', 'compras.proveedor_id')
            ->selectRaw('
                compras.proveedor_id,
                compras.proveedor_nombre,
                SUM(compras.saldo) as saldo,
                COUNT(compras.id) as items,
                proveedores.direccion as domicilio,
                proveedores.telefono
            ')
            ->where('compras.saldo', '>', 0)
            ->whereBetween('compras.fecha_compra', [$ini, $fin])
            ->groupBy(
                'compras.proveedor_id',
                'compras.proveedor_nombre',
                'proveedores.direccion',
                'proveedores.telefono'
            )
            ->orderBy('compras.proveedor_nombre', 'asc')
            ->get();

        // Totales generales
        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        $pdf = Pdf::loadView('cuenta-proveedor.reportes-general.creditos_por_pagar_todos', compact(
            'reportes',
            'totSaldo',
            'totItems',
            'ini',
            'fin'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('creditos_por_pagar_todos.pdf');
    }

    public function creditosPorPagarDiasPdf(Request $request)
    {
        $data = $request->validate([
            'dias' => ['required', 'integer', 'min:1'],
        ]);

        $dias = (int) $data['dias'];

        $reportes = Compra::query()
            ->leftJoin('compra_detalles as vd', 'vd.compra_id', '=', 'compras.id')
            ->selectRaw('
                compras.id,
                compras.fecha_compra,
                compras.fecha_vencimiento,
                CONCAT(compras.comprobante_tipo_codigo," ",compras.serie,"-",compras.correlativo) as documento,
                compras.pago_forma_nombre as tipo_venta,

                compras.total,
                compras.acuenta,
                compras.abonos,
                compras.saldo,

                COUNT(vd.id) as items,

                compras.proveedor_nombre,
                compras.user_nombre
            ')
            ->where('compras.saldo', '>', 0)
            ->whereNotNull('compras.fecha_vencimiento')
            ->whereRaw('DATEDIFF(CURDATE(), compras.fecha_compra) >= ?', [$dias])
            ->groupBy(
                'compras.id',
                'compras.fecha_compra',
                'compras.fecha_vencimiento',
                'compras.comprobante_tipo_codigo',
                'compras.serie',
                'compras.correlativo',
                'compras.pago_forma_nombre',
                'compras.total',
                'compras.acuenta',
                'compras.abonos',
                'compras.saldo',
                'compras.proveedor_nombre',
                'compras.user_nombre'
            )
            ->orderBy('compras.fecha_compra')
            ->get();

        // Totales generales
        $totTotal = (float) $reportes->sum('total');
        $totAcuenta = (float) $reportes->sum('acuenta');
        $totAbonos = (float) $reportes->sum('abonos');
        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        $pdf = Pdf::loadView('cuenta-proveedor.reportes-general.creditos_por_pagar_dias', compact(
            'reportes',
            'totTotal',
            'totAcuenta',
            'totAbonos',
            'totSaldo',
            'totItems',
            'dias'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream('creditos_por_pagar_dias.pdf');
    }

    public function creditosPorPagarDiasAgrupadoProveedorPdf(Request $request)
    {
        $data = $request->validate([
            'dias' => ['required', 'integer', 'min:1'],
        ]);

        $dias = (int) $data['dias'];

        $reportes = Compra::query()
            ->selectRaw('
                compras.id,
                compras.fecha_compra,
                compras.fecha_vencimiento,

                DATE_FORMAT(compras.fecha_compra, "%d/%m/%Y") as fecha_compra_fmt,
                DATE_FORMAT(compras.fecha_vencimiento, "%d/%m/%Y") as fecha_vencimiento_fmt,

                CONCAT(compras.comprobante_tipo_codigo," ",compras.serie,"-",compras.correlativo) as documento,
                compras.pago_forma_nombre as tipo_venta,

                compras.total,
                compras.acuenta,
                compras.abonos,
                compras.saldo,

                compras.proveedor_id,
                compras.proveedor_nombre,
                compras.user_nombre
            ')
            ->withCount(['detalles as items'])
            ->where('compras.saldo', '>', 0)
            ->whereNotNull('compras.fecha_vencimiento')
            // equivalente a >= días, pero sin funciones sobre la columna
            ->whereDate('compras.fecha_compra', '<=', DB::raw('DATE_SUB(CURDATE(), INTERVAL ? DAY)'))
            ->addBinding($dias, 'where')
            ->orderBy('compras.proveedor_nombre')
            ->orderBy('compras.fecha_compra')
            ->get();

        // Agrupar por proveedor_id + subtotales
        $grupos = $reportes->groupBy('proveedor_id')->map(function ($rows) {
            $first = $rows->first();

            return [
                'proveedor_id' => $first->proveedor_id,
                'proveedor_nombre' => $first->proveedor_nombre,

                'totTotal' => (float) $rows->sum('total'),
                'totAcuenta' => (float) $rows->sum('acuenta'),
                'totAbonos' => (float) $rows->sum('abonos'),
                'totSaldo' => (float) $rows->sum('saldo'),
                'totItems' => (int) $rows->sum('items'),
                'cantidad_docs' => (int) $rows->count('id'),
            ];
        })->values();

        // Totales generales
        $totTotal = (float) $reportes->sum('total');
        $totAcuenta = (float) $reportes->sum('acuenta');
        $totAbonos = (float) $reportes->sum('abonos');
        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');
        $totCantDocs = (int) $reportes->count('id');

        $pdf = Pdf::loadView(
            'cuenta-proveedor.reportes-general.creditos_por_pagar_dias_agrupado_proveedor',
            compact('grupos', 'totTotal', 'totAcuenta', 'totAbonos', 'totSaldo', 'totItems', 'totCantDocs')
        )->setPaper('a4', 'landscape');

        return $pdf->stream('creditos_por_pagar_dias_agrupado_proveedor.pdf');
    }

    public function saldosAcumuladosProveedorDiasPdf(Request $request)
    {
        $data = $request->validate([
            'dias' => ['required', 'integer', 'min:1'],
        ]);

        $dias = (int) $data['dias'];

        $reportes = DB::table('compras')
            ->join('proveedores', 'proveedores.id', '=', 'compras.proveedor_id')
            ->selectRaw('
                compras.proveedor_id,
                compras.proveedor_nombre,
                SUM(compras.saldo) as saldo,
                COUNT(compras.id) as items,
                proveedores.direccion as domicilio,
                proveedores.telefono as telefono
            ')
            // si quieres SOLO créditos por pagar:
            ->where('compras.saldo', '>', 0)
            // >= 30 días de antigüedad desde la fecha_compra:
            ->whereDate('compras.fecha_compra', '<=', DB::raw('DATE_SUB(CURDATE(), INTERVAL ? DAY)'))
            ->addBinding($dias, 'where')
            ->groupBy(
                'compras.proveedor_id',
                'compras.proveedor_nombre',
                'proveedores.direccion',
                'proveedores.telefono'
            )
            ->orderBy('compras.proveedor_nombre', 'asc')
            ->get();

        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        $pdf = Pdf::loadView(
            'cuenta-proveedor.reportes-general.saldos_acumulados_proveedor_dias',
            compact('reportes', 'totSaldo', 'totItems')
        )->setPaper('a4', 'portrait');

        return $pdf->stream('saldos_acumulados_proveedor_dias.pdf');
    }

    public function saldoFechasSolicitadaPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $mostrarColProveedor = true;

        $reportes = Compra::query()
            ->selectRaw('
                compras.id,
                compras.fecha_compra,
                compras.fecha_vencimiento,
                DATE_FORMAT(compras.fecha_compra, "%d/%m/%Y") as fecha_compra_fmt,
                DATE_FORMAT(compras.fecha_vencimiento, "%d/%m/%Y") as fecha_vencimiento_fmt,
                CONCAT(compras.comprobante_tipo_codigo," ",compras.serie,"-",compras.correlativo) as documento,
                compras.pago_forma_nombre as tipo_venta,

                compras.total,
                compras.acuenta,
                compras.abonos,
                compras.saldo,

                compras.proveedor_id,
                compras.proveedor_nombre,
                compras.user_nombre
            ')
            ->withCount(['detalles as items'])
            ->whereBetween('compras.fecha_compra', [$ini, $fin])
            ->where('compras.saldo', '>', 0)
            ->orderBy('compras.proveedor_nombre', 'asc')
            ->orderBy('compras.fecha_compra', 'asc')
            ->get();

        $totTotal = (float) $reportes->sum('total');
        $totAcuenta = (float) $reportes->sum('acuenta');
        $totAbonos = (float) $reportes->sum('abonos');
        $totSaldo = (float) $reportes->sum('saldo');
        $totItems = (int) $reportes->sum('items');

        $pdf = Pdf::loadView('cuenta-proveedor.reportes-general.saldo_fechas_solicitada', compact(
            'reportes',
            'ini',
            'fin',
            'mostrarColProveedor',
            'totTotal',
            'totAcuenta',
            'totAbonos',
            'totSaldo',
            'totItems'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream('saldo_fechas_seleccionada.pdf');
    }

    public function resumenCreditosPorPagarPdf()
    {
        $reportes = DB::table('compras as c')
            ->selectRaw('
                c.user_nombre as vendedor,

                SUM(CASE WHEN DATEDIFF(CURDATE(), c.fecha_compra) <= 3 
                    THEN c.saldo ELSE 0 END) as d_3,

                SUM(CASE WHEN DATEDIFF(CURDATE(), c.fecha_compra) BETWEEN 4 AND 7 
                    THEN c.saldo ELSE 0 END) as d_7,

                SUM(CASE WHEN DATEDIFF(CURDATE(), c.fecha_compra) BETWEEN 8 AND 15 
                    THEN c.saldo ELSE 0 END) as d_15,

                SUM(CASE WHEN DATEDIFF(CURDATE(), c.fecha_compra) BETWEEN 16 AND 30 
                    THEN c.saldo ELSE 0 END) as d_30,

                SUM(CASE WHEN DATEDIFF(CURDATE(), c.fecha_compra) >= 31 
                    THEN c.saldo ELSE 0 END) as d_31,

                SUM(c.saldo) as acumulado,

                COUNT(CASE WHEN c.saldo > 0 THEN 1 END) as total_docs
            ')
            ->where('c.saldo', '>', 0)
            ->groupBy('c.user_nombre')
            ->orderBy('c.user_nombre')
            ->get();

        $pdf = Pdf::loadView(
            'cuenta-proveedor.reportes-general.resumen_creditos_por_pagar',
            compact('reportes')
        )->setPaper('a4', 'landscape');

        return $pdf->stream('resumen_creditos_por_pagar.pdf');
    }

    public function detalleCreditosPorPagarTodosPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();

        // ✅ Detalle por compra + producto (producto_id/nombre/empaque desde compra_detalles)
        $reportes = CompraDetalle::query()
            ->join('compras as c', 'c.id', '=', 'compra_detalles.compra_id')
            ->selectRaw('
                c.id as compra_id,
                c.fecha_compra,
                c.pago_forma_nombre,
                c.abonos,
                c.saldo,

                CONCAT(c.comprobante_tipo_codigo," ",c.serie,"-",c.correlativo) as documento,

                compra_detalles.producto_id as producto_id,
                compra_detalles.producto_nombre as producto_nombre,
                IFNULL(compra_detalles.producto_empaque,0) as producto_empaque,

                SUM(compra_detalles.cantidad) as cantidad,

                -- KG = cantidad * producto_empaque
                SUM(compra_detalles.cantidad * IFNULL(compra_detalles.producto_empaque,0)) as kg,

                AVG(compra_detalles.costo_unitario) as precio,

                SUM(compra_detalles.total) as importe
            ')
            ->whereBetween('c.fecha_compra', [$ini, $fin])

            // ✅ créditos por pagar
            ->where('c.saldo', '>', 0)

            ->groupBy(
                'c.id',
                'c.fecha_compra',
                'c.pago_forma_nombre',
                'c.abonos',
                'c.saldo',
                'c.comprobante_tipo_codigo',
                'c.serie',
                'c.correlativo',
                'compra_detalles.producto_id',
                'compra_detalles.producto_nombre',
                'compra_detalles.producto_empaque'
            )
            ->orderBy('c.fecha_compra')
            ->orderBy('c.id')
            ->orderBy('compra_detalles.producto_nombre')
            ->get();

        $pdf = Pdf::loadView('cuenta-proveedor.reportes-general.detalle_creditos_por_pagar_detalles_todos', compact(
            'reportes', 'ini', 'fin'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('detalle_creditos_por_pagar_todos.pdf');
    }
}
