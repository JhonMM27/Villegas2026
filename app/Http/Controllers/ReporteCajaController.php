<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraProvisional;
use App\Models\Gasto;
use App\Models\PlanillaAdelanto;
use App\Models\PlanillaPago;
use App\Models\PlanillaPrestamo;
use App\Models\PlanillaPrestamoPago;
use App\Models\Venta;
use App\Models\VentaProvisional;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReporteCajaController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:caja_report')->only(['index', 'general', 'detallado', 'generalPdf']);
    }

    public function index(Request $request)
    {
        // Lógica para generar el reporte de compras
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

        // ======================
        // VENTAS
        // ======================
        $ventas = Venta::whereBetween('fecha_venta', [$ini, $fin])
            ->where('estado', '!=', 'anulada')
            ->selectRaw('
                COALESCE(SUM(total),0) as total,
                COALESCE(SUM(acuenta),0) as acuenta,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // ======================
        // COMPRAS
        // ======================
        $compras = Compra::whereBetween('fecha_compra', [$ini, $fin])
            ->where('estado', '!=', 'anulada')
            ->selectRaw('
                COALESCE(SUM(total),0) as total,
                COALESCE(SUM(acuenta),0) as acuenta,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // ======================
        // PROVISIONALES
        // ======================
        $ventaProvisionales = VentaProvisional::whereBetween('fecha_provisional', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            // ->where('estado', '!=', 'anulada')
            ->first();

        $compraProvisionales = CompraProvisional::whereBetween('fecha_provisional', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            // ->where('estado', '!=', 'anulada')
            ->first();

        $gastos = Gasto::whereBetween('fecha_gasto', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            // ->where('estado', '!=', 'anulada')
            ->first();

        // ======================
        // PLANILLA - ADELANTOS
        // ======================
        $adelantos = PlanillaAdelanto::whereBetween('fecha', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // ======================
        // PLANILLA - PRESTAMOS (otorgados)
        // ======================
        $prestamos = PlanillaPrestamo::whereBetween('fecha_prestamo', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto_original),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // ======================
        // PLANILLA - PAGOS (pago de planilla)
        // ======================
        $pagosPlanilla = PlanillaPago::whereBetween('fecha_pago', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(total_pagar),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        $resumen = [
            // ingresos: ventas + provisionales
            'ing_p' => (float) $ventas->importe_p + (float) $ventaProvisionales->importe_p,
            'ing_d' => (float) $ventas->importe_d + (float) $ventaProvisionales->importe_d,
            'ing_c' => (float) $ventas->importe_c + (float) $ventaProvisionales->importe_c,

            // egresos: compras + gastos + planilla
            'egr_p' => (float) $compras->importe_p + (float) $compraProvisionales->importe_p + (float) $gastos->importe_p + (float) $adelantos->importe_p + (float) $prestamos->importe_p + (float) $pagosPlanilla->importe_p,
            'egr_d' => (float) $compras->importe_d + (float) $compraProvisionales->importe_d + (float) $gastos->importe_d + (float) $adelantos->importe_d + (float) $prestamos->importe_d + (float) $pagosPlanilla->importe_d,
            'egr_c' => (float) $compras->importe_c + (float) $compraProvisionales->importe_c + (float) $gastos->importe_c + (float) $adelantos->importe_c + (float) $prestamos->importe_c + (float) $pagosPlanilla->importe_c,

            // neto por caja
            'net_p' => ((float) $ventas->importe_p + (float) $ventaProvisionales->importe_p) - (float) $compras->importe_p - (float) $compraProvisionales->importe_p - (float) $gastos->importe_p - (float) $adelantos->importe_p - (float) $prestamos->importe_p - (float) $pagosPlanilla->importe_p,
            'net_d' => ((float) $ventas->importe_d + (float) $ventaProvisionales->importe_d) - (float) $compras->importe_d - (float) $compraProvisionales->importe_d - (float) $gastos->importe_d - (float) $adelantos->importe_d - (float) $prestamos->importe_d - (float) $pagosPlanilla->importe_d,
            'net_c' => ((float) $ventas->importe_c + (float) $ventaProvisionales->importe_c) - (float) $compras->importe_c - (float) $compraProvisionales->importe_c - (float) $gastos->importe_c - (float) $adelantos->importe_c - (float) $prestamos->importe_c - (float) $pagosPlanilla->importe_c,
        ];

        // totales generales (opcional para mostrar)
        $resumen['ingresos_totales'] = $resumen['ing_p'] + $resumen['ing_d'] + $resumen['ing_c'];
        $resumen['egresos_totales'] = $resumen['egr_p'] + $resumen['egr_d'] + $resumen['egr_c'];
        $resumen['saldo_neto'] = $resumen['ingresos_totales'] - $resumen['egresos_totales'];

        return view('reportes.caja.general', [
            'ventas' => $ventas,
            'compras' => $compras,
            'ventaProvisionales' => $ventaProvisionales,
            'compraProvisionales' => $compraProvisionales,
            'gastos' => $gastos,
            'adelantos' => $adelantos,
            'prestamos' => $prestamos,
            'pagosPlanilla' => $pagosPlanilla,
            'resumen' => $resumen,
            'fechaInicio' => $ini,
            'fechaFin' => $fin,
        ]);
    }

    public function detallado(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();

        // ======================
        // VENTAS
        // ======================
        $ventas = Venta::whereBetween('fecha_venta', [$ini, $fin])
            ->where('estado', '!=', 'anulada')
            ->selectRaw('
                COALESCE(SUM(total),0) as total,
                COALESCE(SUM(acuenta),0) as acuenta,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // ======================
        // COMPRAS
        // ======================
        $compras = Compra::whereBetween('fecha_compra', [$ini, $fin])
            ->where('estado', '!=', 'anulada')
            ->selectRaw('
                COALESCE(SUM(total),0) as total,
                COALESCE(SUM(acuenta),0) as acuenta,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // ======================
        // PROVISIONALES
        // ======================
        $ventaProvisionales = VentaProvisional::whereBetween('fecha_provisional', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            // ->where('estado', '!=', 'anulada')
            ->first();

        $compraProvisionales = CompraProvisional::whereBetween('fecha_provisional', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            // ->where('estado', '!=', 'anulada')
            ->first();

        $gastos = Gasto::whereBetween('fecha_gasto', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            // ->where('estado', '!=', 'anulada')
            ->first();

        // ======================
        // PLANILLA - ADELANTOS
        // ======================
        $adelantos = PlanillaAdelanto::whereBetween('fecha', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // ======================
        // PLANILLA - PRESTAMOS (otorgados)
        // ======================
        $prestamos = PlanillaPrestamo::whereBetween('fecha_prestamo', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto_original),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // ======================
        // PLANILLA - PAGOS DE PRESTAMOS (pagos de loans - van a consortium)
        // ======================
        $pagosPrestamos = PlanillaPrestamoPago::whereBetween('fecha_pago', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto_pagado),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // ======================
        // PLANILLA - PAGOS (pago de planilla)
        // ======================
        $pagosPlanilla = PlanillaPago::whereBetween('fecha_pago', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(total_pagar),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        $resumen = [
            // ingresos: ventas + provisionales
            'ing_p' => (float) $ventas->importe_p + (float) $ventaProvisionales->importe_p,
            'ing_d' => (float) $ventas->importe_d + (float) $ventaProvisionales->importe_d,
            'ing_c' => (float) $ventas->importe_c + (float) $ventaProvisionales->importe_c,

            // egresos: compras + gastos + planilla
            'egr_p' => (float) $compras->importe_p + (float) $compraProvisionales->importe_p + (float) $gastos->importe_p + (float) $adelantos->importe_p + (float) $prestamos->importe_p + (float) $pagosPrestamos->importe_p + (float) $pagosPlanilla->importe_p,
            'egr_d' => (float) $compras->importe_d + (float) $compraProvisionales->importe_d + (float) $gastos->importe_d + (float) $adelantos->importe_d + (float) $prestamos->importe_d + (float) $pagosPrestamos->importe_d + (float) $pagosPlanilla->importe_d,
            'egr_c' => (float) $compras->importe_c + (float) $compraProvisionales->importe_c + (float) $gastos->importe_c + (float) $adelantos->importe_c + (float) $prestamos->importe_c + (float) $pagosPrestamos->importe_c + (float) $pagosPlanilla->importe_c,

            // neto por caja
            'net_p' => ((float) $ventas->importe_p + (float) $ventaProvisionales->importe_p) - (float) $compras->importe_p - (float) $compraProvisionales->importe_p - (float) $gastos->importe_p - (float) $adelantos->importe_p - (float) $prestamos->importe_p - (float) $pagosPrestamos->importe_p - (float) $pagosPlanilla->importe_p,
            'net_d' => ((float) $ventas->importe_d + (float) $ventaProvisionales->importe_d) - (float) $compras->importe_d - (float) $compraProvisionales->importe_d - (float) $gastos->importe_d - (float) $adelantos->importe_d - (float) $prestamos->importe_d - (float) $pagosPrestamos->importe_d - (float) $pagosPlanilla->importe_d,
            'net_c' => ((float) $ventas->importe_c + (float) $ventaProvisionales->importe_c) - (float) $compras->importe_c - (float) $compraProvisionales->importe_c - (float) $gastos->importe_c - (float) $adelantos->importe_c - (float) $prestamos->importe_c - (float) $pagosPrestamos->importe_c - (float) $pagosPlanilla->importe_c,
        ];

        // totales generales (opcional para mostrar)
        $resumen['ingresos_totales'] = $resumen['ing_p'] + $resumen['ing_d'] + $resumen['ing_c'];
        $resumen['egresos_totales'] = $resumen['egr_p'] + $resumen['egr_d'] + $resumen['egr_c'];
        $resumen['saldo_neto'] = $resumen['ingresos_totales'] - $resumen['egresos_totales'];

        // ======================
        // LISTAS (detalle)
        // ======================
        $ventasList = Venta::whereBetween('fecha_venta', [$ini, $fin])
            ->select([
                'id',
                'fecha_venta',
                'cliente_nombre',
                'comprobante_tipo_codigo',
                'serie',
                'correlativo',
                'total',
                'acuenta',
                'importe_p',
                'importe_c',
                'importe_d',
            ])
            ->where('estado', '!=', 'anulada')
            ->orderBy('fecha_venta')
            ->get();

        $comprasList = Compra::whereBetween('fecha_compra', [$ini, $fin])
            ->select([
                'id',
                'fecha_compra',
                'proveedor_nombre',
                'comprobante_tipo_codigo',
                'serie',
                'correlativo',
                'total',
                'acuenta',
                'importe_p',
                'importe_c',
                'importe_d',
            ])
            ->where('estado', '!=', 'anulada')
            ->orderBy('fecha_compra')
            ->get();

        $ventaProvisionalesList = VentaProvisional::whereBetween('fecha_provisional', [$ini, $fin])
            ->select([
                'id',
                'fecha_provisional',
                'cliente_nombre',
                'monto',
                'importe_p',
                'importe_c',
                'importe_d',
                // si quieres también documento:
                // 'comprobante_tipo_codigo','serie','correlativo','numero_recibo'
            ])
            // ->where('estado', '!=', 'anulada')
            ->orderBy('fecha_provisional')
            ->get();

        $compraProvisionalesList = CompraProvisional::whereBetween('fecha_provisional', [$ini, $fin])
            ->select([
                'id',
                'fecha_provisional',
                'proveedor_nombre',
                'monto',
                'importe_p',
                'importe_c',
                'importe_d',
                // si quieres también documento:
                // 'comprobante_tipo_codigo','serie','correlativo','numero_recibo'
            ])
            // ->where('estado', '!=', 'anulada')
            ->orderBy('fecha_provisional')
            ->get();

        $gastosList = Gasto::whereBetween('fecha_gasto', [$ini, $fin])
            ->select([
                'id',
                'fecha_gasto',
                'descripcion',
                'monto',
                'importe_p',
                'importe_c',
                'importe_d',
                // si quieres también documento:
                // 'comprobante_tipo_codigo','serie','correlativo','numero_recibo'
            ])
            // ->where('estado', '!=', 'anulada')
            ->orderBy('fecha_gasto')
            ->get();

        // ======================
        // PLANILLA LISTAS
        // ======================
        $adelantosList = PlanillaAdelanto::whereBetween('fecha', [$ini, $fin])
            ->with('empleado')
            ->select([
                'id',
                'fecha',
                'empleado_id',
                'monto',
                'importe_p',
                'importe_d',
                'importe_c',
            ])
            ->orderBy('fecha')
            ->get();

        $prestamosList = PlanillaPrestamo::whereBetween('fecha_prestamo', [$ini, $fin])
            ->with('empleado')
            ->select([
                'id',
                'fecha_prestamo',
                'empleado_id',
                'monto_original',
                'importe_p',
                'importe_d',
                'importe_c',
            ])
            ->orderBy('fecha_prestamo')
            ->get();

        $pagosPrestamosList = PlanillaPrestamoPago::whereBetween('fecha_pago', [$ini, $fin])
            ->with(['prestamo.empleado'])
            ->select([
                'id',
                'fecha_pago',
                'planilla_prestamo_id',
                'monto_pagado',
                'importe_p',
                'importe_d',
                'importe_c',
            ])
            ->orderBy('fecha_pago')
            ->get();

        $pagosPlanillaList = PlanillaPago::whereBetween('fecha_pago', [$ini, $fin])
            ->with('empleado')
            ->select([
                'id',
                'fecha_pago',
                'empleado_id',
                'total_pagar',
                'importe_p',
                'importe_d',
                'importe_c',
            ])
            ->orderBy('fecha_pago')
            ->get();

        return view('reportes.caja.detallado', [
            'ventas' => $ventas,
            'compras' => $compras,
            'ventaProvisionales' => $ventaProvisionales,
            'compraProvisionales' => $compraProvisionales,
            'gastos' => $gastos,
            'adelantos' => $adelantos,
            'prestamos' => $prestamos,
            'pagosPrestamos' => $pagosPrestamos,
            'pagosPlanilla' => $pagosPlanilla,
            'resumen' => $resumen,
            'ventasList' => $ventasList,
            'comprasList' => $comprasList,
            'ventaProvisionalesList' => $ventaProvisionalesList,
            'compraProvisionalesList' => $compraProvisionalesList,
            'gastosList' => $gastosList,
            'adelantosList' => $adelantosList,
            'prestamosList' => $prestamosList,
            'pagosPrestamosList' => $pagosPrestamosList,
            'pagosPlanillaList' => $pagosPlanillaList,
            'fechaInicio' => $ini,
            'fechaFin' => $fin,
        ]);
    }

    public function generalPdf(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $ini = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($data['fecha_fin'])->endOfDay();

        $ventas = Venta::whereBetween('fecha_venta', [$ini, $fin])
            ->where('estado', '!=', 'anulada')
            ->selectRaw('
                COALESCE(SUM(total),0) as total,
                COALESCE(SUM(acuenta),0) as acuenta,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        $compras = Compra::whereBetween('fecha_compra', [$ini, $fin])
            ->where('estado', '!=', 'anulada')
            ->selectRaw('
                COALESCE(SUM(total),0) as total,
                COALESCE(SUM(acuenta),0) as acuenta,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        $ventaProvisionales = VentaProvisional::whereBetween('fecha_provisional', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            // ->where('estado', '!=', 'anulada')
            ->first();

        $compraProvisionales = CompraProvisional::whereBetween('fecha_provisional', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            // ->where('estado', '!=', 'anulada')
            ->first();

        $gastos = Gasto::whereBetween('fecha_gasto', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            // ->where('estado', '!=', 'anulada')
            ->first();

        // ======================
        // PLANILLA - ADELANTOS
        // ======================
        $adelantos = PlanillaAdelanto::whereBetween('fecha', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // ======================
        // PLANILLA - PRESTAMOS (otorgados)
        // ======================
        $prestamos = PlanillaPrestamo::whereBetween('fecha_prestamo', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto_original),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // ======================
        // PLANILLA - PAGOS DE PRESTAMOS (pagos de loans - van a consortium)
        // ======================
        $pagosPrestamos = PlanillaPrestamoPago::whereBetween('fecha_pago', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto_pagado),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // ======================
        // PLANILLA - PAGOS (pago de planilla)
        // ======================
        $pagosPlanilla = PlanillaPago::whereBetween('fecha_pago', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(total_pagar),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        $resumen = [
            // ingresos: ventas + provisionales
            'ing_p' => (float) $ventas->importe_p + (float) $ventaProvisionales->importe_p,
            'ing_d' => (float) $ventas->importe_d + (float) $ventaProvisionales->importe_d,
            'ing_c' => (float) $ventas->importe_c + (float) $ventaProvisionales->importe_c,

            // egresos: compras + gastos + planilla
            'egr_p' => (float) $compras->importe_p + (float) $compraProvisionales->importe_p + (float) $gastos->importe_p + (float) $adelantos->importe_p + (float) $prestamos->importe_p + (float) $pagosPrestamos->importe_p + (float) $pagosPlanilla->importe_p,
            'egr_d' => (float) $compras->importe_d + (float) $compraProvisionales->importe_d + (float) $gastos->importe_d + (float) $adelantos->importe_d + (float) $prestamos->importe_d + (float) $pagosPrestamos->importe_d + (float) $pagosPlanilla->importe_d,
            'egr_c' => (float) $compras->importe_c + (float) $compraProvisionales->importe_c + (float) $gastos->importe_c + (float) $adelantos->importe_c + (float) $prestamos->importe_c + (float) $pagosPrestamos->importe_c + (float) $pagosPlanilla->importe_c,

            // neto por caja
            'net_p' => ((float) $ventas->importe_p + (float) $ventaProvisionales->importe_p) - (float) $compras->importe_p - (float) $compraProvisionales->importe_p - (float) $gastos->importe_p - (float) $adelantos->importe_p - (float) $prestamos->importe_p - (float) $pagosPrestamos->importe_p - (float) $pagosPlanilla->importe_p,
            'net_d' => ((float) $ventas->importe_d + (float) $ventaProvisionales->importe_d) - (float) $compras->importe_d - (float) $compraProvisionales->importe_d - (float) $gastos->importe_d - (float) $adelantos->importe_d - (float) $prestamos->importe_d - (float) $pagosPrestamos->importe_d - (float) $pagosPlanilla->importe_d,
            'net_c' => ((float) $ventas->importe_c + (float) $ventaProvisionales->importe_c) - (float) $compras->importe_c - (float) $compraProvisionales->importe_c - (float) $gastos->importe_c - (float) $adelantos->importe_c - (float) $prestamos->importe_c - (float) $pagosPrestamos->importe_c - (float) $pagosPlanilla->importe_c,
        ];

        $pdf = Pdf::loadView('reportes.caja.general_pdf', [
            'ventas' => $ventas,
            'compras' => $compras,
            'ventaProvisionales' => $ventaProvisionales,
            'compraProvisionales' => $compraProvisionales,
            'gastos' => $gastos,
            'adelantos' => $adelantos,
            'prestamos' => $prestamos,
            'pagosPrestamos' => $pagosPrestamos,
            'pagosPlanilla' => $pagosPlanilla,
            'resumen' => $resumen,
            'fechaInicio' => $ini,
            'fechaFin' => $fin,
        ])->setPaper('a4', 'portrait'); // o 'landscape' si quieres

        return $pdf->stream("reporte_caja_{$ini->format('Ymd')}_{$fin->format('Ymd')}.pdf");
    }
}
