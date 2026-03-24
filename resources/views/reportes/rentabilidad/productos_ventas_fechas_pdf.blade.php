{{-- resources/views/pdf/rentabilidad_productos_contado_credito.blade.php --}}
<!DOCTYPE html>
<html lang="es">
@php
    $fi = $fechaInicio instanceof \Carbon\Carbon ? $fechaInicio : \Carbon\Carbon::parse($fechaInicio);
    $ff = $fechaFin instanceof \Carbon\Carbon ? $fechaFin : \Carbon\Carbon::parse($fechaFin);

    $titulo = "Ventas por Producto (Contado vs Crédito) (".$fi->format('d/m/Y')." al ".$ff->format('d/m/Y').")";
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
                Ventas por Producto (Contado vs Crédito) ({{ $fi->format('d/m/Y') }} al {{ $ff->format('d/m/Y') }})
            </td>
            <td class="text-right">
                <small>Usuario: {{ auth()->user()->name }}</small>
            </td>
        </tr>
    </table>
    <div class="header-separator"></div>
</div>

@php
    $tContado = 0.0;
    $tCredito = 0.0;
    $tTotal   = 0.0;

    // Agrupar por linea_id
    $grupos = collect($reportes)->groupBy(fn($x) => (string)($x->linea_id ?? '0'));
@endphp

<table class="reporte">
    <thead>
        <tr>
            <th style="width:60px;">Código</th>
            <th>Producto</th>
            <th class="text-right" style="width:65px;">Empaque</th>
            <th>Línea</th>
            <th class="text-right" style="width:80px;">Contado</th>
            <th class="text-right" style="width:80px;">Crédito</th>
            <th class="text-right" style="width:80px;">Total</th>
        </tr>
    </thead>

    <tbody>
        @forelse($grupos as $lineaId => $items)
            @php
                $lineaNombre = (string)($items->first()->linea_nombre ?? 'SIN LÍNEA');

                $subContado = 0.0;
                $subCredito = 0.0;
                $subTotal   = 0.0;
            @endphp

            @foreach($items as $r)
                @php
                    $contado = (float)($r->contado ?? 0);
                    $credito = (float)($r->credito ?? 0);
                    $total   = (float)($r->total ?? ($contado + $credito));

                    $subContado += $contado;
                    $subCredito += $credito;
                    $subTotal   += $total;

                    $tContado += $contado;
                    $tCredito += $credito;
                    $tTotal   += $total;
                @endphp

                <tr>
                    <td>{{ $r->producto_id }}</td>
                    <td>{{ $r->producto_nombre }}</td>
                    <td class="text-right">{{ number_format((float)($r->empaque_producto ?? 0), 2, '.', '') }}</td>
                    <td>{{ $r->linea_nombre }}</td>

                    <td class="text-right">{{ number_format($contado, 2, '.', '') }}</td>
                    <td class="text-right">{{ number_format($credito, 2, '.', '') }}</td>
                    <td class="text-right">{{ number_format($total, 2, '.', '') }}</td>
                </tr>
            @endforeach

            {{-- Subtotal por línea --}}
            <tr class="total-row">
                <td colspan="4" class="text-right"><strong>TOTAL {{ $lineaNombre }}</strong></td>
                <td class="text-right"><strong>{{ number_format($subContado, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($subCredito, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($subTotal, 2, '.', '') }}</strong></td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-right">No se encontraron registros.</td>
            </tr>
        @endforelse
    </tbody>

    @if(count($reportes) > 0)
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-right"><strong>TOTAL GENERAL</strong></td>
                <td class="text-right"><strong>{{ number_format($tContado, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($tCredito, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($tTotal, 2, '.', '') }}</strong></td>
            </tr>
        </tfoot>
    @endif
</table>

</body>
</html>