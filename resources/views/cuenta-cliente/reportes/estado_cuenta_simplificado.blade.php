<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Estado de cuenta Simplificado</title>

    <style>
        @page {
            margin: 80px 18px 50px 18px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111;
            /* background: #e9e9e9; */
        }

        .page-header {
            position: fixed;
            top: -60px;
            left: 0;
            right: 0;
            height: 50px;
        }

        .page-header table {
            width: 100%;
            border-collapse: collapse;
        }

        .text-right {
            text-align: right;
        }

        .muted {
            color: #444;
            font-size: 9px;
        }

        .title {
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            margin: 0 0 6px 0;
            letter-spacing: .3px;
        }

        .box {
            margin: 6px 0 8px 0;
            padding: 6px 8px;
            border: 1px solid #bbb;
            /* background: #f1f1f1; */
        }

        .box table {
            width: 100%;
            border-collapse: collapse;
        }

        .box td {
            padding: 1px 0;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
            table-layout: fixed;
        }

        table.report th {
            font-size: 9px;
            font-weight: 700;
            padding: 3px 4px;
            text-transform: uppercase;
            border-bottom: 1px solid #333;
            color: #111;
            background: transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: clip;
        }

        table.report td {
            padding: 2px 4px;
            vertical-align: top;
            border-bottom: 1px dotted #666;
            background: transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: clip;
        }

        table.report td,
        table.report th {
            border-left: none;
            border-right: none;
            border-top: none;
        }

        .num {
            text-align: right;
            white-space: nowrap;
        }

        .nowrap {
            white-space: nowrap;
        }

        .center {
            text-align: center;
            white-space: nowrap;
        }

        .row-venta td {
            font-weight: 700;
        }

        .row-pago td {
            padding-left: 14px;
            font-weight: 400;
        }

        .row-abono td {
            background: #fffdf5;
        }

        .section-title {
            font-weight: 700;
            margin: 10px 0 6px 0;
        }

        .page-number:before {
            content: counter(page);
        }
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
                    <div><strong>Saldo anterior:</strong> {{ number_format((float) ($saldoInicial ?? 0), 2) }}</div>
                    <div><strong>Saldo final (ventas):</strong> {{ number_format($saldoFinal, 2) }}</div>
                    <div><strong>Saldo a favor:</strong> {{ number_format((float) ($totAbonoSuelto ?? 0), 2) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="report">

        <!-- ✅ Anchos FIJOS por colgroup (más confiable en PDF) -->
        <colgroup>
            <col style="width: 90px;"> <!-- Doc. Venta -->
            <col style="width: 90px;"> <!-- Referencia -->
            <col style="width: 45px; text-align:center;"> <!-- Fecha Venta (más angosta) -->
            <col style="width: 65px;"> <!-- Imp Venta -->
            <col style="width: 65px;"> <!-- Cobranza -->
            <col style="width: 60px;"> <!-- Saldo -->
            <col style="width: 60px;"> <!-- N Recibo -->
            <col style="width: 65px;"> <!-- Acum Monto -->
            <col style="width: 55px;"> <!-- Acum Fecha -->
        </colgroup>

        <thead>
            <tr>
                <th>Doc. Venta</th>
                <th>Referencia</th>
                <th class="center">Fecha Venta</th>
                <th class="num">Imp Venta</th>
                <th class="num">Cobranza</th>
                <th class="num">Saldo</th>
                <th>N Recibo</th>
                <th class="num" colspan="2">Acumulado</th>
            </tr>
        </thead>

        <tbody>
            @php
                /* Acumulador: suma de cada valor mostrado en la columna SALDO */
                $totalSaldoSuma = 0.0;

                $saldoAcum = (float) ($saldoInicial ?? 0);

                $provUsado = [];

                // total por provisional
                $provTotales = [];
                foreach ($ventas ?? collect() as $__v) {
                    $__pagos = $__v->pagos ?? collect();
                    $__pagos = is_array($__pagos) ? collect($__pagos) : $__pagos;

                    foreach ($__pagos as $__p) {
                        $key =
                            $__p->provisional_id ??
                            ($__p->provisionalId ??
                                trim((string) ($__p->numero_recibo ?? '')) .
                                    '|' .
                                    (string) ($__p->fecha_provisional ?? ''));

                        $m = (float) ($__p->monto ?? 0);

                        if (!isset($provTotales[$key])) {
                            $provTotales[$key] = [
                                'total' => 0.0,
                                'fecha' => $__p->fecha_provisional ?? null,
                            ];
                        }
                        $provTotales[$key]['total'] += $m;

                        if (empty($provTotales[$key]['fecha']) && !empty($__p->fecha_provisional)) {
                            $provTotales[$key]['fecha'] = $__p->fecha_provisional;
                        }
                    }
                }
            @endphp

            <tr class="row-venta">
                <td class="nowrap">{{ $ini->copy()->subDay()->format('d/m/Y') }}</td>
                <td colspan="7">SALDO ANTERIOR</td>
                <td class="num" colspan="2">{{ number_format($saldoAcum, 2) }}</td>
            </tr>

            @forelse($ventas as $v)
                @php
                    $pagos = $v->pagos ?? collect();
                    $pagos = is_array($pagos) ? collect($pagos) : $pagos;
                    $nPagos = $pagos->count();

                    $docVenta =
                        $v->documento ??
                        trim(
                            ($v->comprobante_tipo_codigo ? $v->comprobante_tipo_codigo . ' ' : '') .
                                $v->serie .
                                '-' .
                                $v->correlativo,
                        );

                    $refVenta = $v->pago_forma_nombre ?? '';
                    $fechaVentaCarbon = \Carbon\Carbon::parse($v->fecha_venta);
                    $fechaVentaFmt = $fechaVentaCarbon->format('d/m/Y');
                    $esVentaEnRango = $fechaVentaCarbon->gte($ini);

                    $impVenta = $esVentaEnRango ? (float) ($v->credito_base ?? 0) : 0.0;
                    $saldoVenta = (float) ($v->saldo_inicio_rango ?? 0);

                    if ($esVentaEnRango) {
                        $saldoAcum += $impVenta;
                    }
                @endphp

                @if ($nPagos === 0)
                    @php
                        /* Sin pagos: el saldo mostrado es saldoVenta */
                        $totalSaldoSuma += $saldoVenta;
                    @endphp
                    <tr class="row-venta">
                        <td class="nowrap">{{ $docVenta }}</td>
                        <td>{{ $refVenta }}</td>
                        <td class="center">{{ $fechaVentaFmt }}</td>
                        <td class="num">{{ number_format($impVenta, 2) }}</td>
                        <td class="num">0.00</td>
                        <td class="num">{{ number_format($saldoVenta, 2) }}</td>
                        <td class="nowrap"></td>

                        <td class="num"></td>
                        <td class="num"></td>
                    </tr>
                @else
                    @foreach ($pagos as $i => $p)
                        @php
                            $monto = (float) ($p->monto ?? 0);
                            $saldoDespues = (float) ($p->saldo_despues ?? 0);
                            $nRecibo = $p->numero_recibo ?? '';

                            $fechaProvFmt = !empty($p->fecha_provisional)
                                ? \Carbon\Carbon::parse($p->fecha_provisional)->format('d/m/Y')
                                : '';

                            $esPrimero = $i === 0;
                            $esUltimo = $i === $nPagos - 1;

                            // Solo actualizar acumulado en el último pago de la venta
                            if ($esUltimo) {
                                $totalSaldoSuma += $saldoDespues;
                            }
                            $saldoAcum -= $monto;

                            $provKey =
                                $p->provisional_id ??
                                ($p->provisionalId ??
                                    trim((string) ($p->numero_recibo ?? '')) .
                                        '|' .
                                        (string) ($p->fecha_provisional ?? ''));

                            $mostrarProv = false;
                            $provTotal = 0.0;
                            $provFechaFmt = $fechaProvFmt;

                            if (!isset($provUsado[$provKey])) {
                                $provUsado[$provKey] = true;
                                $mostrarProv = true;

                                $provTotal = (float) ($provTotales[$provKey]['total'] ?? $monto);
                                $provFecha = $provTotales[$provKey]['fecha'] ?? ($p->fecha_provisional ?? null);
                                $provFechaFmt = !empty($provFecha)
                                    ? \Carbon\Carbon::parse($provFecha)->format('d/m/Y')
                                    : $fechaProvFmt;
                            }
                        @endphp

                        <tr class="row-pago">
                            <td class="nowrap">{{ $esPrimero ? $docVenta : '' }}</td>
                            <td>{{ $esPrimero ? $refVenta : '' }}</td>
                            <td class="center">{{ $esPrimero ? $fechaVentaFmt : '' }}</td>
                            <td class="num">{{ $esPrimero ? number_format($impVenta, 2) : '' }}</td>

                            <td class="num">{{ number_format($monto, 2) }}</td>
                            <td class="num">{{ number_format($saldoDespues, 2) }}</td>
                            <td class="nowrap">{{ $nRecibo }}</td>

                            <!-- ✅ Acumulado en 2 celdas -->
                            <td class="num">
                                @if ($mostrarProv && $provTotal > 0)
                                    <span
                                        style="color:#b30000; font-weight:700;">{{ number_format($provTotal, 2) }}</span>
                                @endif
                            </td>
                            <td class="num">
                                @if ($mostrarProv && $provTotal > 0)
                                    <span style="color:#b30000; font-weight:700;">{{ $provFechaFmt }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif

            @empty
                <tr>
                    <td colspan="9" class="text-right muted">Sin movimientos en el rango.</td>
                </tr>
            @endforelse

            {{-- ✅ Fila TOTAL al final de la columna Saldo --}}
            <tr style="border-top: 2px solid #333;">
                <td colspan="5" style="text-align:right; font-weight:700; padding:4px;">TOTAL SALDO:</td>
                <td class="num" style="font-weight:700; padding:4px; border-top:2px solid #333;">
                    {{ number_format($saldoFinal, 2) }}</td>
                <td colspan="3"></td>
            </tr>

        </tbody>
    </table>

    {{-- CUADRO DE ABONOS SUELTOS --}}
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
            @forelse($abonosSueltos ?? [] as $p)
                @php
                    $docPago =
                        !empty($p->serie) && !empty($p->correlativo)
                            ? trim(
                                ($p->comprobante_tipo_codigo ? $p->comprobante_tipo_codigo . ' ' : '') .
                                    $p->serie .
                                    '-' .
                                    $p->correlativo,
                            )
                            : 'REC ' . ($p->numero_recibo ?? '');
                @endphp

                <tr class="row-abono">
                    <td class="nowrap">{{ \Carbon\Carbon::parse($p->fecha_provisional)->format('d/m/Y') }}</td>
                    <td>{{ $docPago }}</td>
                    <td>
                        ABONO
                        @if (!empty($p->comentario))
                            <div class="muted">{{ $p->comentario }}</div>
                        @endif
                    </td>
                    <td class="num">{{ number_format((float) ($p->monto ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center muted">Sin abonos sueltos en el rango.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>
