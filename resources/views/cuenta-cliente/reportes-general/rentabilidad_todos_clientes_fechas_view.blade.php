<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Cliente</th>
                <th class="text-end">Total Importe</th>
                <th class="text-end">Total Costo</th>
                <th class="text-end">Rentabilidad</th>
                <th class="text-end">%</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totImporte = 0.0;
                $totCosto = 0.0;
                $totRentabilidad = 0.0;
            @endphp

            @forelse($reportes as $r)
                @php
                    $importe = (float)($r->total_importe ?? 0);
                    $costo = (float)($r->total_costo ?? 0);
                    $rentabilidad = (float)($r->total_rentabilidad ?? 0);

                    $totImporte += $importe;
                    $totCosto += $costo;
                    $totRentabilidad += $rentabilidad;
                @endphp
                <tr>
                    <td>{{ $r->cliente_nombre }}</td>
                    <td class="text-end">{{ number_format($importe, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($costo, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($rentabilidad, 2, '.', '') }}</td>
                    <td class="text-end">{{ $importe > 0 ? number_format(($rentabilidad / $importe) * 100, 2, '.', '') : '0.00' }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No se encontraron registros en el rango de fechas</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($reportes) > 0)
            <tfoot>
                <tr class="table-dark fw-bold">
                    <td class="text-end">TOTAL GENERAL:</td>
                    <td class="text-end">{{ number_format($totImporte, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($totCosto, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($totRentabilidad, 2, '.', '') }}</td>
                    <td class="text-end">{{ $totImporte > 0 ? number_format(($totRentabilidad / $totImporte) * 100, 2, '.', '') : '0.00' }}%</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>