<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $entrega->numero_recibo }} - venta {{ $entrega->comprobante_tipo_codigo }} {{ $entrega->serie }}-{{ $entrega->correlativo }}</title>
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
        <h3 class="bold">ENTREGA {{ $entrega->numero_recibo}} ({{ $entrega->comprobante_tipo_nombre}} {{ $entrega->serie }}-{{ str_pad($entrega->correlativo,8,'0',STR_PAD_LEFT) }})</h3>
    </div>
    <div class="line"></div>
    {{-- DATOS Cliente --}}
    <p><strong>Cliente:</strong> {{ $entrega->cliente_nombre }}</p>
    <p><strong>Documento:</strong> {{ $entrega->cliente->documentoTipo->descripcion }} {{ $entrega->cliente_documento }}</p>
    <p><strong>Dirección:</strong> {{ $entrega->cliente_direccion ?? '-' }}</p>
    <p><strong>Fecha Venta:</strong> {{ \Carbon\Carbon::parse($entrega->fecha_venta)->format('d/m/Y H:i') }}</p>
    <div class="line"></div>
    <br>
    {{-- DETALLE PRODUCTOS --}}
    <table>
        <tr class="bold">            
            <td style="width: 38%;">Descripción</td>
            <td style="width: 12%; text-align: center;">Cant. Entregada</td>
            <td style="width: 25%; text-align: right; padding-right: 3px;">Cant. Comprado</td>
            <td style="width: 25%; text-align: right;">Saldo</td>
        </tr>
        <tr><td colspan="4" style="border-top: 1px solid #000;"></td></tr>
        @foreach($entrega->detalles as $detalle)
        <tr>
            <td>
                {{ $detalle->ventaDetalle->producto_nombre ?? '' }}
                {{ $detalle->ventaDetalle->unidad_nombre ?? '' }}
            </td>
            <td style="text-align: center;">{{ number_format((float)$detalle->cantidad, 2) }}</td>
            <td style="text-align: right; padding-right: 3px;">
                {{ number_format((float)($detalle->ventaDetalle->cantidad ?? 0), 2) }}
            </td>
            <td style="text-align: right;">
                {{ number_format((float)($detalle->ventaDetalle->saldo ?? 0), 2) }}
            </td>
        </tr>
        @endforeach
        <tr><td colspan="4" style="border-top: 1px solid #000;"></td></tr>
    </table>
    
    <div class="line"></div>
    <div class="center">
        <p><em>GRACIAS POR SU PREFERENCIA</em></p>
    </div>
</div>
</body>
</html>