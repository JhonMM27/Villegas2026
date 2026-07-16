<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>
        NP - venta {{ $venta->serie }}-{{ str_pad($venta->correlativo, 8, '0', STR_PAD_LEFT) }}
    </title>

    <style>
        @page {
            margin: 0;
            size: 302px auto;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 302px;
            margin: 0;
            padding: 0;
            color: #000;
        }

        body {
            font-family: FontA11, Font11, Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.22;
            font-weight: normal;
        }

        .ticket {
            width: 302px;
            margin: 0;
            padding-left: 26px;
            padding-right: 31px;
            text-align: left;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold,
        strong,
        .data-label {
            font-weight: bold;
        }

        h3 {
            width: 245px;
            margin: 0;
            padding: 0;
            font-size: 15px;
            line-height: 1.15;
            font-weight: bold;
            text-align: center;
        }

        p {
            width: 245px;
            margin: 0;
            padding: 0;
            font-size: 13px;
            line-height: 1.22;
            font-weight: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table {
            width: 245px;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
            padding: 0;
        }

        td,
        th {
            padding: 0;
            margin: 0;
            font-size: 13px;
            line-height: 1.22;
            font-weight: normal;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        th {
            font-weight: bold;
        }

        .line {
            width: 245px;
            border-top: 1px dashed #000;
            height: 0;
            margin: 4px 0;
        }

        .line-thin {
            width: 245px;
            border-top: 1px dotted #888;
            height: 0;
            margin: 4px 0;
        }

        .spacer {
            height: 5px;
        }

        .fecha-col {
            width: 122px;
            text-align: left;
        }

        .hora-col {
            width: 123px;
            text-align: right;
        }

        .detail-table {
            width: 245px;
        }

        .col-cant {
            width: 78px;
            text-align: left;
        }

        .col-price {
            width: 75px;
            text-align: right;
        }

        .col-total {
            width: 92px;
            text-align: right;
        }

        .product-name {
            width: 245px;
            font-size: 14px;
            line-height: 1.20;
            font-weight: bold;
            text-transform: uppercase;
            padding-top: 5px;
        }

        .total {
            width: 245px;
            text-align: right;
            font-size: 19px;
            line-height: 1.20;
            font-weight: bold;
        }

        .small {
            width: 245px;
            font-size: 12px;
            line-height: 1.20;
        }

        .cobranza p {
            text-align: center;
            font-size: 13px;
            line-height: 1.25;
        }

        .saldo {
            width: 245px;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
        }

        .footer-space {
            height: 25px;
        }
    </style>
</head>

<body>
    <div class="ticket">

        @php
            $numeroVenta = $venta->serie . '-' . str_pad($venta->correlativo, 8, '0', STR_PAD_LEFT);
            $fechaVenta = \Carbon\Carbon::parse($venta->fecha_venta);
        @endphp

        {{-- ENCABEZADO EMPRESA --}}
        <div class="center">
            <h3>CONSORCIOS VILLEGAS E.I.R.L.</h3>
            <p>CEL: 967984895 / 978431737</p>
            <p>NOTA PEDIDO NP: {{ $numeroVenta }}</p>
        </div>

        <div class="line"></div>

        {{-- FECHA Y HORA --}}
        <table>
            <tr>
                <td class="fecha-col">
                    FECHA: {{ $fechaVenta->format('d/m/Y') }}
                </td>
                <td class="hora-col">
                    HORA: {{ $fechaVenta->format('H:i:s') }}
                </td>
            </tr>
        </table>

        <div class="spacer"></div>

        {{-- DATOS CLIENTE --}}
        <p>
            <span class="data-label">RUC/DNI:</span>
            {{ $venta->cliente->documento_numero ?? '-' }}
        </p>

        <p>
            <span class="data-label">CLIENTE:</span>
            {{ $venta->cliente_nombre ?? '-' }}
        </p>

        <p>
            <span class="data-label">DIRECCION:</span>
            {{ $venta->cliente->direccion ?? '-' }}
        </p>

        <p>
            <span class="data-label">TELEFONO:</span>
            {{ $venta->cliente->telefono ?? '-' }}
        </p>

        <div class="spacer"></div>

        <p>
            <span class="data-label">FORMA DE PAGO:</span>
            {{ $venta->pago_forma_nombre ?? '-' }}
        </p>

        <div class="line"></div>

        {{-- CABECERA DETALLE --}}
        <table class="detail-table">
            <thead>
                <tr>
                    <th class="col-cant">Cant</th>
                    <th class="col-price">P.Unit</th>
                    <th class="col-total">Importe</th>
                </tr>
            </thead>
        </table>

        <div class="line"></div>

        {{-- DETALLE PRODUCTOS --}}
        @foreach ($venta->detalles as $detalle)
            @php
                $unidadCodigo = $detalle->unidad_codigo ?? 'NIU';

                if ($unidadCodigo === 'KGM') {
                    $cantidad = number_format((float) ($detalle->salida_kg ?? 0), 2, '.', ',');
                    $unidadTexto = 'KG';
                } elseif ($unidadCodigo === 'SCO') {
                    $cantidad = number_format((float) ($detalle->salida_saco ?? 0), 2, '.', ',');
                    $unidadTexto = 'SACO';
                } elseif ($unidadCodigo === 'ZZ') {
                    $cantidad = number_format((float) ($detalle->salida_saco ?? 0), 2, '.', ',');
                    $unidadTexto = '';
                } else {
                    $cantidad = number_format((float) ($detalle->salida_saco ?? 0), 2, '.', ',');
                    $unidadTexto = $unidadCodigo;
                }

                $precio = number_format((float) ($detalle->precio_unitario ?? 0), 2, '.', ',');
                $importe = number_format((float) ($detalle->total ?? 0), 2, '.', ',');
            @endphp

            <p class="product-name">
                {{ $detalle->producto_nombre ?? 'PRODUCTO SIN NOMBRE' }}
            </p>

            <table class="detail-table">
                <tr>
                    <td class="col-cant">
                        {{ $cantidad }} {{ $unidadTexto }}
                    </td>
                    <td class="col-price">
                        S/ {{ $precio }}
                    </td>
                    <td class="col-total">
                        S/ {{ $importe }}
                    </td>
                </tr>
            </table>
            @if (!$loop->last)
                <div class="line-thin"></div>
            @endif
        @endforeach

        <div class="line"></div>
        <div class="spacer"></div>

        {{-- TOTAL --}}
        <p class="total">
            TOTAL: S/ {{ number_format((float) ($venta->total ?? 0), 2, '.', ',') }}
        </p>

        <p class="center small">
            Son: {{ $total_letras ?? '' }}
        </p>

        <div class="line"></div>
        <div class="spacer"></div>

        {{-- USUARIO --}}
        <p>
            <span class="data-label">USUARIO:</span>
            {{ $venta->user_nombre ?? '-' }}
        </p>

        <div class="line"></div>

        {{-- PIE --}}
        <div class="center">
            <p class="bold">GRACIAS POR SU PREFERENCIA</p>
        </div>

        <div class="spacer"></div>

        {{-- COBRANZA --}}
        <div class="cobranza">
            <p class="bold center">COBRANZA</p>
            <table>
                <tr>
                    <td class="bold">PRINCIPAL:</td>
                    <td class="right">S/ {{ number_format((float)($venta->importe_p ?? 0), 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td class="bold">DEPOSITO:</td>
                    <td class="right">S/ {{ number_format((float)($venta->importe_d ?? 0), 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td class="bold">CONSORCIO:</td>
                    <td class="right">S/ {{ number_format((float)($venta->importe_c ?? 0), 2, '.', ',') }}</td>
                </tr>
            </table>
        </div>

        <div class="spacer"></div>

        {{-- SALDO ANTERIOR --}}
        @if (($saldoAnterior ?? 0) > 0)
            <div class="center">
                <p class="saldo">
                    SALDO ANTERIOR: S/ {{ number_format((float) $saldoAnterior, 2, '.', ',') }}
                </p>
            </div>
        @endif

        <div class="footer-space"></div>

    </div>
</body>

</html>