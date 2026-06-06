<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Recibo</th>
                <th>Nro. Interno</th>
                <th>Categoria</th>
                <th>Fecha</th>
                <th>Tipo Gasto</th>
                <th>Descripcion</th>
                <th>Responsable</th>
                <th class="text-end">Principal</th>
                <th class="text-end">Deposito</th>
                <th class="text-end">Consorcio</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalPrincipal = 0;
                $totalDeposito = 0;
                $totalConsorcio = 0;
            @endphp

            @forelse($reportes as $r)
                @php
                    $principal = (float)($r->importe_p ?? 0);
                    $deposito = (float)($r->importe_d ?? 0);
                    $consorcio = (float)($r->importe_c ?? 0);
                    $fecha = $r->fecha_gasto ? \Carbon\Carbon::parse($r->fecha_gasto)->format('d/m/Y') : '';
                    $totalPrincipal += $principal;
                    $totalDeposito += $deposito;
                    $totalConsorcio += $consorcio;
                @endphp
                <tr>
                    <td>{{ $r->numero_recibo }}</td>
                    <td>{{ $r->numero_interno ?? '' }}</td>
                    <td>{{ $r->categoriaGasto?->nombre ?? '' }}</td>
                    <td>{{ $fecha }}</td>
                    <td>{{ $r->gastoTipo?->nombre ?? '' }}</td>
                    <td>{{ $r->descripcion ?? '' }}</td>
                    <td>{{ $r->responsable ?? '' }}</td>
                    <td class="text-end">{{ number_format($principal, 2) }}</td>
                    <td class="text-end">{{ number_format($deposito, 2) }}</td>
                    <td class="text-end">{{ number_format($consorcio, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse

            @if(count($reportes) > 0)
                <tr style="font-weight:bold; border-top:2px solid #000;">
                    <td colspan="7" class="text-end">TOTALES:</td>
                    <td class="text-end">{{ number_format($totalPrincipal, 2) }}</td>
                    <td class="text-end">{{ number_format($totalDeposito, 2) }}</td>
                    <td class="text-end">{{ number_format($totalConsorcio, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>