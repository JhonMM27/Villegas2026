<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>COMPRA {{ $compra->serie }}-{{ str_pad($compra->correlativo, 8, '0', STR_PAD_LEFT) }}</title>
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
            <p>COMPRA {{ $compra->serie }}-{{ str_pad($compra->correlativo, 8, '0', STR_PAD_LEFT) }}</p>
        </div>

        <div class="line"></div>

        {{-- FECHA Y HORA --}}
        <table style="width: 100%;">
            <tr>
                <td></td>
                <td class="right">FECHA: {{ \Carbon\Carbon::parse($compra->fecha_compra)->format('d/m/Y') }}</td>
                <td class="right">HORA: {{ \Carbon\Carbon::parse($compra->fecha_compra)->format('H:i:s') }}</td>
            </tr>
        </table>

        <div class="spacer"></div>

        {{-- DATOS PROVEEDOR --}}
        <p><strong>RUC/DNI:</strong> {{ $compra->proveedor_documento ?? '-' }}</p>
        <p><strong>PROVEEDOR:</strong> {{ $compra->proveedor_nombre }}</p>
        <p><strong>DIRECCION:</strong> {{ $compra->proveedor_direccion ?? '-' }}</p>

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
        @foreach ($compra->detalles as $detalle)
            @php
                // Determinar unidad y cantidad según unidad_codigo
                $unidadCodigo = $detalle->unidad_codigo ?? 'NIU';
                
                if ($unidadCodigo === 'KGM') {
                    $cantidad = number_format((float) $detalle->cantidad_kgm, 2);
                    $unidadTexto = 'KG';
                } elseif ($unidadCodigo === 'SCO' || $unidadCodigo === 'NIU') {
                    // SCO y NIU son productos empacados (aSACOs)
                    $cantidad = number_format((float) $detalle->cantidad, 2);
                    $unidadTexto = 'SACO';
                } else {
                    $cantidad = number_format((float) $detalle->cantidad, 2);
                    $unidadTexto = 'SACO';
                }

                $precio = number_format((float) $detalle->costo_unitario, 2);
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

        {{-- TOTALES --}}
        <p style="text-align: right;">
            OP. GRAVADAS: S/ {{ number_format($compra->op_gravada, 2) }}
        </p>
        <p style="text-align: right;">
            OP. EXONERADAS: S/ {{ number_format($compra->op_exonerada, 2) }}
        </p>
        <p style="text-align: right;">
            IMPUESTO: S/ {{ number_format($compra->impuesto, 2) }}
        </p>

        <div class="spacer"></div>

        <p class="bold" style="text-align: right; font-size: 11px;">
            TOTAL: S/ {{ number_format($compra->total, 2) }}
        </p>

        <div class="line"></div>
        <div class="spacer"></div>

        {{-- COMPRADOR --}}
        <p><strong>USUARIO:</strong> {{ $compra->user_nombre }}</p>

        <div class="line"></div>

        <div class="center">
            <p class="bold">GRACIAS POR SU PREFERENCIA</p>
        </div>

        <div style="height: 4mm;"></div>

    </div>
</body>

</html>