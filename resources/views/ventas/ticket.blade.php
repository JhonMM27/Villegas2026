<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>
        NP - venta {{ $venta->serie }}-{{ str_pad((string) $venta->correlativo, 8, '0', STR_PAD_LEFT) }}
    </title>

    @include('tickets.styles')
    @include('tickets.compact-styles')
</head>

<body>
    <div class="ticket">

        @php
            $numeroVenta = $venta->serie . '-' . str_pad((string) $venta->correlativo, 8, '0', STR_PAD_LEFT);
            $fechaVenta = \Carbon\Carbon::parse($venta->fecha_venta);
        @endphp

        {{-- ENCABEZADO EMPRESA --}}
        <div class="center">
            <h3>CONSORCIOS VILLEGAS E.I.R.L.</h3>
            <p>CEL: 967984895 / 978431737</p>
            <p>NOTA PEDIDO NP: {{ $numeroVenta }}</p>
        </div>

        <div class="line"></div>

        {{-- FECHA Y HORA --}}

            @include('tickets.date-time', ['date' => $fechaVenta])

        {{-- DATOS CLIENTE --}}
        <p>
            <span class="data-label">RUC/DNI:</span>
            {{ $venta->cliente->documento_numero ?? '-' }}
        </p>

        <p>
            <span class="data-label">CLIENTE:</span>
            {{ $venta->cliente_nombre ?? '-' }}
        </p>

        <p>
            <span class="data-label">DIRECCION:</span>
            {{ $venta->cliente->direccion ?? '-' }}
        </p>

        <p>
            <span class="data-label">TELEFONO:</span>
            {{ $venta->cliente->telefono ?? '-' }}
        </p>

        <p>
            <span class="data-label">FORMA DE PAGO:</span>
            {{ $venta->pago_forma_nombre ?? '-' }}
        </p>

        <div class="line"></div>

        {{-- DETALLE PRODUCTOS --}}
        @include('tickets.detail-header')
        @foreach ($venta->detalles as $detalle)
            @php
                $unidadCodigo = $detalle->unidad_codigo ?? 'NIU';

                if ($unidadCodigo === 'KGM') {
                    $cantidad = number_format((float) ($detalle->salida_kg ?? 0), 2, '.', ',');
                    $unidadTexto = 'KG';
                } elseif ($unidadCodigo === 'SCO') {
                    $cantidad = number_format((float) ($detalle->salida_saco ?? 0), 2, '.', ',');
                    $unidadTexto = 'SACO';
                } elseif ($unidadCodigo === 'ZZ') {
                    $cantidad = number_format((float) ($detalle->salida_saco ?? 0), 2, '.', ',');
                    $unidadTexto = '';
                } else {
                    $cantidad = number_format((float) ($detalle->salida_saco ?? 0), 2, '.', ',');
                    $unidadTexto = $unidadCodigo;
                }

                $precio = number_format((float) ($detalle->precio_unitario ?? 0), 2, '.', ',');
                $importe = number_format((float) ($detalle->total ?? 0), 2, '.', ',');
            @endphp

            @include('tickets.item', ['name' => $detalle->producto_nombre ?? 'PRODUCTO SIN NOMBRE', 'quantity' => $cantidad, 'unit' => $unidadTexto, 'price' => $precio, 'amount' => $importe])
        @endforeach

        <div class="line"></div>

        {{-- TOTAL --}}
        @include('tickets.summary-row', ['label' => 'TOTAL:', 'value' => 'S/ ' . number_format((float) ($venta->total ?? 0), 2, '.', ','), 'isTotal' => true])

        <p class="center small">
            Son: {{ $total_letras ?? '' }}
        </p>

        <div class="line"></div>

        {{-- USUARIO --}}
        <p>
            <span class="data-label">USUARIO:</span>
            {{ $venta->user_nombre ?? '-' }}
        </p>

        <div class="line"></div>

        {{-- PIE --}}
        <div class="center">
            <p class="bold">GRACIAS POR SU PREFERENCIA</p>
        </div>

        {{-- COBRANZA --}}
        <div class="cobranza">
            <p class="bold center">COBRANZA</p>

                @include('tickets.summary-row', ['label' => 'PRINCIPAL:', 'value' => 'S/ ' . number_format((float)($venta->importe_p ?? 0), 2, '.', ','), 'isTotal' => false])
                @include('tickets.summary-row', ['label' => 'DEPOSITO:', 'value' => 'S/ ' . number_format((float)($venta->importe_d ?? 0), 2, '.', ','), 'isTotal' => false])
                @include('tickets.summary-row', ['label' => 'CONSORCIO:', 'value' => 'S/ ' . number_format((float)($venta->importe_c ?? 0), 2, '.', ','), 'isTotal' => false])

        </div>

        {{-- SALDO ANTERIOR --}}
        @if (($saldoAnterior ?? 0) > 0)
            <div class="center">
                @include('tickets.summary-row', ['label' => 'SALDO ANTERIOR:', 'value' => 'S/ ' . number_format((float) $saldoAnterior, 2, '.', ','), 'isTotal' => false, 'isBold' => true])
            </div>
        @endif

        <div class="footer-space"></div>

    </div>
<div id="ticket-end"></div>
</body>

</html>
