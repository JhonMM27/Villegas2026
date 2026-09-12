<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Saldos Proveedor: {{ $proveedorNombre }}</title>
    @include('tickets.styles')
    @include('tickets.compact-styles')
</head>
<body>
<div class="ticket">
    {{-- ENCABEZADO EMPRESA --}}
    <div class="center">
        <h3>{{ $empresa->razon_social }}</h3>
        <p>{!! nl2br(e($empresa->direccion)) !!}</p>
        <p>RUC: {{ $empresa->ruc }}</p>
        <p>CEL: {{ $empresa->celular }}</p>

        <h3 class="bold">CRÉDITOS CON SALDOS</h3>
    </div>
    <div class="line"></div>
    {{-- DATOS PROVEEDOR --}}
    <p><strong>Proveedor:</strong> {{ $proveedorNombre }}</p>
    <div class="line"></div>

    {{-- DETALLE PRODUCTOS --}}

        @php
            $total=0;
            $totalSaldo=0.0;
        @endphp
        @foreach($reportes as $r)
            @php
                $total += 1;
                $totalSaldo += (float)$r->saldo;
            @endphp
        <div class="ticket-row">
<div class="ticket-field"><span class="field-label">Doc.:</span> {{ $r->documento }}</div>
<div class="ticket-field"><span class="field-label">Fecha:</span> {{ \Carbon\Carbon::parse($r->fecha_compra)->format('d/m/Y') }}</div>
@include('tickets.summary-row', ['label' => 'Saldo:', 'value' => number_format((float)$r->saldo, 2), 'isTotal' => false])
</div>
        @endforeach

    {{-- TOTALES --}}

        <div class="ticket-row">
@include('tickets.summary-row', ['label' => 'DOCUMENTOS:', 'value' => number_format($total,2), 'isTotal' => false])
@include('tickets.summary-row', ['label' => 'SALDO TOTAL:', 'value' => 'S/ ' . number_format($totalSaldo, 2), 'isTotal' => true])
</div>

    <div class="line"></div>
    <div class="center">
        <p><em>GRACIAS POR SU PREFERENCIA</em></p>
    </div>
</div>
<div id="ticket-end"></div>
</body>
</html>
