<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Saldos Cliente: {{ $clienteNombre }}</title>
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
        <h3 class="bold">CRÉDITOS CON SALDOS</h3>
    </div>
    <div class="line"></div>
    {{-- DATOS PROVEEDOR --}}
    <p><strong>Cliente:</strong> {{ $clienteNombre }}</p>
    <div class="line"></div>
    <br>
    {{-- DETALLE PRODUCTOS --}}
    <table>
        <tr class="bold">
            <td style="width: 40%; text-align: center;">Doc.</td>
            <td style="width: 25%;">Fecha</td>
            <td style="width: 35%; text-align: right; padding-right: 3px;">Saldo</td>
        </tr>
        <tr><td colspan="4" style="border-top: 1px solid #000;"></td></tr>
        @php
            $total=0;
            $totalSaldo=0.0;
        @endphp
        @foreach($reportes as $r)
            @php
                $total += 1;
                $totalSaldo += (float)$r->saldo;
            @endphp
        <tr>
            <td style="text-align: center;">{{ $r->documento }}</td>
            <td>{{ \Carbon\Carbon::parse($r->fecha_venta)->format('d/m/Y') }}</</td>
            <td style="text-align: right; padding-right: 3px;">{{ number_format((float)$r->saldo, 2) }}</td>
        </tr>
        @endforeach
        <tr><td colspan="4" style="border-top: 1px solid #000;"></td></tr>
    </table>
    {{-- TOTALES --}}
    <table class="totales">
        <tr class="bold">
            <td style="text-align: right;">TOTAL:</td>
            <td style="text-align: right;">{{ number_format($total,2) }}</td>
            <td style="text-align: right;">{{ number_format($totalSaldo,2) }}</td>
        </tr>
    </table>
    <div class="line"></div>
    <div class="center">
        <p><em>GRACIAS POR SU PREFERENCIA</em></p>
    </div>
</div>
</body>
</html>