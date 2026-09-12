<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Provisional Compra {{ $provisional->numero_recibo }}</title>
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
    </div>

    <div class="center">
        <h3 class="bold">RECIBO {{ $provisional->numero_recibo }}</h3>
    </div>

    <div class="line"></div>

    {{-- DATOS PROVEEDOR --}}
    <p><strong>Proveedor:</strong> {{ $provisional->proveedor_nombre }}</p>
    <p><strong>Documento:</strong> {{ $provisional->proveedor->documentoTipo->descripcion ?? '-' }} {{ $provisional->proveedor_documento }}</p>
    <p><strong>Dirección:</strong> {{ $provisional->proveedor_direccion ?? '-' }}</p>
    @include('tickets.date-time', ['date' => $provisional->fecha_provisional])

    <div class="line"></div>

    {{-- TOTALES --}}

        @include('tickets.summary-row', ['label' => 'PAGO REALIZADO:', 'value' => 'S/ ' . number_format($provisional->monto, 2), 'isTotal' => false])


    <p style=" text-align: center;">{{ $total_letras }}</p>

        @include('tickets.summary-row', ['label' => 'TOTAL DEUDA:', 'value' => 'S/ ' . number_format($totalDeuda, 2), 'isTotal' => true])


    <div class="line"></div>

    <p><strong>Usuario:</strong> {{ $provisional->user_nombre }}</p>
    <p class="bold">COBRANZA</p>

        @include('tickets.summary-row', ['label' => 'Principal:', 'value' => 'S/ ' . number_format($provisional->importe_p, 2), 'isTotal' => false])

        @include('tickets.summary-row', ['label' => 'Depósito:', 'value' => 'S/ ' . number_format($provisional->importe_d, 2), 'isTotal' => false])

        @include('tickets.summary-row', ['label' => 'Consorcio:', 'value' => 'S/ ' . number_format($provisional->importe_c, 2), 'isTotal' => false])


    <div class="line"></div>
    <div class="center">
        <p><em>GRACIAS POR SU PREFERENCIA</em></p>
    </div>

</div>
<div id="ticket-end"></div>
</body>
</html>
