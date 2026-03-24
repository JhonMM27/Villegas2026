{{-- resources/views/pdf/rentabilidad_productos_fechas.blade.php --}}
<!DOCTYPE html>
<html lang="es">
@php
    $fi = $fechaInicio instanceof \Carbon\Carbon ? $fechaInicio : \Carbon\Carbon::parse($fechaInicio);
    $ff = $fechaFin instanceof \Carbon\Carbon ? $fechaFin : \Carbon\Carbon::parse($fechaFin);

    $titulo = "Rentabilidad por Producto (".$fi->format('d/m/Y')." al ".$ff->format('d/m/Y').")";
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
                Rentabilidad por Producto ({{ $fi->format('d/m/Y') }} al {{ $ff->format('d/m/Y') }})
            </td>
            <td class="text-right">
                <small>Usuario: {{ auth()->user()->name }}</small>
            </td>
        </tr>
    </table>
    <div class="header-separator"></div>
</div>

@php
    $tSacos   = 0.0;
    $tKg      = 0.0;
    $tImporte = 0.0;
    $tCosto   = 0.0;
    $tValor   = 0.0;
@endphp

<table class="reporte">
    <thead>
        <tr>
            <th>Producto</th>
            <th style="width:95px;">Línea</th>

            <th class="text-right" style="width:60px;">Empaque Prod</th>

            <th class="text-right" style="width:55px;">Sacos</th>
            <th class="text-right" style="width:55px;">Kg</th>

            <th class="text-right" style="width:70px;">Importe</th>
            <th class="text-right" style="width:70px;">Costo</th>
            <th class="text-right" style="width:70px;">Valor</th>
            <th class="text-right" style="width:55px;">Rentab %</th>
        </tr>
    </thead>

    <tbody>
        @forelse($reportes as $r)
            @php
                $tSacos   += (float)($r->salida_saco ?? 0);
                $tKg      += (float)($r->salida_kg ?? 0);
                $tImporte += (float)($r->importe ?? 0);
                $tCosto   += (float)($r->costo ?? 0);
                $tValor   += (float)($r->valor ?? 0);
            @endphp

            <tr>
                <td>{{ $r->producto_nombre }}</td>
                <td>{{ $r->producto_linea_nombre }}</td>

                <td class="text-right">{{ number_format((float)($r->empaque_producto ?? 0), 2, '.', '') }}</td>

                <td class="text-right">{{ number_format((float)($r->salida_saco ?? 0), 4, '.', '') }}</td>
                <td class="text-right">{{ number_format((float)($r->salida_kg ?? 0), 2, '.', '') }}</td>

                <td class="text-right">{{ number_format((float)($r->importe ?? 0), 4, '.', '') }}</td>
                <td class="text-right">{{ number_format((float)($r->costo ?? 0), 4, '.', '') }}</td>
                <td class="text-right">{{ number_format((float)($r->valor ?? 0), 4, '.', '') }}</td>
                <td class="text-right">{{ number_format((float)($r->rentab_pct ?? 0), 4, '.', '') }}%</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-right">No se encontraron registros.</td>
            </tr>
        @endforelse
    </tbody>

    @if($reportes->count() > 0)
        @php
            $rentabGen = $tImporte > 0 ? ($tValor / $tImporte) * 100 : 0;
        @endphp
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-right"><strong>TOTAL GENERAL</strong></td>
                <td class="text-right"><strong>{{ number_format($tSacos, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($tKg, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($tImporte, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong></strong></td>
                <td class="text-right"><strong>{{ number_format($tValor, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($rentabGen, 2, '.', '') }}%</strong></td>
            </tr>
        </tfoot>
    @endif
</table>

</body>
</html>