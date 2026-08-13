<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estado de cuenta</title>
    <style>
        @page { margin: 90px 25px 60px 25px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#111; }

        .page-header{ position: fixed; top: -70px; left: 0; right: 0; height: 60px; }
        .page-header table{ width:100%; border-collapse: collapse; }
        .text-right{ text-align:right; }
        .muted{ color:#555; font-size: 11px; }

        .title{ font-size: 16px; font-weight: 700; margin: 0 0 8px 0; text-align:center; }

        .box{ border:1px solid #ddd; padding:8px; border-radius: 4px; margin-bottom:10px; }

        table.report{ width:100%; border-collapse: collapse; }
        table.report th, table.report td{ border:1px solid #ddd; padding:6px 6px; vertical-align: top; }
        table.report th{ background:#f3f3f3; font-weight:700; font-size: 11px; }

        table.main-report th { background: #add8e6; }

        .num{ text-align:right; white-space: nowrap; }
        .nowrap{ white-space: nowrap; }
        .text-center { text-align: center; }
        .text-red { color: red !important; }

        .page-number:before { content: counter(page); }

        .row-saldo-anterior td{ font-weight:700; background:#f9f9f9; }
        .row-venta td{ background:#fcfcfc; font-weight:700; }
        .row-pago td{ padding-left: 18px; }
        .row-abono td{ background:#fffdf5; }

        .section-title{ font-weight: 700; margin: 10px 0 6px 0; }

        .total-row td{
            font-weight:700;
            border-top: 2px solid #111 !important;
            background: #fafafa;
        }
    </style>
</head>

<body>

@php
    // ==========================================================
    // 1) PRE-CÁLCULO PARA QUE LA CABECERA COINCIDA (NO MÁS 0.00)
    // ==========================================================
    $saldoInicial = (float)($saldoInicial ?? 0);

    // Créditos del rango (ventas) => suma de credito_base de ventas en el rango
    $totCreditoMostrado = collect($ventas ?? [])->filter(function($v) use ($ini) {
        return \Carbon\Carbon::parse($v->fecha_venta)->gte($ini);
    })->sum(function($v){
        return (float)($v->credito_base ?? 0);
    });

    // Pagos enlazados del rango => suma de montos de pagos de todas las ventas
    $totPagoRango = collect($ventas ?? [])->sum(function($v){
        return collect($v->pagos ?? [])->sum(function($p){
            return (float)($p->monto ?? 0);
        });
    });

    // Abonos sueltos (no enlazados) => SOLO se muestran aparte (no afectan el saldo)
    $totAbonoSuelto = collect($abonosSueltos ?? [])->sum(function($p){
        return (float)($p->monto ?? 0);
    });

    // Saldo final del reporte (abonos sueltos NO descuentan aquí)
    $saldoFinalCalculado = round(
    $saldoInicial
    + $totCreditoMostrado
    - $totPagoRango,
    2
);

$saldoFinalReporte = round(
    (float) ($saldoFinal ?? $saldoFinalCalculado),
    2
);

    // ==========================================================
    // NUEVO: AGRUPAR PAGOS POR DOCUMENTO DE LA VENTA ASOCIADA
    // ==========================================================
    $movimientos = collect();
    foreach($ventas ?? [] as $v) {
        $docVenta = $v->documento ??
            trim(($v->comprobante_tipo_codigo ? $v->comprobante_tipo_codigo.' ' : '').$v->serie.'-'.$v->correlativo);

        $fechaVentaCarbon = \Carbon\Carbon::parse($v->fecha_venta);

        // Agregar la venta SOLO si fue emitida en el rango (>= $ini)
        if ($fechaVentaCarbon->gte($ini)) {
            $movimientos->push((object)[
                'tipo'              => 'VENTA',
                'fecha'             => $fechaVentaCarbon,
                'documento'         => $docVenta,
                'fecha_vencimiento' => $v->fecha_vencimiento,
                'credito_base'      => (float)($v->credito_base ?? 0)
            ]);
        }

        // Agrupar pagos por numero_recibo dentro de esta venta
        $pagosPorRecibo = collect($v->pagos ?? [])->groupBy(function($p) {
            return $p->numero_recibo ?? 'sin_recibo';
        });

        foreach($pagosPorRecibo as $numRecibo => $pagosDelRecibo) {
            $montoTotal = $pagosDelRecibo->sum(function($p) {
                return (float)($p->monto ?? 0);
            });

            $primerPago = $pagosDelRecibo->first();
            $fechaPago = $primerPago->fecha_provisional ?? $v->fecha_venta;

            // Usar el documento de la venta como referencia del pago
            $movimientos->push((object)[
                'tipo'           => 'PAGO',
                'fecha'          => \Carbon\Carbon::parse($fechaPago),
                'documento_pago' => $docVenta, // Usar documento de la venta asociada
                'monto'          => $montoTotal
            ]);
        }
    }

    // Ordenar: primero ventas, luego pagos de cada fecha
    $movimientosFinal = $movimientos->sort(function($a, $b) {
        if ($a->fecha->equalTo($b->fecha)) {
            return $a->tipo === 'VENTA' ? -1 : 1;
        }
        return $a->fecha->lessThan($b->fecha) ? -1 : 1;
    });

    // ==========================================================
    // 2) ACUMULADOR PARA LA TABLA (DEBE TERMINAR EN saldoFinalReporte)
    // ==========================================================
    $saldoAcum = $saldoInicial;
@endphp

<div class="page-header">
    <table>
        <tr>
            <td><strong>CONSORCIOS VILLEGAS EIRL</strong></td>
            <td class="text-right muted">
                Página <span class="page-number"></span>
                &nbsp;&nbsp;{{ now()->format('d/m/Y H:i:s') }}
            </td>
        </tr>
        <tr>
            <td class="muted">Rango: {{ $ini->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }}</td>
            <td class="text-right muted">Usuario: {{ auth()->user()->name ?? '' }}</td>
        </tr>
    </table>
</div>

<div class="title">Estado de cuenta del Cliente</div>

<div class="box">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:65%;">
                <div><strong>Cliente:</strong> {{ $clienteNombre }}</div>
                <div><strong>Dirección:</strong> {{ $clienteData->direccion ?? '-' }}</div>
                <div><strong>Teléfono:</strong> {{ $clienteData->telefono ?? '-' }}</div>
            </td>
            <td class="num" style="width:35%;">
                <div><strong>Saldo antes del filtro:</strong> {{ number_format($saldoInicial, 2) }}</div>
                <div><strong>Saldo final (según reporte):</strong> {{ number_format($saldoFinalReporte, 2) }}</div>
                <div><strong>Saldo a favor (abonos sueltos):</strong> {{ number_format($totAbonoSuelto, 2) }}</div>
            </td>
        </tr>
    </table>
</div>

<table class="report main-report">
    <thead>
    <tr>
        <th class="nowrap">Fecha</th>
        <th>Documento</th>
        <th class="nowrap">F. Venc.</th>
        <th>Detalle</th>
        <th class="num">Crédito</th>
        <th class="num">Pago</th>
        <th class="num">Saldo</th>
    </tr>
    </thead>

    <tbody>

    {{-- SALDO ANTERIOR --}}
    <tr class="row-saldo-anterior">
        <td class="nowrap">{{ $ini->copy()->subDay()->format('d/m/Y') }}</td>
        <td>SALDO ANTERIOR</td>
        <td class="nowrap">{{ $ini->format('d/m/Y') }}</td>
        <td></td>
        <td class="num">0.00</td>
        <td class="num">0.00</td>
        <td class="num">{{ number_format($saldoAcum, 2) }}</td>
    </tr>

    {{-- VENTAS + PAGOS AGRUPADOS (SALDO ACUMULADO) --}}
    @forelse($movimientosFinal as $mov)
        @if($mov->tipo === 'VENTA')
            @php
                $creditoVenta = $mov->credito_base;
                $saldoAcum += $creditoVenta;
            @endphp
            <tr class="row-venta">
                <td class="nowrap">{{ $mov->fecha->format('d/m/Y') }}</td>
                <td>{{ $mov->documento }}</td>
                <td class="nowrap">
                    {{ $mov->fecha_vencimiento ? \Carbon\Carbon::parse($mov->fecha_vencimiento)->format('d/m/Y') : '-' }}
                </td>
                <td>ventas</td>
                <td class="num">{{ number_format($creditoVenta, 2) }}</td>
                <td class="num">0.00</td>
                <td class="num">{{ number_format($saldoAcum, 2) }}</td>
            </tr>
        @else
            @php
                $montoPago = $mov->monto;
                $saldoAcum -= $montoPago;
            @endphp
            <tr class="row-pago">
                <td class="nowrap text-red">{{ $mov->fecha->format('d/m/Y') }}</td>
                <td class="nowrap text-red">{{ $mov->documento_pago }}</td>
                <td class="nowrap"></td>
                <td class="text-red">Pago / Cobro - Venta</td>
                <td class="num"></td>
                <td class="num text-red">{{ number_format($montoPago, 2) }}</td>
                <td class="num">{{ number_format($saldoAcum, 2) }}</td>
            </tr>
        @endif
    @empty
        <tr>
            <td colspan="7" class="text-right muted">Sin ventas/pagos enlazados en el rango.</td>
        </tr>
    @endforelse

    </tbody>
</table>

{{-- CUADRO DE ABONOS SUELTOS (SE MANTIENE SEPARADO) --}}
<div class="section-title">Saldo a favor (abonos sin venta asociada)</div>

<table class="report">
    <thead>
    <tr>
        <th class="nowrap">Fecha</th>
        <th>Documento</th>
        <th>Detalle</th>
        <th class="num">Pago</th>
    </tr>
    </thead>
    <tbody>
    @forelse($abonosSueltos as $p)
        @php
            $docPago = (!empty($p->serie) && !empty($p->correlativo))
                ? trim(($p->comprobante_tipo_codigo ? $p->comprobante_tipo_codigo.' ' : '').$p->serie.'-'.$p->correlativo)
                : ('REC '.($p->numero_recibo ?? ''));
        @endphp

        <tr class="row-abono">
            <td class="nowrap">{{ \Carbon\Carbon::parse($p->fecha_provisional)->format('d/m/Y') }}</td>
            <td>{{ $docPago }}</td>
            <td>
                ABONO
                @if(!empty($p->comentario))
                    <div class="muted">{{ $p->comentario }}</div>
                @endif
            </td>
            <td class="num">{{ number_format((float)($p->monto ?? 0), 2) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="4" class="text-center muted">Sin abonos sueltos en el rango.</td>
        </tr>
    @endforelse
    </tbody>
</table>

{{-- FOOTER COHERENTE CON LA CABECERA --}}
<table class="report" style="margin-top:10px;">
    <tbody>
    <tr class="total-row">
        <td colspan="4" rowspan="2" style="border:none !important; background:transparent !important;"></td>
        <td class="num">Créditos</td>
        <td class="num">Pagos</td>
        <td class="num">Saldo</td>
    </tr>
    <tr class="total-row">
        <td class="num">S/ {{ number_format($totCreditoMostrado, 2) }}</td>
        <td class="num text-red">S/ {{ number_format($totPagoRango, 2) }}</td>
        <td class="num">S/ {{ number_format($saldoFinalReporte, 2) }}</td>
    </tr>
    </tbody>
</table>

{{-- (Opcional informativo) Validación rápida: el acumulado final debe coincidir con saldoFinalReporte --}}
{{-- <div class="muted">
    Check: acumulado={{ number_format($saldoAcum,2) }} | saldoFinalReporte={{ number_format($saldoFinalReporte,2) }}
</div> --}}

</body>
</html>