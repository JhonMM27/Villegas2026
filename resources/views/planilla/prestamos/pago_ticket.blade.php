<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago N° {{ $pago->numero_interno }}</title>
    @include('tickets.styles')
    @include('tickets.compact-styles')
</head>
<body>
<div class="ticket">
    <div class="center">
        <h3 class="bold">{{ $empresa->razon_social }}</h3>
        <p>RUC: {{ $empresa->ruc }}</p>
        <p class="bold">COMPROBANTE DE PAGO</p>
    </div>
    <div class="line"></div>

    <p><strong>N° Pago:</strong> {{ str_pad((string) $pago->numero_interno, 6, '0', STR_PAD_LEFT) }}</p>
    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}</p>
    <div class="line"></div>

    <p><strong>Préstamo Ref:</strong> N° {{ $pago->prestamo->numero_interno ?? '-' }}</p>
    <p><strong>Empleado:</strong> {{ $pago->prestamo->empleado->nombre ?? '-' }}</p>
    <div class="line"></div>

    <p><strong>Monto Pagado:</strong></p>
    <h3 class="bold center">S/ <span class="number">{{ number_format((float)$pago->monto_pagado, 2) }}</span></h3>
    <div class="line"></div>

    @if($pago->observaciones)
    <p><strong>Obs:</strong> {{ $pago->observaciones }}</p>
    <div class="line"></div>
    @endif

    <p class="center small">¡Gracias por su pago!</p>
</div>
<div id="ticket-end"></div>
</body>
</html>
