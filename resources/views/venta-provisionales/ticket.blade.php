<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Provisional {{ $provisional->numero_recibo }}</title>
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
        <h3 class="bold">RECIBO {{ $provisional->numero_recibo}}</h3>
    </div>
    <div class="line"></div>
    {{-- DATOS PROVEEDOR --}}
    <p><strong>Cliente:</strong> {{ $provisional->cliente_nombre }}</p>
    <p><strong>Documento:</strong> {{ $provisional->cliente->documentoTipo->descripcion }} {{ $provisional->cliente_documento }}</p>
    <p><strong>Dirección:</strong> {{ $provisional->cliente_direccion ?? '-' }}</p>
    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($provisional->fecha_provisional)->format('d/m/Y H:i') }}</p>
    <div class="line"></div>
    <br>
    {{-- RESUMEN CLARO --}}
    <table class="totales">
        <tr class="bold">
            <td style="text-align:left;">PAGO REALIZADO:</td>
            <td style="text-align:right;">S/ {{ number_format($provisional->monto, 2) }}</td>
        </tr>
        <tr>
            <td colspan="2">{{ $total_letras }}</td>
        </tr>
        <tr class="bold">
            <td style="text-align:left;">TOTAL DEUDA DEL CLIENTE:</td>
            <td style="text-align:right;">S/ {{ number_format($totalDeuda, 2) }}</td>
        </tr>
    </table>

    <div class="line"></div>
    <br>
    <p><strong>Vendedor: </strong>{{ $provisional->user_nombre }}</p>
    <p><strong>COBRANZA</p>
    <table>
        <tr>
            <td class="bold">Principal:</td>
            <td>S/ {{ number_format($provisional->importe_p,2) }}</td>
        </tr>
        <tr>
            <td class="bold">Depósito:</td>
            <td>S/ {{ number_format($provisional->importe_d,2) }}</td>
        </tr>
        <tr>
            <td class="bold">Consorcio:</td>
            <td>S/ {{ number_format($provisional->importe_c,2) }}</td>
        </tr>
    </table>
    <div class="line"></div>
    <div class="center">
        <p><em>GRACIAS POR SU PREFERENCIA</em></p>
    </div>
</div>
</body>
</html>