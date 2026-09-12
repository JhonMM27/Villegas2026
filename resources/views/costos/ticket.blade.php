<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Costo {{ $costo->numero_recibo }}</title>
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
        <h3 class="bold">RECIBO {{ $costo->numero_recibo }}</h3>
    </div>

    <div class="line"></div>

    {{-- DATOS COSTO --}}
    <p><strong>Categoría:</strong> {{ $costo->categoriaCosto?->nombre ?? '-' }}</p>
    <p><strong>Tipo:</strong> {{ $costo->costoTipo?->nombre ?? '-' }}</p>
    <p><strong>Responsable:</strong> {{ $costo->responsable }} @if($costo->responsable_dni) (DNI: {{ $costo->responsable_dni }}) @endif</p>
    <p><strong>Descripción:</strong> {{ $costo->descripcion }}</p>
    @include('tickets.date-time', ['date' => $costo->fecha_costo])

    <div class="line"></div>

    {{-- TOTALES --}}

        @include('tickets.summary-row', ['label' => 'TOTAL COSTO:', 'value' => 'S/ ' . number_format($costo->monto, 2), 'isTotal' => true])


    <p style=" text-align: center;">{{ $total_letras }}</p>

    <div class="line"></div>

    <p><strong>Usuario:</strong> {{ $costo->user_nombre }}</p>
    <p class="bold">COBRANZA</p>

        @include('tickets.summary-row', ['label' => 'Principal:', 'value' => 'S/ ' . number_format($costo->importe_p, 2), 'isTotal' => false])

        @include('tickets.summary-row', ['label' => 'Depósito:', 'value' => 'S/ ' . number_format($costo->importe_d, 2), 'isTotal' => false])

        @include('tickets.summary-row', ['label' => 'Consorcio:', 'value' => 'S/ ' . number_format($costo->importe_c, 2), 'isTotal' => false])


    <div class="line"></div>
    <div class="center">
        <p><em>GRACIAS POR SU PREFERENCIA</em></p>
    </div>

</div>
<div id="ticket-end"></div>
</body>
</html>
