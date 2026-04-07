<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $venta->comprobante_tipo_nombre }} - venta {{ $venta->serie }}-{{ $venta->correlativo }}</title>
    <style>
        /* CORRECCIÓN 1: Márgenes mínimos (2mm arriba para evitar corte) */
        @page {
            margin: 2mm 0mm 2mm 0mm;
            size: 76mm auto;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            /* Ligeramente más pequeño para ahorrar espacio */
            margin: 0;
            padding: 0;
            line-height: 1.2;
            /* Interlineado ajustado */
        }

        /* CORRECCIÓN 2: Ancho real del papel */
        .ticket {
            width: 72mm;
            /* Margen de seguridad lateral mínimo */
            margin: 0 auto;
            text-align: left;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .right {
            text-align: right;
        }

        h3 {
            margin: 0;
            padding: 0;
            font-size: 12px;
        }

        /* CORRECCIÓN 3: Eliminar márgenes de párrafos */
        p {
            margin: 0;
            padding: 0;
            font-size: 9px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        td {
            padding: 1px 0;
            /* Reducido de 2px a 1px */
            vertical-align: top;
        }

        /* CORRECCIÓN 4: Líneas separadoras más delgadas y con menos margen */
        .line {
            border-top: 1px dashed #000;
            margin: 2px 0;
        }

        .spacer {
            height: 3px;
        }

        /* Espaciador controlado en vez de <br> */
    </style>
</head>

<body>
    <div class="ticket">

        {{-- ENCABEZADO EMPRESA --}}
        <div class="center">
            <h3>{{ $empresa->razon_social }}</h3>
            <p>{!! nl2br(e($empresa->direccion)) !!}</p>
            <p>RUC: {{ $empresa->ruc }}</p>
            <p>CEL: {{ $empresa->celular }}</p>
        </div>

        {{-- CORRECCIÓN 5: Eliminado <br> y usado clase spacer --}}
        <div class="spacer"></div>

        <div class="center">
            <h3 class="bold">
                {{ $venta->comprobante_tipo_nombre }}
                {{ $venta->serie }}-{{ str_pad($venta->correlativo, 8, '0', STR_PAD_LEFT) }}
            </h3>
        </div>

        <div class="line"></div>

        {{-- DATOS CLIENTE --}}
        <p><strong>Cliente:</strong> {{ $venta->cliente_nombre }}</p>
        <p><strong>Documento:</strong> {{ $venta->cliente->documentoTipo->descripcion }}
            {{ $venta->cliente->documento_numero }}</p>
        <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y H:i') }}</p>
        <p><strong>Pago:</strong> {{ $venta->pago_forma_nombre ?? '-' }}</p>

        <div class="line"></div>
        <div class="spacer"></div>

        {{-- DETALLE PRODUCTOS --}}
        <table>
            <tr class="bold">
                <td style="width: 15%;">Cant.</td>
                <td style="width: 45%;">Descripción</td>
                <td style="width: 20%; text-align: right;">P.Unit</td>
                <td style="width: 20%; text-align: right;">Total</td>
            </tr>
            <tr>
                <td colspan="4" style="border-top: 1px solid #000;"></td>
            </tr>

            @foreach ($venta->detalles as $detalle)
                <tr>
                    <td style="text-align: center;">{{ $detalle->cantidad }}</td>
                    <td>{{ $detalle->producto_nombre }}</td>
                    <td style="text-align: right;">{{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($detalle->total, 2) }}</td>
                </tr>
            @endforeach

            <tr>
                <td colspan="4" style="border-top: 1px solid #000;"></td>
            </tr>
        </table>

        {{-- TOTALES --}}
        <div class="spacer"></div>
        <table class="totales">
            <tr class="bold">
                <td style="text-align: right; width: 70%;">TOTAL:</td>
                <td style="text-align: right; width: 30%;">S/ {{ number_format($venta->total, 2) }}</td>
            </tr>
        </table>

        <p style="font-size: 8px; text-align: center;">{{ $total_letras }}</p>

        <div class="line"></div>
        <div class="spacer"></div>

        <p><strong>Vendedor:</strong> {{ $venta->user_nombre }}</p>
        <p class="bold">COBRANZA</p>

        <table>
            <tr>
                <td class="bold">Principal:</td>
                <td class="right">S/ {{ number_format($venta->importe_p, 2) }}</td>
            </tr>
            <tr>
                <td class="bold">Depósito:</td>
                <td class="right">S/ {{ number_format($venta->importe_d, 2) }}</td>
            </tr>
            <tr>
                <td class="bold">Consorcio:</td>
                <td class="right">S/ {{ number_format($venta->importe_c, 2) }}</td>
            </tr>
            <tr>
                <td class="bold" style="text-decoration: underline;">Saldo:</td>
                <td class="right" style="text-decoration: underline;">S/ {{ number_format($venta->saldo, 2) }}</td>
            </tr>
        </table>

        <div class="line"></div>
        <div class="center">
            <p><em>GRACIAS POR SU PREFERENCIA</em></p>
        </div>

        {{-- Espacio final para el corte de papel --}}
        <div style="height: 4mm;"></div>

    </div>
</body>

</html>
