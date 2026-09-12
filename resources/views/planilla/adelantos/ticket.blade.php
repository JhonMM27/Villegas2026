<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Adelanto {{ $adelanto->numero_interno }}</title>
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
            <h3 class="bold">ADELANTO N° {{ $adelanto->numero_interno }}</h3>
        </div>

        <div class="line"></div>

        {{-- DATOS DEL ADELANTO --}}
        <p><strong>Empleado:</strong> {{ $adelanto->empleado->nombre }}</p>
        <p><strong>DNI:</strong> {{ $adelanto->empleado->dni ?? '-' }}</p>
        <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($adelanto->fecha)->format('d/m/Y') }}</p>

        <div class="line"></div>

        {{-- MONTO --}}

            @include('tickets.summary-row', ['label' => 'TOTAL ADELANTO:', 'value' => 'S/ ' . number_format($adelanto->monto, 2), 'isTotal' => true])


        <div class="line"></div>

        <p class="bold">DISTRIBUCIÓN DE CAJA</p>

            @include('tickets.summary-row', ['label' => 'Principal:', 'value' => 'S/ ' . number_format($adelanto->importe_p, 2), 'isTotal' => false])

            @include('tickets.summary-row', ['label' => 'Depósito:', 'value' => 'S/ ' . number_format($adelanto->importe_d, 2), 'isTotal' => false])

            @include('tickets.summary-row', ['label' => 'Consorcio:', 'value' => 'S/ ' . number_format($adelanto->importe_c, 2), 'isTotal' => false])

            @include('tickets.summary-row', ['label' => 'Total Distribuido:', 'value' => 'S/ ' . number_format($adelanto->importe_p + $adelanto->importe_d + $adelanto->importe_c, 2), 'isTotal' => true])


        @if($adelanto->observaciones)
        <div class="line"></div>

        <p><strong>Observaciones:</strong> {{ $adelanto->observaciones }}</p>
        @endif

        <div class="line"></div>
        <div class="center">
            <p><em>GRACIAS POR SU PREFERENCIA</em></p>
        </div>

        {{-- Espacio final para el corte de papel --}}

    </div>
<div id="ticket-end"></div>
</body>

</html>
