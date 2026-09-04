<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CajaIngreso;
use App\Models\Compra;
use App\Models\CompraProvisional;
use App\Models\Costo;
use App\Models\Gasto;
use App\Models\PlanillaAdelanto;
use App\Models\PlanillaPago;
use App\Models\PlanillaPrestamo;
use App\Models\PlanillaPrestamoPago;
use App\Models\Venta;
use App\Models\VentaProvisional;
use Carbon\Carbon;

/**
 * Servicio Centralizado de Reporte de Caja.
 *
 * Unifica las consultas y cálculos para el reporte general, reporte detallado
 * y exportación a PDF de caja, garantizando consistencia absoluta de montos.
 */
class ReporteCajaService
{
    /**
     * Obtiene los datos consolidados y las listas detalladas del reporte de caja.
     *
     * @param  Carbon  $ini  Fecha inicio
     * @param  Carbon  $fin  Fecha fin
     * @param  bool  $incluirListas  Si es true, incluye las colecciones para el reporte detallado
     */
    public function obtenerDatosReporteCaja(Carbon $ini, Carbon $fin, bool $incluirListas = false): array
    {
        // 1. VENTAS
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

        // 2. COMPRAS
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

        // 3. PROVISIONALES VENTAS
        $ventaProvisionales = VentaProvisional::whereBetween('fecha_provisional', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // 4. PROVISIONALES COMPRAS
        $compraProvisionales = CompraProvisional::whereBetween('fecha_provisional', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // 5. GASTOS
        $gastos = Gasto::whereBetween('fecha_gasto', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->whereNull('planilla_mes')
            ->whereNull('planilla_anio')
            ->first();

        // 6. COSTOS
        $costos = Costo::whereBetween('fecha_costo', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // 7. PLANILLA - ADELANTOS (Incluye históricos sin filtrar por estado del empleado)
        $adelantos = PlanillaAdelanto::whereBetween('fecha', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // 8. PLANILLA - PRESTAMOS (Incluye históricos sin filtrar por estado del empleado)
        $prestamos = PlanillaPrestamo::whereBetween('fecha_prestamo', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto_original),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // 9. PLANILLA - PAGOS DE PRESTAMOS (Incluye históricos sin filtrar por estado del empleado)
        $pagosPrestamos = PlanillaPrestamoPago::whereBetween('fecha_pago', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(monto_pagado),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // 10. PLANILLA - PAGOS PLANILLA (Incluye históricos sin filtrar por estado del empleado)
        $pagosPlanilla = PlanillaPago::whereBetween('fecha_pago', [$ini, $fin])
            ->pagados()
            ->selectRaw('
                COALESCE(SUM(total_pagar),0) as total,
                COALESCE(SUM(importe_p),0) as importe_p,
                COALESCE(SUM(importe_d),0) as importe_d,
                COALESCE(SUM(importe_c),0) as importe_c
            ')
            ->first();

        // 11. INGRESOS MANUALES A CAJA
        $ingresosCaja = CajaIngreso::whereBetween('fecha', [$ini, $fin])
            ->selectRaw('
                COALESCE(SUM(CASE WHEN caja_destino = "P" THEN monto ELSE 0 END),0) AS importe_p,
                COALESCE(SUM(CASE WHEN caja_destino = "D" THEN monto ELSE 0 END),0) AS importe_d,
                COALESCE(SUM(CASE WHEN caja_destino = "C" THEN monto ELSE 0 END),0) AS importe_c,
                COALESCE(SUM(monto),0) AS total
            ')
            ->first();

        $ingIC = (float) ($ingresosCaja->importe_p ?? 0);
        $ingID = (float) ($ingresosCaja->importe_d ?? 0);
        $ingCC = (float) ($ingresosCaja->importe_c ?? 0);

        // CONSTRUIR RESUMEN UNIFICADO
        $resumen = [
            'ing_p' => (float) $ventas->importe_p + (float) $ventaProvisionales->importe_p + $ingIC,
            'ing_d' => (float) $ventas->importe_d + (float) $ventaProvisionales->importe_d + $ingID,
            'ing_c' => (float) $ventas->importe_c + (float) $ventaProvisionales->importe_c + $ingCC,

            'egr_p' => (float) $compras->importe_p + (float) $compraProvisionales->importe_p + (float) $gastos->importe_p + (float) $adelantos->importe_p + (float) $prestamos->importe_p + (float) $pagosPrestamos->importe_p + (float) $pagosPlanilla->importe_p + (float) $costos->importe_p,
            'egr_d' => (float) $compras->importe_d + (float) $compraProvisionales->importe_d + (float) $gastos->importe_d + (float) $adelantos->importe_d + (float) $prestamos->importe_d + (float) $pagosPrestamos->importe_d + (float) $pagosPlanilla->importe_d + (float) $costos->importe_d,
            'egr_c' => (float) $compras->importe_c + (float) $compraProvisionales->importe_c + (float) $gastos->importe_c + (float) $adelantos->importe_c + (float) $prestamos->importe_c + (float) $pagosPrestamos->importe_c + (float) $pagosPlanilla->importe_c + (float) $costos->importe_c,
        ];

        $resumen['net_p'] = $resumen['ing_p'] - $resumen['egr_p'];
        $resumen['net_d'] = $resumen['ing_d'] - $resumen['egr_d'];
        $resumen['net_c'] = $resumen['ing_c'] - $resumen['egr_c'];

        $resumen['ingresos_totales'] = $resumen['ing_p'] + $resumen['ing_d'] + $resumen['ing_c'];
        $resumen['egresos_totales'] = $resumen['egr_p'] + $resumen['egr_d'] + $resumen['egr_c'];
        $resumen['saldo_neto'] = $resumen['ingresos_totales'] - $resumen['egresos_totales'];

        $data = [
            'ventas' => $ventas,
            'compras' => $compras,
            'ventaProvisionales' => $ventaProvisionales,
            'compraProvisionales' => $compraProvisionales,
            'gastos' => $gastos,
            'costos' => $costos,
            'adelantos' => $adelantos,
            'prestamos' => $prestamos,
            'pagosPrestamos' => $pagosPrestamos,
            'pagosPlanilla' => $pagosPlanilla,
            'resumen' => $resumen,
            'ingresosCaja' => $ingresosCaja,
            'fechaInicio' => $ini,
            'fechaFin' => $fin,
        ];

        if ($incluirListas) {
            $data['ventasList'] = Venta::whereBetween('fecha_venta', [$ini, $fin])
                ->select(['id', 'fecha_venta', 'cliente_nombre', 'comprobante_tipo_codigo', 'serie', 'correlativo', 'total', 'acuenta', 'importe_p', 'importe_c', 'importe_d'])
                ->where('estado', '!=', 'anulada')
                ->orderBy('fecha_venta')
                ->get();

            $data['comprasList'] = Compra::whereBetween('fecha_compra', [$ini, $fin])
                ->select(['id', 'fecha_compra', 'proveedor_nombre', 'comprobante_tipo_codigo', 'serie', 'correlativo', 'total', 'acuenta', 'importe_p', 'importe_c', 'importe_d'])
                ->where('estado', '!=', 'anulada')
                ->orderBy('fecha_compra')
                ->get();

            $data['ventaProvisionalesList'] = VentaProvisional::whereBetween('fecha_provisional', [$ini, $fin])
                ->select(['id', 'fecha_provisional', 'cliente_nombre', 'monto', 'importe_p', 'importe_c', 'importe_d'])
                ->orderBy('fecha_provisional')
                ->get();

            $data['compraProvisionalesList'] = CompraProvisional::whereBetween('fecha_provisional', [$ini, $fin])
                ->select(['id', 'fecha_provisional', 'proveedor_nombre', 'monto', 'importe_p', 'importe_c', 'importe_d'])
                ->orderBy('fecha_provisional')
                ->get();

            $data['gastosList'] = Gasto::whereBetween('fecha_gasto', [$ini, $fin])
                ->select(['id', 'fecha_gasto', 'descripcion', 'monto', 'importe_p', 'importe_c', 'importe_d'])
                ->whereNull('planilla_mes')
                ->whereNull('planilla_anio')
                ->orderBy('fecha_gasto')
                ->get();

            $data['costosList'] = Costo::whereBetween('fecha_costo', [$ini, $fin])
                ->with(['costoTipo', 'categoriaCosto'])
                ->select(['id', 'fecha_costo', 'descripcion', 'responsable', 'monto', 'importe_p', 'importe_d', 'importe_c', 'costo_tipo_id', 'categoria_costo_id', 'numero_recibo'])
                ->orderBy('fecha_costo')
                ->get();

            $data['adelantosList'] = PlanillaAdelanto::whereBetween('fecha', [$ini, $fin])
                ->with('empleado')
                ->select(['id', 'fecha', 'empleado_id', 'monto', 'importe_p', 'importe_d', 'importe_c'])
                ->orderBy('fecha')
                ->get();

            $data['prestamosList'] = PlanillaPrestamo::whereBetween('fecha_prestamo', [$ini, $fin])
                ->with('empleado')
                ->select(['id', 'fecha_prestamo', 'empleado_id', 'monto_original', 'importe_p', 'importe_d', 'importe_c'])
                ->orderBy('fecha_prestamo')
                ->get();

            $data['pagosPrestamosList'] = PlanillaPrestamoPago::whereBetween('fecha_pago', [$ini, $fin])
                ->with(['prestamo.empleado'])
                ->select(['id', 'fecha_pago', 'planilla_prestamo_id', 'monto_pagado', 'importe_p', 'importe_d', 'importe_c'])
                ->orderBy('fecha_pago')
                ->get();

            $data['pagosPlanillaList'] = PlanillaPago::whereBetween('fecha_pago', [$ini, $fin])
                ->pagados()
                ->with('empleado')
                ->select(['id', 'fecha_pago', 'empleado_id', 'total_pagar', 'importe_p', 'importe_d', 'importe_c'])
                ->orderBy('fecha_pago')
                ->get();

            $data['ingresosCajaList'] = CajaIngreso::whereBetween('fecha', [$ini, $fin])
                ->orderBy('fecha', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        }

        return $data;
    }
}
