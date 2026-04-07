<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago N° {{ $pago->numero_interno }}</title>
    <style>
        @page { margin: 4mm; size: 80mm auto; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; margin: 0; padding: 0; }
        .ticket { width: 72mm; margin: 0; padding: 0; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        h3 { margin: 0 0 2px 0; }
        p { margin: 1px 0; }
        .line { border-top: 1px dashed #000; margin: 3px 0; }
    </style>
</head>
<body>
<div class="ticket">
    <div class="center">
        <h3 class="bold">{{ $empresa->razon_social }}</h3>
        <p>RUC: {{ $empresa->ruc }}</p>
        <p class="bold">COMPROBANTE DE PAGO</p>
    </div>
    <div class="line"></div>
    
    <p><strong>N° Pago:</strong> {{ str_pad($pago->numero_interno, 6, '0', STR_PAD_LEFT) }}</p>
    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}</p>
    <div class="line"></div>
    
    <p><strong>Préstamo Ref:</strong> N° {{ $pago->prestamo->numero_interno ?? '-' }}</p>
    <p><strong>Empleado:</strong> {{ $pago->prestamo->empleado->nombre ?? '-' }}</p>
    <div class="line"></div>
    
    <p><strong>Monto Pagado:</strong></p>
    <h3 class="bold center">S/ {{ number_format((float)$pago->monto_pagado, 2) }}</h3>
    <div class="line"></div>
    
    @if($pago->observaciones)
    <p><strong>Obs:</strong> {{ $pago->observaciones }}</p>
    <div class="line"></div>
    @endif
    
    <p class="center small">¡Gracias por su pago!</p>
</div>
</body>
</html>
