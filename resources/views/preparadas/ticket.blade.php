<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>PREPARADA {{ $preparada->id }}</title>
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
            <p>PREPARADA: {{ $preparada->id }}</p>
        </div>

        <div class="line"></div>

        {{-- FECHA Y HORA --}}
        <table style="width: 100%;">
            <tr>
                <td></td>
                <td class="right">FECHA: {{ \Carbon\Carbon::parse($preparada->fecha)->format('d/m/Y') }}</td>
                <td class="right">HORA: {{ \Carbon\Carbon::parse($preparada->fecha)->format('H:i:s') }}</td>
            </tr>
        </table>

        <div class="spacer"></div>

        {{-- DATOS CLIENTE --}}
        <p><strong>CLIENTE:</strong> {{ $preparada->cliente_nombre }}</p>
        <p><strong>DIRECCION:</strong> {{ $preparada->cliente_direccion ?? '-' }}</p>

        <div class="spacer"></div>

        <p><strong>PRODUCTO:</strong> {{ $preparada->producto_nombre }} ({{ $preparada->producto_empaque }}KG)</p>

        <div class="line"></div>
        <div class="spacer"></div>

        {{-- ENCABEZADO TABLA --}}
        <div class="bold">
            <span style="display: inline-block; width: 30%;">Cant</span>
            <span style="display: inline-block; width: 25%; text-align: right;">P.Unit</span>
            <span style="display: inline-block; width: 35%; text-align: right;">Importe</span>
        </div>
        <div class="line"></div>

        {{-- DETALLE INSUMOS --}}
        @foreach ($preparada->detalles as $detalle)
            <p class="bold">{{ $detalle->producto_nombre }}</p>

            @php
                $cantidad = number_format((float) $detalle->salida_kg, 2);
                $precio = number_format((float) $detalle->precio_unitario, 2);
                $importe = number_format((float) ($detalle->salida_kg * $detalle->precio_unitario), 2);
            @endphp

            <p>
                <span style="display: inline-block; width: 30%;">{{ $cantidad }} KG</span>
                <span style="display: inline-block; width: 25%; text-align: right;">S/ {{ $precio }}</span>
                <span style="display: inline-block; width: 35%; text-align: right;">S/ {{ $importe }}</span>
            </p>
        @endforeach

        <div class="line"></div>
        <div class="spacer"></div>

        {{-- TOTALES --}}
        <p style="text-align: right;">
            SACOS: {{ number_format($preparada->ingreso_saco, 2) }}
        </p>
        <p style="text-align: right;">
            KG TOTALES: {{ number_format($preparada->ingreso_kg, 2) }}
        </p>

        <div class="spacer"></div>

        <p class="bold" style="text-align: right; font-size: 11px;">
            TOTAL: S/ {{ number_format($preparada->ingreso_soles, 2) }}
        </p>

        <div class="line"></div>
        <div class="spacer"></div>

        {{-- USUARIO --}}
        <p><strong>USUARIO:</strong> {{ $preparada->user_nombre }}</p>

        <div class="line"></div>

        <div class="center">
            <p class="bold">GRACIAS POR SU PREFERENCIA</p>
        </div>

        <div style="height: 4mm;"></div>

    </div>
</body>

</html>