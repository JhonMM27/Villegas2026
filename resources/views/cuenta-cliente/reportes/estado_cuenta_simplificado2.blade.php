<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Estado de cuenta Simplificado</title>

    <style>
        @page { margin: 80px 18px 50px 18px; }

        body{
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color:#111;
            background: #e9e9e9;
        }

        .page-header{ position: fixed; top: -60px; left: 0; right: 0; height: 50px; }
        .page-header table{ width:100%; border-collapse: collapse; }
        .text-right{ text-align:right; }
        .muted{ color:#444; font-size: 9px; }

        .title{
            font-size: 12px;
            font-weight: 700;
            text-align:center;
            margin: 0 0 6px 0;
            letter-spacing: .3px;
        }

        .box{
            margin: 6px 0 8px 0;
            padding: 6px 8px;
            border: 1px solid #bbb;
            background: #f1f1f1;
        }
        .box table{ width:100%; border-collapse: collapse; }
        .box td{ padding: 1px 0; }

        table.report{
            width:100%;
            border-collapse: collapse;
            background: transparent;
            table-layout: fixed;
        }

        table.report th{
            font-size: 9px;
            font-weight: 700;
            padding: 3px 4px;
            text-transform: uppercase;
            border-bottom: 1px solid #333;
            color:#111;
            background: transparent;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        table.report td{
            padding: 2px 4px;
            vertical-align: top;
            border-bottom: 1px dotted #666;
            background: transparent;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        table.report td, table.report th{
            border-left: none;
            border-right: none;
            border-top: none;
        }

        .num{ text-align:right; white-space: nowrap; }
        .nowrap{ white-space: nowrap; }

        .row-venta td{ font-weight: 700; }
        .row-pago td{ padding-left: 14px; font-weight: 400; }

        .page-number:before { content: counter(page); }
    </style>
</head>

<body>

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

<div class="title">Estado de Cuenta del Cliente (Simplificado)</div>

<div class="box">
    <table>
        <tr>
            <td style="width:65%;">
                <div><strong>Cliente:</strong> {{ $clienteNombre }}</div>
                <div><strong>Dirección:</strong> {{ $clienteData->direccion ?? '' }}</div>
                <div><strong>Teléfono:</strong> {{ $clienteData->telefono ?? '' }}</div>
            </td>
            <td class="text-right" style="width:35%;">
                <div><strong>Saldo anterior:</strong> {{ number_format((float)($saldoInicial ?? 0), 2) }}</div>
                <div><strong>Saldo final (ventas):</strong> {{ number_format($saldoFinal, 2) }}<</div>
                <div><strong>Saldo a favor:</strong> {{ number_format((float)($totAbonoSuelto ?? 0), 2) }}</div>
            </td>
        </tr>
    </table>
</div>

<table class="report">
    <thead>
        <tr>
            <th style="width: 95px;">Doc. Venta</th>
            <th style="width: 85px;">Referencia</th>
            <th style="width: 60px;">Fecha Venta</th>
            <th class="num" style="width: 60px;">Imp Venta</th>
            <th class="num" style="width: 60px;">Cobranza</th>
            <th class="num" style="width: 60px;">Saldo</th>
            <th style="width: 70px;">N Recibo</th>
            <th class="num" style="width: 70px;">Acumulado</th>
        </tr>
    </thead>

    <tbody>
@php
    $saldoAcum = (float)($saldoInicial ?? 0);
@endphp

<tr class="row-venta">
    <td class="nowrap">{{ $ini->copy()->subDay()->format('d/m/Y') }}</td>
    <td colspan="6">SALDO ANTERIOR</td>
    <td class="num">{{ number_format($saldoAcum, 2) }}</td>
</tr>

@forelse($ventas as $v)
    @php
        $pagos = ($v->pagos ?? collect());
        $pagos = is_array($pagos) ? collect($pagos) : $pagos;
        $nPagos = $pagos->count();

        $docVenta   = $v->documento
            ?? trim(($v->comprobante_tipo_codigo ? $v->comprobante_tipo_codigo.' ' : '').$v->serie.'-'.$v->correlativo);

        $refVenta   = $v->pago_forma_nombre ?? '';
        $fechaVentaFmt = \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y');

        $impVenta   = (float)($v->credito_base ?? 0);

        // Saldo de esa venta al entrar al rango (igual que detallado)
        $saldoVenta = (float)($v->saldo_inicio_rango ?? 0);

        // Acumulado sube al “entrar” la venta
        $saldoAcum += $saldoVenta;
    @endphp

    {{-- ✅ Caso 1: Venta SIN provisionales -> mostrar una fila --}}
    @if($nPagos === 0)
        <tr class="row-venta">
            <td class="nowrap">{{ $docVenta }}</td>
            <td>{{ $refVenta }}</td>
            <td class="nowrap">{{ $fechaVentaFmt }}</td>
            <td class="num">{{ number_format($impVenta, 2) }}</td>
            <td class="num">0.00</td>
            <td class="num">{{ number_format($saldoVenta, 2) }}</td>
            <td class="nowrap"></td>

            {{-- ✅ acumulado + fecha (fecha venta, porque no hay provisional) --}}
            <td class="num">
                {{ number_format($saldoAcum, 2) }}
                <span class="muted">({{ $fechaVentaFmt }})</span>
            </td>
        </tr>
    @else
        {{-- ✅ Caso 2: Venta CON provisionales -> una fila por provisional (sin repetir datos de venta) --}}
        @foreach($pagos as $i => $p)
            @php
                $monto = (float)($p->monto ?? 0);
                $saldoDespues = (float)($p->saldo_despues ?? 0);
                $nRecibo = $p->numero_recibo ?? '';

                $fechaProvFmt = !empty($p->fecha_provisional)
                    ? \Carbon\Carbon::parse($p->fecha_provisional)->format('d/m/Y')
                    : '';

                // Acumulado baja según lo que se reduce el saldo de la venta
                $saldoAcum -= ($saldoVenta - $saldoDespues);
                $saldoVenta = $saldoDespues;

                $esUltimo  = ($i === $nPagos - 1);
                $esPrimero = ($i === 0);
            @endphp

            <tr class="row-pago">
                <td class="nowrap">{{ $esPrimero ? $docVenta : '' }}</td>
                <td>{{ $esPrimero ? $refVenta : '' }}</td>
                <td class="nowrap">{{ $esPrimero ? $fechaVentaFmt : '' }}</td>
                <td class="num">{{ $esPrimero ? number_format($impVenta, 2) : '' }}</td>

                <td class="num">{{ number_format($monto, 2) }}</td>
                <td class="num">{{ number_format($saldoDespues, 2) }}</td>
                <td class="nowrap">{{ $nRecibo }}</td>

                {{-- ✅ acumulado + fecha del provisional (solo en el último) --}}
                <td class="num">
                    @if($esUltimo)
                        {{ number_format($saldoAcum, 2) }}
                        <span class="muted">({{ $fechaProvFmt }})</span>
                    @endif
                </td>
            </tr>
        @endforeach
    @endif

@empty
    <tr>
        <td colspan="8" class="text-right muted">Sin movimientos en el rango.</td>
    </tr>
@endforelse
</tbody>
</table>

</body>
</html>