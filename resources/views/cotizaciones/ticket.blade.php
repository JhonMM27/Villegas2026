<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>
        COTIZACION {{ $cotizacion->serie }}-{{ str_pad((string) $cotizacion->correlativo, 8, '0', STR_PAD_LEFT) }}
    </title>

    @include('tickets.styles')
    @include('tickets.compact-styles')
</head>

<body>
    <div class="ticket">

        @php
            $numeroCotizacion = $cotizacion->serie . '-' . str_pad((string) $cotizacion->correlativo, 8, '0', STR_PAD_LEFT);
            $fechaCotizacion = \Carbon\Carbon::parse($cotizacion->fecha_cotizacion);
        @endphp

        {{-- ENCABEZADO --}}
        <div class="center">
            <h3>CONSORCIOS VILLEGAS E.I.R.L.</h3>
            <p>CEL: 967984895 / 978431737</p>
            <p>COTIZACION: {{ $numeroCotizacion }}</p>
        </div>

        <div class="line"></div>

        {{-- FECHA Y HORA --}}

            @include('tickets.date-time', ['date' => $fechaCotizacion])

        {{-- DATOS CLIENTE --}}
        <p>
            <span class="data-label">RUC/DNI:</span>
            {{ $cotizacion->cliente->documento_numero ?? '-' }}
        </p>

        <p>
            <span class="data-label">CLIENTE:</span>
            {{ $cotizacion->cliente_nombre ?? '-' }}
        </p>

        <p>
            <span class="data-label">DIRECCION:</span>
            {{ $cotizacion->cliente->direccion ?? '-' }}
        </p>

        <p>
            <span class="data-label">TELEFONO:</span>
            {{ $cotizacion->cliente->telefono ?? '-' }}
        </p>

        <p>
            <span class="data-label">FORMA DE PAGO:</span>
            {{ $cotizacion->pago_forma_nombre ?? '-' }}
        </p>

        <div class="line"></div>

        {{-- DETALLE PRODUCTOS --}}
        @include('tickets.detail-header')
        @foreach ($cotizacion->detalles as $detalle)
            @php
                $unidadCodigo = $detalle->unidad_codigo ?? 'NIU';
                $empaque = (float) ($detalle->producto_empaque ?? 1);
                $cantidadBase = (float) ($detalle->cantidad ?? 0);

                if ($unidadCodigo === 'KGM') {
                    $cantidad = number_format($cantidadBase * $empaque, 2, '.', ',');
                    $unidadTexto = 'KG';
                } elseif ($unidadCodigo === 'SCO') {
                    $cantidad = number_format($cantidadBase, 2, '.', ',');
                    $unidadTexto = 'SACO';
                } elseif ($unidadCodigo === 'ZZ') {
                    $cantidad = number_format($cantidadBase, 2, '.', ',');
                    $unidadTexto = '';
                } else {
                    $cantidad = number_format($cantidadBase, 2, '.', ',');
                    $unidadTexto = $unidadCodigo;
                }

                $precio = number_format((float) ($detalle->precio_unitario ?? 0), 2, '.', ',');
                $importe = number_format((float) ($detalle->total ?? 0), 2, '.', ',');
            @endphp

            @include('tickets.item', ['name' => $detalle->producto_nombre ?? 'PRODUCTO SIN NOMBRE', 'quantity' => $cantidad, 'unit' => $unidadTexto, 'price' => $precio, 'amount' => $importe])
        @endforeach

        <div class="line"></div>

        {{-- TOTAL --}}
        @include('tickets.summary-row', ['label' => 'TOTAL:', 'value' => 'S/ ' . number_format((float) ($cotizacion->total ?? 0), 2, '.', ','), 'isTotal' => true])

        <p class="center small">
            Son: {{ $total_letras ?? '' }}
        </p>

        <div class="line"></div>

        {{-- USUARIO --}}
        <p>
            <span class="data-label">USUARIO:</span>
            {{ $cotizacion->user_nombre ?? '-' }}
        </p>

        <div class="line"></div>

        {{-- PIE --}}
        <div class="center">
            <p class="bold">GRACIAS POR SU PREFERENCIA</p>
        </div>

        <div class="footer-space"></div>

    </div>
<div id="ticket-end"></div>
</body>

</html>
