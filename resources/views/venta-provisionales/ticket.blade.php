<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Provisional {{ $provisional->numero_recibo }}</title>
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
        table { width: 100%; border-collapse: collapse; margin: 0; }
        td { padding: 1px 0; vertical-align: top; }
        .line { border-top: 1px dashed #000; margin: 2px 0; }
        .spacer { height: 3px; }
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

    <div class="spacer"></div>

    <div class="center">
        <h3 class="bold">RECIBO {{ $provisional->numero_recibo }}</h3>
    </div>

    <div class="line"></div>

    {{-- DATOS CLIENTE --}}
    <p><strong>Cliente:</strong> {{ $provisional->cliente_nombre }}</p>
    <p><strong>Documento:</strong> {{ $provisional->cliente->documentoTipo->descripcion ?? '-' }} {{ $provisional->cliente_documento }}</p>
    <p><strong>Dirección:</strong> {{ $provisional->cliente_direccion ?? '-' }}</p>
    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($provisional->fecha_provisional)->format('d/m/Y H:i') }}</p>

    <div class="line"></div>
    <div class="spacer"></div>

    {{-- TOTALES --}}
    <table class="totales">
        <tr class="bold">
            <td style="text-align: left; width: 70%;">PAGO REALIZADO:</td>
            <td style="text-align: right; width: 30%;">S/ {{ number_format($provisional->monto, 2) }}</td>
        </tr>
    </table>

    <p style="font-size: 8px; text-align: center;">{{ $total_letras }}</p>

    <table class="totales">
        <tr class="bold">
            <td style="text-align: left; width: 70%;">TOTAL DEUDA DEL CLIENTE:</td>
            <td style="text-align: right; width: 30%;">S/ {{ number_format($totalDeuda, 2) }}</td>
        </tr>
    </table>

    <div class="line"></div>
    <div class="spacer"></div>

    <p><strong>Vendedor:</strong> {{ $provisional->user_nombre }}</p>
    <p class="bold">COBRANZA</p>

    <table>
        <tr>
            <td class="bold">Principal:</td>
            <td class="right">S/ {{ number_format($provisional->importe_p, 2) }}</td>
        </tr>
        <tr>
            <td class="bold">Depósito:</td>
            <td class="right">S/ {{ number_format($provisional->importe_d, 2) }}</td>
        </tr>
        <tr>
            <td class="bold">Consorcio:</td>
            <td class="right">S/ {{ number_format($provisional->importe_c, 2) }}</td>
        </tr>
    </table>

    <div class="line"></div>
    <div class="center">
        <p><em>GRACIAS POR SU PREFERENCIA</em></p>
    </div>

    <div style="height: 4mm;"></div>
</div>
</body>
</html>