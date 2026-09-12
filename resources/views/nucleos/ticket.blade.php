<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Núcleo {{ $nucleo->id }} - {{ $nucleo->nombre }}</title>
    @include('tickets.styles')
    @include('tickets.compact-styles')
</head>
<body>
<div class="ticket">

    {{-- ENCABEZADO EMPRESA --}}
    <div class="center">
        <h3 class="bold">{{ $empresa->razon_social }}</h3>
        <p>RUC: {{ $empresa->ruc }}</p>
        <p class="bold">Núcleo ID: {{ $nucleo->id }} {{ $nucleo->nombre }}</p>
    </div>
    <div class="line"></div>

    {{-- DETALLE PRODUCTOS --}}

        @foreach($nucleo->detalles as $detalle)
        <div class="ticket-row">
<div class="ticket-field"><span class="field-label">ID:</span> {{ $detalle->producto_id }}</div>
<div class="ticket-field"><span class="field-label">Producto:</span> {{ $detalle->producto_nombre }}</div>
<div class="ticket-field"><span class="field-label">Unidad:</span> {{ $detalle->unidad_codigo }}</div>
@include('tickets.summary-row', ['label' => 'Cantidad:', 'value' => number_format($detalle->cantidad,2), 'isTotal' => false])
</div>
        @endforeach

    {{-- TOTALES --}}

        @include('tickets.summary-row', ['label' => 'Total:', 'value' => number_format($nucleo->detalles->sum('cantidad'),2), 'isTotal' => true])


    <div class="line"></div>
</div>
<div id="ticket-end"></div>
</body>
</html>
