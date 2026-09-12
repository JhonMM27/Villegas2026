<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gasto {{ $gasto->numero_recibo }}</title>
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
        <h3 class="bold">RECIBO {{ $gasto->numero_recibo }}</h3>
    </div>

    <div class="line"></div>

    {{-- DATOS GASTO --}}
    <p><strong>Categoría:</strong> {{ $gasto->categoriaGasto?->nombre ?? '-' }}</p>
    <p><strong>Tipo:</strong> {{ $gasto->gastoTipo?->nombre ?? '-' }}</p>
    <p><strong>Responsable:</strong> {{ $gasto->responsable }} @if($gasto->responsable_dni) (DNI: {{ $gasto->responsable_dni }}) @endif</p>
    <p><strong>Descripción:</strong> {{ $gasto->descripcion }}</p>
    @include('tickets.date-time', ['date' => $gasto->fecha_gasto])

    <div class="line"></div>

    {{-- TOTALES --}}

        @include('tickets.summary-row', ['label' => 'TOTAL GASTO:', 'value' => 'S/ ' . number_format($gasto->monto, 2), 'isTotal' => true])


    <p style=" text-align: center;">{{ $total_letras }}</p>

    <div class="line"></div>

    <p><strong>Usuario:</strong> {{ $gasto->user_nombre }}</p>
    <p class="bold">COBRANZA</p>

        @include('tickets.summary-row', ['label' => 'Principal:', 'value' => 'S/ ' . number_format($gasto->importe_p, 2), 'isTotal' => false])

        @include('tickets.summary-row', ['label' => 'Depósito:', 'value' => 'S/ ' . number_format($gasto->importe_d, 2), 'isTotal' => false])

        @include('tickets.summary-row', ['label' => 'Consorcio:', 'value' => 'S/ ' . number_format($gasto->importe_c, 2), 'isTotal' => false])


    <div class="line"></div>
    <div class="center">
        <p><em>GRACIAS POR SU PREFERENCIA</em></p>
    </div>

</div>
<div id="ticket-end"></div>
</body>
</html>
