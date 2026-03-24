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
    // =========================
    // APLANAR DETALLES
    // =========================
    $rows = collect();

    foreach ($reportes as $venta) {
        $fecha = \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y');

        $documento = trim(
            ($venta->comprobante_tipo_codigo ? $venta->comprobante_tipo_codigo.' ' : '') .
            ($venta->serie ?? '') . '-' . ($venta->correlativo ?? '')
        );

        foreach (($venta->detalles ?? collect()) as $d) {
            $cantidad = (float)($d->cantidad ?? 0);
            $precio   = (float)($d->precio_unitario ?? 0);

            $rows->push((object)[
                'producto_id' => $d->producto_id,
                'producto'    => (string)($d->producto_nombre ?? ''),
                'linea'       => (string)($d->producto->linea->nombre ?? ''),
                'um'          => (string)($d->unidad_nombre ?? ($d->unidad_medida ?? '')),

                'fecha'       => $fecha,
                'documento'   => $documento,

                'cantidad'    => $cantidad,
                'precio'      => $precio,
                'importe'     => $cantidad * $precio,

                'costo_unit'  => (float)($d->costo_unitario ?? 0),
                'valor'       => (float)($d->costo_total ?? 0),
                'rent_det'    => (float)($d->rentabilidad ?? 0),
            ]);
        }
    }

    // =========================
    // AGRUPAR Y ORDENAR
    // =========================
    $grupos = $rows->groupBy('producto_id')
                   ->sortBy(function($items){
                       return mb_strtoupper($items->first()->producto, 'UTF-8');
                   });

    // Totales generales
    $gImporte = 0;
    $gCosto   = 0;
    $gValor   = 0;
    $gRentVentas = collect($reportes)->sum(fn($v) => (float)($v->rentabilidad ?? 0));
@endphp

<table class="reporte">
    <thead>
        <tr>
            <th style="width:70px;">Fecha</th>
            <th style="width:110px;">Documento</th>

            <th>Producto</th>
            <th style="width:85px;">Línea</th>
            <th style="width:55px;">U.M.</th>

            <th class="text-right" style="width:45px;">Cant</th>
            <th class="text-right" style="width:55px;">Precio</th>
            <th class="text-right" style="width:65px;">Importe</th>

            <th class="text-right" style="width:65px;">Costo</th>
            <th class="text-right" style="width:65px;">Valor</th>
            <th class="text-right" style="width:70px;">Rentab.</th>
        </tr>
    </thead>

    <tbody>
        @foreach($grupos as $productoId => $items)
            @php
                $p = $items->first();

                $sumCantidad = $items->sum('cantidad');
                $sumImporte  = $items->sum('importe');
                $sumCosto    = $items->sum(fn($x) => $x->cantidad * $x->costo_unit);
                $sumValor    = $items->sum('valor');
                $sumRentDet  = $items->sum('rent_det');

                $gImporte += $sumImporte;
                $gCosto   += $sumCosto;
                $gValor   += $sumValor;
            @endphp

            {{-- DETALLE --}}
            @foreach($items as $r)
                <tr>
                    <td>{{ $r->fecha }}</td>
                    <td>{{ $r->documento }}</td>

                    <td>{{ $r->producto }}</td>
                    <td>{{ $r->linea }}</td>
                    <td>{{ $r->um }}</td>

                    <td class="text-right">{{ number_format($r->cantidad, 2, '.', '') }}</td>
                    <td class="text-right">{{ number_format($r->precio, 4, '.', '') }}</td>
                    <td class="text-right">{{ number_format($r->importe, 2, '.', '') }}</td>

                    <td class="text-right">{{ number_format($r->costo_unit, 4, '.', '') }}</td>
                    <td class="text-right">{{ number_format($r->valor, 4, '.', '') }}</td>
                    <td class="text-right">{{ number_format($r->rent_det, 4, '.', '') }}</td>
                </tr>
            @endforeach

            {{-- TOTAL PRODUCTO --}}
            <tr class="total-row">
                <td colspan="5" class="text-right">
                    <strong>TOTAL {{ $p->producto }}</strong>
                </td>
                <td class="text-right"><strong>{{ number_format($sumCantidad, 2, '.', '') }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($sumImporte, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($sumCosto, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($sumValor, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($sumRentDet, 2, '.', '') }}</strong></td>
            </tr>

        @endforeach
    </tbody>

    <tfoot>
        <tr class="total-row">
            <td colspan="7" class="text-right"><strong>TOTAL GENERAL</strong></td>
            <td class="text-right"><strong>{{ number_format($gImporte, 2, '.', '') }}</strong></td>
            <td class="text-right"><strong></strong></td>
            <td class="text-right"><strong>{{ number_format($gValor, 2, '.', '') }}</strong></td>
            <td class="text-right"><strong>{{ number_format($gRentVentas, 2, '.', '') }}</strong></td>
        </tr>
    </tfoot>
</table>

</body>
</html>