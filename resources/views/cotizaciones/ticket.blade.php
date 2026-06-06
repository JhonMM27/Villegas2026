<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $cotizacion->comprobante_tipo_nombre }} - venta {{ $cotizacion->serie }}-{{ $cotizacion->correlativo }}</title>
    <style>
        @page { margin: 4mm; size: 80mm auto; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; margin: 0; padding: 0; }
        .ticket { width: 72mm; margin: 0; padding: 0; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        h3 { margin: 0 0 2px 0; }
        p { margin: 1px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; vertical-align: top; }
        .line { border-top: 1px dashed #000; margin: 3px 0; }
        .totales td { padding: 1px 0; }
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
        <h3 class="bold">Cotización {{ $cotizacion->serie }}-{{ str_pad($cotizacion->correlativo,8,'0',STR_PAD_LEFT) }}</h3>
    </div>
    <div class="line"></div>
    {{-- DATOS PROVEEDOR --}}
    <p><strong>Cliente:</strong> {{ $cotizacion->cliente_nombre }}</p>
    <p><strong>Documento:</strong> {{ $cotizacion->cliente->documentoTipo->descripcion }} {{ $cotizacion->cliente->documento_numero }}</p>
    <p><strong>Dirección:</strong> {{ $cotizacion->cliente->direccion ?? '-' }}</p>
    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($cotizacion->fecha_cotizacion)->format('d/m/Y H:i') }}</p>
    <div class="line"></div>
    {{-- DETALLE PRODUCTOS --}}
    <table>
        <tr class="bold">
            <td style="width: 12%; text-align: center;">Cant.</td>
            <td style="width: 38%;">Descripción</td>
            <td style="width: 25%; text-align: right; padding-right: 3px;">Precio</td>
            <td style="width: 25%; text-align: right;">Importe</td>
        </tr>
        <tr><td colspan="4" style="border-top: 1px solid #000;"></td></tr>
        @foreach($cotizacion->detalles as $detalle)
        <tr>
            <td style="text-align: center;">{{ $detalle->cantidad }} {{ $detalle->unidad_codigo }}</td>
            <td>{{ $detalle->producto_nombre }}</td>            
            <td style="text-align: right; padding-right: 3px;">{{ number_format($detalle->precio_unitario,2) }}</td>
            <td style="text-align: right;">{{ number_format($detalle->total,2) }}</td>
        </tr>
        @endforeach
        <tr><td colspan="4" style="border-top: 1px solid #000;"></td></tr>
    </table>
    {{-- TOTALES --}}
    <table class="totales">
        <tr class="bold">
            <td>TOTAL:</td>
            <td style="text-align: right;">S/ {{ number_format($cotizacion->total,2) }}</td>
        </tr>
    </table>
    {{ $total_letras }}
    <div class="line"></div>
    <br>
    <p><strong>Usuario: </strong>{{ $cotizacion->user_nombre }}</p>
</div>
</body>
</html>