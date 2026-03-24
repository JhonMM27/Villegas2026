<!DOCTYPE html>
<html lang="es">
@php
    $fi = $fechaInicio instanceof \Carbon\Carbon ? $fechaInicio : \Carbon\Carbon::parse($fechaInicio);
    $ff = $fechaFin instanceof \Carbon\Carbon ? $fechaFin : \Carbon\Carbon::parse($fechaFin);

    $titulo = "Rentabilidad de Ventas (".$fi->format('d/m/Y')." al ".$ff->format('d/m/Y').")";
@endphp

@include('pdf.styles', ['title' => $titulo])

<body>

<div class="page-header">
    <table>
        <tr>
            <td>CONSORCIOS VILLEGAS EIRL</td>
            <td class="text-right">
                <span class="page-number"></span>&nbsp;&nbsp;{{ now()->format('d/m/Y H:i:s') }}
            </td>
        </tr>
        <tr>
            <td>
                Rentabilidad de Ventas ({{ $fi->format('d/m/Y') }} al {{ $ff->format('d/m/Y') }})
            </td>
            <td class="text-right">
                <small>Usuario: {{ auth()->user()->name }}</small>
            </td>
        </tr>
    </table>
    <div class="header-separator"></div>
</div>

@php
    $gImporte = 0.0;
    $gCosto   = 0.0;
    $gValor   = 0.0;
    $gRentVentas = 0.0;
@endphp

<table class="reporte">
    <thead>
        <tr>
            <th style="width:70px;">Fecha</th>
            <th style="width:120px;">Documento</th>

            <th>Producto</th>
            <th style="width:85px;">Línea</th>

            <th class="text-right" style="width:45px;">Cant</th>
            <th class="text-right" style="width:55px;">Precio</th>
            <th class="text-right" style="width:65px;">Importe</th>

            <th class="text-right" style="width:65px;">Costo Unit.</th>
            <th class="text-right" style="width:65px;">Valor</th>
            <th class="text-right" style="width:70px;">Rentab.</th>
        </tr>
    </thead>

    <tbody>
        @forelse($reportes as $venta)
            @php
                $vRent = (float)($venta->rentabilidad ?? 0);
                $gRentVentas += $vRent;

                $fechaVenta = \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y');

                $documento = trim(
                    ($venta->comprobante_tipo_codigo ? $venta->comprobante_tipo_codigo.' ' : '') .
                    ($venta->serie ?? '') . '-' .
                    ($venta->correlativo ?? '')
                );

                $detalles = $venta->detalles ?? collect();

                $vImporte = 0.0;
                $vCosto   = 0.0;
                $vValor   = 0.0;
            @endphp

            {{-- CABECERA VENTA --}}
            <tr>
                <td><strong>{{ $fechaVenta }}</strong></td>
                <td><strong>{{ $documento }}</strong></td>
                <td colspan="8"></td>
            </tr>

            @if($detalles->count() === 0)
                <tr>
                    <td colspan="10" class="text-right">Venta sin detalles</td>
                </tr>
            @else
                @foreach($detalles as $d)
                    @php
                        $cantidad = (float)($d->cantidad ?? 0);
                        $precio   = (float)($d->precio_unitario ?? 0);
                        $importe  = $cantidad * $precio;

                        $costoUnit = (float)($d->costo_unitario ?? 0);
                        $valor     = (float)($d->costo_total ?? 0);
                        $rentDet   = (float)($d->rentabilidad ?? 0);

                        $vImporte += $importe;
                        $vCosto   += $costoUnit * $cantidad;
                        $vValor   += $valor;
                    @endphp

                    <tr>
                        <td></td>
                        <td></td>

                        <td>{{ $d->producto_nombre }}</td>
                        <td>{{ $d->producto->linea->nombre  }}</td>

                        <td class="text-right">{{ number_format($cantidad, 2, '.', '') }}</td>
                        <td class="text-right">{{ number_format($precio, 4, '.', '') }}</td>
                        <td class="text-right">{{ number_format($importe, 2, '.', '') }}</td>

                        <td class="text-right">{{ number_format($costoUnit, 4, '.', '') }}</td>
                        <td class="text-right">{{ number_format($valor, 4, '.', '') }}</td>
                        <td class="text-right">{{ number_format($rentDet, 4, '.', '') }}</td>
                    </tr>
                @endforeach
            @endif

            @php
                $gImporte += $vImporte;
                $gCosto   += $vCosto;
                $gValor   += $vValor;
            @endphp

            {{-- TOTAL POR VENTA --}}
            <tr class="total-row">
                <td colspan="6" class="text-right">
                    <strong>TOTAL {{ $documento }}</strong>
                </td>
                <td class="text-right"><strong>{{ number_format($vImporte, 2, '.', '') }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($vValor, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($vRent, 2, '.', '') }}</strong></td>
            </tr>

        @empty
            <tr>
                <td colspan="10" class="text-right">No se encontraron registros.</td>
            </tr>
        @endforelse
    </tbody>

    @if(count($reportes) > 0)
        <tfoot>
            <tr class="total-row">
                <td colspan="6" class="text-right"><strong>TOTAL GENERAL</strong></td>
                <td class="text-right"><strong>{{ number_format($gImporte, 2, '.', '') }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($gValor, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($gRentVentas, 2, '.', '') }}</strong></td>
            </tr>
        </tfoot>
    @endif

</table>

</body>
</html>