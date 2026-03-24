<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $venta->comprobante_tipo_nombre }} - venta {{ $venta->serie }}-{{ $venta->correlativo }}</title>
    <style>
        /* Margen lateral mayor para que no se corte */
        @page { margin: 6mm 6mm 6mm 6mm; size: 80mm auto; }

        /* Aumentamos tamaño general */
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 11.5px; 
            margin: 0; 
            padding: 0; 
        }

        /* Reducimos un poco el ancho para evitar que toque bordes */
        .ticket { 
            width: 70mm; 
            margin: 0 auto; 
            padding: 0; 
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }

        h3 { 
            margin: 0 0 3px 0; 
            font-size: 13px; 
        }

        p { margin: 2px 0; }

        table { 
            width: 100%; 
            border-collapse: collapse; 
        }

        td { 
            padding: 2px 0; 
            vertical-align: top; 
        }

        .line { 
            border-top: 1px dashed #000; 
            margin: 4px 0; 
        }

        .totales td { padding: 2px 0; }
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
        <br>
        <h3 class="bold">
            {{ $venta->comprobante_tipo_nombre}} 
            {{ $venta->serie }}-{{ str_pad($venta->correlativo,8,'0',STR_PAD_LEFT) }}
        </h3>
    </div>

    <div class="line"></div>

    {{-- DATOS CLIENTE --}}
    <p><strong>Cliente:</strong> {{ $venta->cliente_nombre }}</p>
    <p><strong>Documento:</strong> {{ $venta->cliente->documentoTipo->descripcion }} {{ $venta->cliente->documento_numero }}</p>
    <p><strong>Dirección:</strong> {{ $venta->cliente->direccion ?? '-' }}</p>
    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y H:i') }}</p>
    <p><strong>Forma de Pago:</strong> {{ $venta->pago_forma_nombre ?? '-' }}</p>

    <div class="line"></div>
    <br>

    {{-- DETALLE PRODUCTOS --}}
    <table>
        <tr class="bold">
            <td style="width: 12%; text-align: center;">Cant.</td>
            <td style="width: 38%;">Descripción</td>
            <td style="width: 25%; text-align: right; padding-right: 5px;">Precio</td>
            <td style="width: 25%; text-align: right;">Importe</td>
        </tr>

        <tr><td colspan="4" style="border-top: 1px solid #000;"></td></tr>

        @foreach($venta->detalles as $detalle)
        <tr>
            <td style="text-align: center;">{{ $detalle->cantidad }}</td>
            <td>{{ $detalle->producto_nombre }} {{ $detalle->unidad_codigo }}</td>
            <td style="text-align: right; padding-right: 5px;">
                {{ number_format($detalle->precio_unitario,2) }}
            </td>
            <td style="text-align: right;">
                {{ number_format($detalle->total,2) }}
            </td>
        </tr>
        @endforeach

        <tr><td colspan="4" style="border-top: 1px solid #000;"></td></tr>
    </table>

    {{-- TOTALES --}}
    <table class="totales">
        <tr class="bold">
            <td style="text-align: right;">TOTAL:</td>
            <td style="text-align: right;">
                S/ {{ number_format($venta->total,2) }}
            </td>
        </tr>
    </table>

    {{ $total_letras }}

    <div class="line"></div>
    <br>

    <p><strong>Vendedor:</strong> {{ $venta->user_nombre }}</p>

    <p><strong>COBRANZA</strong></p>

    <table>
        <tr>
            <td class="bold">Principal:</td>
            <td>S/ {{ number_format($venta->importe_p,2) }}</td>
        </tr>
        <tr>
            <td class="bold">Depósito:</td>
            <td>S/ {{ number_format($venta->importe_d,2) }}</td>
        </tr>
        <tr>
            <td class="bold">Consorcio:</td>
            <td>S/ {{ number_format($venta->importe_c,2) }}</td>
        </tr>
        <tr style="text-decoration: underline;">
            <td class="bold">Saldo:</td>
            <td>S/ {{ number_format($venta->saldo,2) }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="center">
        <p><em>GRACIAS POR SU PREFERENCIA</em></p>
    </div>

</div>
</body>
</html>