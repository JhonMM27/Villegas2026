<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formulación {{ $formulacion->id }}</title>
    @include('tickets.styles')
    @include('tickets.compact-styles')
</head>
<body>
<div class="ticket">

    {{-- ENCABEZADO EMPRESA --}}
    <div class="center">
        <h3 class="bold">{{ $empresa->razon_social }}</h3>
        <p>RUC: {{ $empresa->ruc }}</p>
        <p class="bold">Formulación ID: {{ $formulacion->id }}</p>
    </div>
    <div class="line"></div>

    {{-- DATOS CLIENTE --}}
    <p><strong>Cliente:</strong> {{ $formulacion->cliente_id }} - {{ $formulacion->cliente_nombre }}</p>
    <p><strong>Dirección:</strong> {{ $formulacion->cliente->direccion ?? '-' }}</p>
    @include('tickets.date-time', ['date' => $formulacion->fecha])
    <p><strong>Formulación:</strong> {{ $formulacion->producto_nombre }} | Empaque: {{ $formulacion->producto_empaque }}</p>
    <p><strong>Salida (KG):</strong> {{ $formulacion->salida_kg }}</p>
    <div class="line"></div>

    {{-- DETALLE PRODUCTOS --}}

        @foreach($formulacion->detalles as $detalle)
        <div class="ticket-row">
<div class="ticket-field"><span class="field-label">Producto:</span> {{ $detalle->producto_nombre }}</div>
<div class="ticket-field"><span class="field-label">Empaque:</span> {{ $detalle->producto_empaque }}</div>
<div class="ticket-field"><span class="field-label">Línea:</span> {{ $detalle->producto_linea }}</div>
@include('tickets.summary-row', ['label' => 'Salida (KG):', 'value' => number_format($detalle->salida_kg,2), 'isTotal' => false])
</div>
        @endforeach

    {{-- TOTALES --}}

        @include('tickets.summary-row', ['label' => 'Total Salida (KG):', 'value' => number_format($formulacion->detalles->sum('salida_kg'),2), 'isTotal' => true])


    <div class="line"></div>
</div>
<div id="ticket-end"></div>
</body>
</html>
