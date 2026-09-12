<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Préstamo {{ $prestamo->numero_interno }}</title>
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
            <h3 class="bold">PRÉSTAMO N° {{ $prestamo->numero_interno }}</h3>
        </div>

        <div class="line"></div>

        {{-- DATOS DEL PRÉSTAMO --}}
        <p><strong>Empleado:</strong> {{ $prestamo->empleado->nombre }}</p>
        <p><strong>DNI:</strong> {{ $prestamo->empleado->dni ?? '-' }}</p>
        <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($prestamo->fecha_prestamo)->format('d/m/Y') }}</p>
        <p><strong>Estado:</strong> {{ ucfirst($prestamo->estado) }}</p>

        <div class="line"></div>

        {{-- MONTOS --}}

            @include('tickets.summary-row', ['label' => 'MONTO ORIGINAL:', 'value' => 'S/ ' . number_format($prestamo->monto_original, 2), 'isTotal' => false])

            @include('tickets.summary-row', ['label' => 'TOTAL PAGADO:', 'value' => 'S/ ' . number_format($prestamo->monto_original - $prestamo->saldo_pendiente, 2), 'isTotal' => true])

            @include('tickets.summary-row', ['label' => 'SALDO PENDIENTE:', 'value' => 'S/ ' . number_format($prestamo->saldo_pendiente, 2), 'isTotal' => false])


        <div class="line"></div>

        <p class="bold">DISTRIBUCIÓN DE CAJA</p>

            @include('tickets.summary-row', ['label' => 'Principal:', 'value' => 'S/ ' . number_format($prestamo->importe_p, 2), 'isTotal' => false])

            @include('tickets.summary-row', ['label' => 'Depósito:', 'value' => 'S/ ' . number_format($prestamo->importe_d, 2), 'isTotal' => false])

            @include('tickets.summary-row', ['label' => 'Consorcio:', 'value' => 'S/ ' . number_format($prestamo->importe_c, 2), 'isTotal' => false])


        @if($prestamo->observaciones)

        <p><strong>Observaciones:</strong> {{ $prestamo->observaciones }}</p>
        @endif

        @if($prestamo->pagos && $prestamo->pagos->count() > 0)
        <div class="line"></div>

        <p class="bold">HISTORIAL DE PAGOS</p>

            @foreach($prestamo->pagos as $pago)
            @include('tickets.summary-row', ['label' => \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y'), 'value' => 'S/ ' . number_format($pago->monto_pagado, 2), 'isTotal' => false])

            @endforeach

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
