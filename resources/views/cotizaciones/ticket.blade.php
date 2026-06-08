<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>COTIZACIÓN {{ $cotizacion->serie }}-{{ str_pad($cotizacion->correlativo, 8, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            margin: 2mm 0mm 2mm 0mm;
            size: 76mm auto;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }

        .ticket {
            width: 72mm;
            margin: 0 auto;
            text-align: left;
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }
        .right { text-align: right; }

        h3 { margin: 0; padding: 0; font-size: 12px; }
        p { margin: 0; padding: 0; font-size: 9px; }

        .line {
            border-top: 1px dashed #000;
            margin: 2px 0;
        }

        .spacer { height: 3px; }
    </style>
</head>

<body>
    <div class="ticket">

        {{-- ENCABEZADO EMPRESA --}}
        <div class="center">
            <h3>CONSORCIOS VILLEGAS E.I.R.L.</h3>
        </div>

        <div class="center">
            <p>CEL: 967984895 / 978431737</p>
            <p>COTIZACIÓN: {{ $cotizacion->serie }}-{{ str_pad($cotizacion->correlativo, 8, '0', STR_PAD_LEFT) }}</p>
        </div>

        <div class="line"></div>

        {{-- FECHA Y HORA --}}
        <table style="width: 100%;">
            <tr>
                <td></td>
                <td class="right">FECHA: {{ \Carbon\Carbon::parse($cotizacion->fecha_cotizacion)->format('d/m/Y') }}</td>
                <td class="right">HORA: {{ \Carbon\Carbon::parse($cotizacion->fecha_cotizacion)->format('H:i:s') }}</td>
            </tr>
        </table>

        <div class="spacer"></div>

        {{-- DATOS CLIENTE --}}
        <p><strong>RUC/DNI:</strong> {{ $cotizacion->cliente->documento_numero ?? '-' }}</p>
        <p><strong>CLIENTE:</strong> {{ $cotizacion->cliente_nombre }}</p>
        <p><strong>DIRECCION:</strong> {{ $cotizacion->cliente->direccion ?? '-' }}</p>
        <p><strong>TELEFONO:</strong> {{ $cotizacion->cliente->telefono ?? '-' }}</p>

        <div class="spacer"></div>

        <p><strong>FORMA DE PAGO:</strong> {{ $cotizacion->pago_forma_nombre ?? '-' }}</p>

        <div class="line"></div>
        <div class="spacer"></div>

        {{-- ENCABEZADO TABLA --}}
        <div class="bold">
            <span style="display: inline-block; width: 30%;">Cant</span>
            <span style="display: inline-block; width: 25%; text-align: right;">P.Unit</span>
            <span style="display: inline-block; width: 35%; text-align: right;">Importe</span>
        </div>
        <div class="line"></div>

        {{-- DETALLE PRODUCTOS --}}
        @foreach ($cotizacion->detalles as $detalle)
            @php
                $unidadCodigo = $detalle->unidad_codigo ?? 'NIU';
                $empaque = (float) ($detalle->producto_empaque ?? 1);

                if ($unidadCodigo === 'KGM') {
                    $cantidad = number_format((float) $detalle->cantidad * $empaque, 2);
                    $unidadTexto = 'KG';
                } elseif ($unidadCodigo === 'SCO') {
                    $cantidad = number_format((float) $detalle->cantidad, 2);
                    $unidadTexto = 'SACO';
                } elseif ($unidadCodigo === 'ZZ') {
                    $cantidad = number_format((float) $detalle->cantidad, 2);
                    $unidadTexto = '';
                } else {
                    $cantidad = number_format((float) $detalle->cantidad, 2);
                    $unidadTexto = $unidadCodigo;
                }

                $precio = number_format((float) $detalle->precio_unitario, 2);
                $importe = number_format((float) $detalle->total, 2);
            @endphp

            <p class="bold">{{ $detalle->producto_nombre }}</p>
            <p>
                <span style="display: inline-block; width: 30%;">{{ $cantidad }} {{ $unidadTexto }}</span>
                <span style="display: inline-block; width: 25%; text-align: right;">S/ {{ $precio }}</span>
                <span style="display: inline-block; width: 35%; text-align: right;">S/ {{ $importe }}</span>
            </p>
        @endforeach

        <div class="line"></div>
        <div class="spacer"></div>

        {{-- TOTAL --}}
        <p class="bold" style="text-align: right; font-size: 11px;">
            TOTAL: S/ {{ number_format($cotizacion->total, 2) }}
        </p>

        <p class="center" style="font-size: 8px;">Son: {{ $total_letras }}</p>

        <div class="line"></div>
        <div class="spacer"></div>

        {{-- VENDEDOR --}}
        <p><strong>USUARIO:</strong> {{ $cotizacion->user_nombre }}</p>

        <div class="line"></div>

        <div class="center">
            <p class="bold">GRACIAS POR SU PREFERENCIA</p>
        </div>

        <div class="spacer"></div>

        <div style="height: 4mm;"></div>

    </div>
</body>

</html>
