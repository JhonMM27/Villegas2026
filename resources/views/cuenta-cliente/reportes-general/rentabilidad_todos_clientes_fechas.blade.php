<!DOCTYPE html>
<html>
@include('pdf.styles', [
    'title' => "Rentabilidad por cliente ({$ini->format('d/m/Y')} al {$fin->format('d/m/Y')})"
])

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
            <td>Reporte: Rentabilidad por cliente ({{ $ini->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }})</td>
            <td class="text-right">
                <small>Usuario: {{ auth()->user()->name }}</small>
            </td>
        </tr>
    </table>
    <div class="header-separator"></div>
</div>

<table class="reporte">
    <thead>
        <tr>
            <th>Cliente</th>
            <th class="text-right">Total Importe</th>
            <th class="text-right">Total Costo</th>
            <th class="text-right">Rentabilidad</th>
            <th class="text-right">%</th>
        </tr>
    </thead>
    <tbody>
        @forelse($reportes as $r)
            <tr class="linea2">
                <td>{{ $r->cliente_nombre }}</td>
                <td class="text-right">{{ number_format((float)$r->total_importe, 2) }}</td>
                <td class="text-right">{{ number_format((float)$r->total_costo, 2) }}</td>
                <td class="text-right">{{ number_format((float)$r->total_rentabilidad, 2) }}</td>
                <td class="text-right">{{ $r->total_importe > 0 ? number_format(($r->total_rentabilidad / $r->total_importe) * 100, 2) : '0.00' }}%</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center">No se encontraron registros en el rango de fechas</td>
            </tr>
        @endforelse

        @if(count($reportes) > 0)
            <tr class="total-row">
                <td class="text-right">TOTAL GENERAL:</td>
                <td class="text-right">{{ number_format((float)$totImporte, 2) }}</td>
                <td class="text-right">{{ number_format((float)$totCosto, 2) }}</td>
                <td class="text-right">{{ number_format((float)$totRentabilidad, 2) }}</td>
                <td class="text-right">{{ $totImporte > 0 ? number_format(($totRentabilidad / $totImporte) * 100, 2) : '0.00' }}%</td>
            </tr>
        @endif
    </tbody>
</table>

</body>
</html>