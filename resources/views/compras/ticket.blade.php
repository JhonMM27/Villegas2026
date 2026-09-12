<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>COMPRA {{ $compra->serie }}-{{ str_pad((string) $compra->correlativo, 8, '0', STR_PAD_LEFT) }}</title>
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
            <p>COMPRA {{ $compra->serie }}-{{ str_pad((string) $compra->correlativo, 8, '0', STR_PAD_LEFT) }}</p>
        </div>

        <div class="line"></div>

        {{-- FECHA Y HORA --}}

            @include('tickets.date-time', ['date' => $compra->fecha_compra])

        {{-- DATOS PROVEEDOR --}}
        <p><strong>RUC/DNI:</strong> {{ $compra->proveedor_documento ?? '-' }}</p>
        <p><strong>PROVEEDOR:</strong> {{ $compra->proveedor_nombre }}</p>
        <p><strong>DIRECCION:</strong> {{ $compra->proveedor_direccion ?? '-' }}</p>

        <div class="line"></div>

        {{-- DETALLE PRODUCTOS --}}
        @include('tickets.detail-header')
        @foreach ($compra->detalles as $detalle)
            @php
                // Determinar unidad y cantidad según unidad_codigo
                $unidadCodigo = $detalle->unidad_codigo ?? 'NIU';

                if ($unidadCodigo === 'KGM') {
                    $cantidad = number_format((float) $detalle->cantidad_kgm, 2);
                    $unidadTexto = 'KG';
                } elseif ($unidadCodigo === 'SCO' || $unidadCodigo === 'NIU') {
                    // SCO y NIU son productos empacados (aSACOs)
                    $cantidad = number_format((float) $detalle->cantidad, 2);
                    $unidadTexto = 'SACO';
                } else {
                    $cantidad = number_format((float) $detalle->cantidad, 2);
                    $unidadTexto = 'SACO';
                }

                $precio = number_format((float) $detalle->costo_unitario, 2);
                $importe = number_format((float) $detalle->total, 2);
            @endphp

            @include('tickets.item', ['name' => $detalle->producto_nombre, 'quantity' => $cantidad, 'unit' => $unidadTexto, 'price' => $precio, 'amount' => $importe])
        @endforeach

        <div class="line"></div>

        {{-- TOTALES --}}
        @include('tickets.summary-row', ['label' => 'OP. GRAVADAS:', 'value' => 'S/ ' . number_format($compra->op_gravada, 2), 'isTotal' => false])
        @include('tickets.summary-row', ['label' => 'OP. EXONERADAS:', 'value' => 'S/ ' . number_format($compra->op_exonerada, 2), 'isTotal' => false])
        @include('tickets.summary-row', ['label' => 'IMPUESTO:', 'value' => 'S/ ' . number_format($compra->impuesto, 2), 'isTotal' => false])

        @include('tickets.summary-row', ['label' => 'TOTAL:', 'value' => 'S/ ' . number_format($compra->total, 2), 'isTotal' => true])

        <div class="line"></div>

        {{-- COMPRADOR --}}
        <p><strong>USUARIO:</strong> {{ $compra->user_nombre }}</p>

        <div class="line"></div>

        <div class="center">
            <p class="bold">GRACIAS POR SU PREFERENCIA</p>
        </div>

    </div>
<div id="ticket-end"></div>
</body>

</html>
