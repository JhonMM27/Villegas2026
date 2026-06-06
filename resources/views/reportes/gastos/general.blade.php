<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Categoria</th>
                <th>Tipo</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $agrupado = [];
                foreach($reportes as $r) {
                    $cat = $r->categoriaGasto?->nombre ?? 'SIN CATEGORIA';
                    $tipo = $r->gastoTipo?->nombre ?? 'SIN TIPO';
                    $total = (float)($r->importe_p ?? 0) + (float)($r->importe_d ?? 0) + (float)($r->importe_c ?? 0);
                    $key = $cat . '||' . $tipo;
                    if (!isset($agrupado[$key])) {
                        $agrupado[$key] = ['categoria' => $cat, 'tipo' => $tipo, 'total' => 0];
                    }
                    $agrupado[$key]['total'] += $total;
                }

                $categoriaActual = null;
                $subtotalCat = 0;
                $granTotal = 0;
                $items = collect($agrupado)->sortBy(function($item) {
                    return $item['categoria'] . '||' . $item['tipo'];
                });
            @endphp

            @forelse($items as $item)
                @php
                    $cat = $item['categoria'];
                    $tipo = $item['tipo'];
                    $total = round($item['total'], 2);
                    $granTotal += $total;
                @endphp

                @if($categoriaActual !== null && $categoriaActual !== $cat)
                    <tr style="font-weight:bold; background:#f8f9fa;">
                        <td colspan="2" class="text-end">Subtotal {{ $categoriaActual }}:</td>
                        <td class="text-end">{{ number_format($subtotalCat, 2) }}</td>
                    </tr>
                    @php $subtotalCat = 0; @endphp
                @endif

                <tr>
                    <td><strong>{{ $cat }}</strong></td>
                    <td>{{ $tipo }}</td>
                    <td class="text-end">{{ number_format($total, 2) }}</td>
                </tr>

                @php
                    $subtotalCat += $total;
                    $categoriaActual = $cat;
                @endphp
            @empty
                <tr>
                    <td colspan="3" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse

            @if(count($items) > 0)
                <tr style="font-weight:bold; background:#f8f9fa;">
                    <td colspan="2" class="text-end">Subtotal {{ $categoriaActual }}:</td>
                    <td class="text-end">{{ number_format($subtotalCat, 2) }}</td>
                </tr>
                <tr style="font-weight:bold; border-top:2px solid #000;">
                    <td colspan="2" class="text-end">TOTAL GENERAL:</td>
                    <td class="text-end">{{ number_format($granTotal, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>