<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th class="text-end">Empaque</th>
                <th>Línea</th>
                <th class="text-end">Stock</th>
                <th class="text-end">Costo Unitario</th>
                <th class="text-end">Valor total</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalValor = 0;
                $totalStock = 0;

                $lineaActual = null;
                $subStock = 0;
                $subValor = 0;
            @endphp

            @forelse($reportes as $r)
                @php
                    $linea = $r->linea ?? 'SIN LÍNEA';
                    $stock = (float)($r->stock ?? 0);
                    $costo = (float)($r->costo_unitario ?? 0);
                    $valor = (float)($r->valor_total ?? ($stock * $costo));
                @endphp

                {{-- Si cambia la línea, imprimimos subtotal de la línea anterior --}}
                @if($lineaActual !== null && $linea !== $lineaActual)
                    <tr style="font-weight:bold; background:#f8f9fa;">
                        <td colspan="4" class="text-end">SUBTOTAL {{ $lineaActual }}:</td>
                        <td class="text-end">{{ number_format($subStock, 2) }}</td>
                        <td></td>
                        <td class="text-end">{{ number_format($subValor, 2) }}</td>
                    </tr>

                    @php
                        $subStock = 0;
                        $subValor = 0;
                    @endphp
                @endif

                @php
                    // actualizar línea actual
                    if ($lineaActual === null) $lineaActual = $linea;
                    if ($linea !== $lineaActual) $lineaActual = $linea;

                    // acumular subtotales y totales
                    $subStock += $stock;
                    $subValor += $valor;

                    $totalStock += $stock;
                    $totalValor += $valor;
                @endphp

                <tr>
                    <td>{{ $r->producto_id }}</td>
                    <td>{{ $r->producto }}</td>
                    <td class="text-end">{{ number_format((float)$r->empaque, 2) }}</td>
                    <td>{{ $linea }}</td>
                    <td class="text-end">{{ number_format($stock, 2) }}</td>
                    <td class="text-end">{{ number_format($costo, 4) }}</td>
                    <td class="text-end">{{ number_format($valor, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse

            {{-- Subtotal final (última línea) --}}
            @if(count($reportes) > 0)
                <tr style="font-weight:bold; background:#f8f9fa;">
                    <td colspan="4" class="text-end">SUBTOTAL {{ $lineaActual }}:</td>
                    <td class="text-end">{{ number_format($subStock, 2) }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format($subValor, 2) }}</td>
                </tr>
            @endif
        </tbody>

        @if(count($reportes) > 0)
        <tfoot>
            <tr style="font-weight:bold; border-top:2px solid #000;">
                <th colspan="4" class="text-end">TOTAL GENERAL:</th>
                <th class="text-end">{{ number_format($totalStock, 2) }}</th>
                <th></th>
                <th class="text-end">{{ number_format($totalValor, 2) }}</th>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<div id="modalContainerStock"></div>
