<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $prestamo->comprobante_tipo_nombre }} - compra {{ $prestamo->serie }}-{{ $prestamo->correlativo }}</title>
    @include('tickets.styles')
    @include('tickets.compact-styles')
</head>
<body>
<div class="ticket">
    {{-- ENCABEZADO EMPRESA --}}
    <div class="center">
        <h3 class="bold">{{ $empresa->razon_social }}</h3>
        <!--<p>{{ $empresa->direccion }}</p>-->
        <p>RUC: {{ $empresa->ruc }}</p>
        <p class="bold">{{ $prestamo->comprobante_tipo_nombre}} {{ $prestamo->serie }}-{{ str_pad((string) $prestamo->correlativo,8,'0',STR_PAD_LEFT) }}</p>
    </div>
    <div class="line"></div>
    {{-- DATOS Origen--}}
    <p><strong>Origen:</strong> {{ $prestamo->clienteOrigen->razon_social }}</p>
    <p><strong>Documento:</strong> {{ $prestamo->clienteOrigen->documento_numero }}</p>
    <p><strong>Dirección:</strong> {{ $prestamo->clienteOrigen->direccion ?? '-' }}</p>
    <div class="line"></div>
    {{-- DATOS Destino--}}
    <p><strong>Destino:</strong> {{ $prestamo->clienteDestino->razon_social }}</p>
    <p><strong>Documento:</strong> {{ $prestamo->clienteDestino->documento_numero }}</p>
    <p><strong>Dirección:</strong> {{ $prestamo->clienteDestino->direccion ?? '-' }}</p>
    @include('tickets.date-time', ['date' => $prestamo->fecha_prestamo])
    <div class="line"></div>
    {{-- DETALLE PRODUCTOS --}}

        @include('tickets.detail-header')
        @foreach($prestamo->detalles as $detalle)
            @include('tickets.item', ['name' => $detalle->producto_nombre, 'quantity' => (string) $detalle->cantidad, 'unit' => '', 'price' => number_format($detalle->valor_unitario, 2), 'amount' => number_format($detalle->total, 2)])
        @endforeach

    {{-- TOTALES --}}

        @include('tickets.summary-row', ['label' => 'TOTAL:', 'value' => 'S/ ' . number_format($prestamo->total,2), 'isTotal' => true])

    <div class="line"></div>
</div>
<div id="ticket-end"></div>
</body>
</html>
