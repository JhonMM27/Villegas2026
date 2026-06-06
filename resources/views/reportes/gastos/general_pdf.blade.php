<!DOCTYPE html>
<html>
<head>
    <title>Reporte General de Gastos</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Courier, monospace; font-size: 11px; margin: 0; padding: 0; }
        @page { margin: 60px 20px 40px 20px; }
        .page-header { position: fixed; top: -60px; left: 0; right: 0; height: 50px; font-family: Courier, monospace; font-size: 11px; padding: 5px 20px; }
        .page-header table { width: 100%; border-collapse: collapse; }
        .page-header td { padding: 2px 0; }
        .text-right { text-align: right; }
        .header-separator { border-bottom: 1px solid #000; margin: 5px 0 10px 0; }
        .page-number:before { content: "Página " counter(page); }
        table.reporte { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.reporte th, table.reporte td { padding: 2px 3px; }
        table.reporte th { border-bottom: 1px solid #000; }
        .subtotal-row { font-weight: bold; border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .total-row { font-weight: bold; border-top: 2px solid #000; }
    </style>
</head>
<body>

    <div class="page-header">
        <table>
            <tr>
                <td>CONSORCIOS VILLEGAS EIRL</td>
                <td class="text-right"><span class="page-number"></span>&nbsp;&nbsp;{{ now()->format('d/m/Y H:i:s') }}</td>
            </tr>
            <tr>
                <td>Reporte General de Gastos ({{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }})</td>
                <td class="text-right"><small>Usuario: {{ auth()->user()->name }}</small></td>
            </tr>
        </table>
        <div class="header-separator"></div>
    </div>

    <table class="reporte">
        <thead>
            <tr>
                <th>Categoria</th>
                <th>Tipo</th>
                <th class="text-right">Total</th>
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
                    <tr class="subtotal-row">
                        <td colspan="2" class="text-right">Subtotal {{ $categoriaActual }}:</td>
                        <td class="text-right">{{ number_format($subtotalCat, 2) }}</td>
                    </tr>
                    @php $subtotalCat = 0; @endphp
                @endif

                <tr>
                    <td><strong>{{ $cat }}</strong></td>
                    <td>{{ $tipo }}</td>
                    <td class="text-right">{{ number_format($total, 2) }}</td>
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
                <tr class="subtotal-row">
                    <td colspan="2" class="text-right">Subtotal {{ $categoriaActual }}:</td>
                    <td class="text-right">{{ number_format($subtotalCat, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="2" class="text-right">TOTAL GENERAL:</td>
                    <td class="text-right">{{ number_format($granTotal, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>