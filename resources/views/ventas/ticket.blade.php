<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>NP - venta {{ $venta->serie }}-{{ str_pad($venta->correlativo, 8, '0', STR_PAD_LEFT) }}</title>
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
            <p>NOTA PEDIDO NP: {{ $venta->serie }}-{{ str_pad($venta->correlativo, 8, '0', STR_PAD_LEFT) }}</p>
        </div>

        <div class="line"></div>

        {{-- FECHA Y HORA --}}
        <table style="width: 100%;">
            <tr>
                <td></td>
                <td class="right">FECHA: {{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }}</td>
                <td class="right">HORA: {{ \Carbon\Carbon::parse($venta->fecha_venta)->format('H:i:s') }}</td>
            </tr>
        </table>

        <div class="spacer"></div>

        {{-- DATOS CLIENTE --}}
        <p><strong>RUC/DNI:</strong> {{ $venta->cliente->documento_numero ?? '-' }}</p>
        <p><strong>CLIENTE:</strong> {{ $venta->cliente_nombre }}</p>
        <p><strong>DIRECCION:</strong> {{ $venta->cliente->direccion ?? '-' }}</p>
        <p><strong>TELEFONO:</strong> {{ $venta->cliente->telefono ?? '-' }}</p>

        <div class="spacer"></div>

        <p><strong>FORMA DE PAGO:</strong> {{ $venta->pago_forma_nombre ?? '-' }}</p>

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
        @foreach ($venta->detalles as $detalle)
            @php
                // Determinar unidad y cantidad según unidad_codigo
                $unidadCodigo = $detalle->unidad_codigo ?? 'NIU';
                
                // Para KGM usar salida_kg, para SCO usar salida_saco
                // ZZ (servicios) no muestra unidad
                if ($unidadCodigo === 'KGM') {
                    $cantidad = number_format((float) $detalle->salida_kg, 2);
                    $unidadTexto = 'KG';
                } elseif ($unidadCodigo === 'SCO') {
                    $cantidad = number_format((float) $detalle->salida_saco, 2);
                    $unidadTexto = 'SACO';
                } elseif ($unidadCodigo === 'ZZ') {
                    // Servicio - usar salida_saco sin unidad
                    $cantidad = number_format((float) $detalle->salida_saco, 2);
                    $unidadTexto = '';
                } else {
                    $cantidad = number_format((float) $detalle->salida_saco, 2);
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
            TOTAL: S/ {{ number_format($venta->total, 2) }}
        </p>

        <p class="center" style="font-size: 8px;">Son: {{ $total_letras }}</p>

        <div class="line"></div>
        <div class="spacer"></div>

        {{-- VENDEDOR --}}
        <p><strong>USUARIO:</strong> {{ $venta->user_nombre }}</p>

        <div class="line"></div>

        <div class="center">
            <p class="bold">GRACIAS POR SU PREFERENCIA</p>
        </div>

        <div class="spacer"></div>

        {{-- COBRANZA --}}
        <div class="center">
            <p class="bold">COBRANZA</p>
            <p>PRINCIPAL</p>
            <p>DEPOSITO</p>
            <p>CONSORCIO</p>
        </div>

        <div style="height: 4mm;"></div>

    </div>
</body>

</html>