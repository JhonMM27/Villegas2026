<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $entrega->numero_recibo }} - venta {{ $entrega->comprobante_tipo_codigo }} {{ $entrega->serie }}-{{ $entrega->correlativo }}</title>
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

        <h3 class="bold">ENTREGA {{ $entrega->numero_recibo}} ({{ $entrega->comprobante_tipo_nombre}} {{ $entrega->serie }}-{{ str_pad((string) $entrega->correlativo,8,'0',STR_PAD_LEFT) }})</h3>
    </div>
    <div class="line"></div>
    {{-- DATOS Cliente --}}
    <p><strong>Cliente:</strong> {{ $entrega->cliente_nombre }}</p>
    <p><strong>Documento:</strong> {{ $entrega->cliente->documentoTipo->descripcion ?? '-' }} {{ $entrega->cliente_documento }}</p>
    <p><strong>Dirección:</strong> {{ $entrega->cliente_direccion ?? '-' }}</p>
    @include('tickets.date-time', ['date' => $entrega->fecha_venta])
    <div class="line"></div>

    {{-- DETALLE PRODUCTOS --}}

        @foreach($entrega->detalles as $detalle)
        <div class="ticket-row">
<div class="ticket-field"><span class="field-label">Descripción:</span> {{ $detalle->ventaDetalle->producto_nombre ?? '' }}
                {{ $detalle->ventaDetalle->unidad_nombre ?? '' }}</div>
@include('tickets.summary-row', ['label' => 'Cant. Entregada:', 'value' => number_format((float)$detalle->cantidad, 2), 'isTotal' => false])
@include('tickets.summary-row', ['label' => 'Cant. Comprado:', 'value' => number_format((float)($detalle->ventaDetalle->cantidad ?? 0), 2), 'isTotal' => false])
@include('tickets.summary-row', ['label' => 'Saldo:', 'value' => number_format((float)($detalle->ventaDetalle->saldo ?? 0), 2), 'isTotal' => false])
</div>
        @endforeach

    <div class="line"></div>
    <div class="center">
        <p><em>GRACIAS POR SU PREFERENCIA</em></p>
    </div>
</div>
<div id="ticket-end"></div>
</body>
</html>
