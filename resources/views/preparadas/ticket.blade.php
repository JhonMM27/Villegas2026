<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>PREPARADA {{ $preparada->id }}</title>
    @include('tickets.styles')
    @include('tickets.compact-styles')
</head>

<body>
    <div class="ticket">

        {{-- ENCABEZADO EMPRESA --}}
        <div class="center">
            <h3>CONSORCIOS VILLEGAS E.I.R.L.</h3>
        </div>

        <div class="center">
            <p>CEL: 967984895 / 978431737</p>
            <p>PREPARADA: {{ $preparada->id }}</p>
        </div>

        <div class="line"></div>

        {{-- FECHA Y HORA --}}

            @include('tickets.date-time', ['date' => $preparada->fecha])

        {{-- DATOS CLIENTE --}}
        <p><strong>CLIENTE:</strong> {{ $preparada->cliente_nombre }}</p>
        <p><strong>DIRECCION:</strong> {{ $preparada->cliente_direccion ?? '-' }}</p>

        <p><strong>PRODUCTO:</strong> {{ $preparada->producto_nombre }} ({{ $preparada->producto_empaque }}KG)</p>

        <div class="line"></div>

        {{-- DETALLE INSUMOS --}}
        @include('tickets.detail-header')
        @foreach ($preparada->detalles as $detalle)

            @php
                $cantidad = number_format((float) $detalle->salida_kg, 2);
                $precio = number_format((float) $detalle->precio_unitario, 2);
                $importe = number_format((float) ($detalle->salida_kg * $detalle->precio_unitario), 2);
            @endphp

            @include('tickets.item', ['name' => $detalle->producto_nombre, 'quantity' => $cantidad, 'unit' => 'KG', 'price' => $precio, 'amount' => $importe])
        @endforeach

        <div class="line"></div>

        {{-- TOTALES --}}
        @include('tickets.summary-row', ['label' => 'SACOS:', 'value' => number_format($preparada->ingreso_saco, 2), 'isTotal' => false])
        @include('tickets.summary-row', ['label' => 'KG TOTALES:', 'value' => number_format($preparada->ingreso_kg, 2), 'isTotal' => false])

        @include('tickets.summary-row', ['label' => 'TOTAL:', 'value' => 'S/ ' . number_format($preparada->ingreso_soles, 2), 'isTotal' => true])

        <div class="line"></div>

        {{-- USUARIO --}}
        <p><strong>USUARIO:</strong> {{ $preparada->user_nombre }}</p>

        <div class="line"></div>

        <div class="center">
            <p class="bold">GRACIAS POR SU PREFERENCIA</p>
        </div>

    </div>
<div id="ticket-end"></div>
</body>

</html>
