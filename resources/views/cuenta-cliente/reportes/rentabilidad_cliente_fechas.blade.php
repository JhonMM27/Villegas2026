<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Documento</th>
                <th>Producto</th>
                <th>Línea</th>
                <th class="text-end">Cantidad</th>
                <th class="text-end">Precio</th>
                <th class="text-end">Importe</th>
                <th class="text-end">Costo</th>
                <th class="text-end">Valor</th>
                <th class="text-end">Rentabilidad</th>
                <th class="text-end">%</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalImporte = 0.0;
                $totalCosto = 0.0;
                $totalValor = 0.0;
                $totalRentabilidad = 0.0;

                $ventaActual = null;
                $subImporte = 0.0;
                $subCosto = 0.0;
                $subValor = 0.0;
                $subRentabilidad = 0.0;
                $documentoActual = '';
            @endphp

            @forelse($reportes as $r)
                @php
                    $isNewVenta = $ventaActual === null || $r->venta_id !== $ventaActual;

                    if ($isNewVenta && $ventaActual !== null) {
                        @endphp
                        <tr class="table-secondary fw-bold">
                            <td colspan="6" class="text-end">SUBTOTAL {{ $documentoActual }}:</td>
                            <td class="text-end">{{ number_format($subImporte, 2, '.', '') }}</td>
                            <td class="text-end"></td>
                            <td class="text-end">{{ number_format($subValor, 2, '.', '') }}</td>
                            <td class="text-end">{{ number_format($subRentabilidad, 2, '.', '') }}</td>
                            <td class="text-end">{{ $subImporte > 0 ? number_format(($subRentabilidad / $subImporte) * 100, 2, '.', '') : '0.00' }}%</td>
                        </tr>
                        @php
                        $subImporte = 0.0;
                        $subCosto = 0.0;
                        $subValor = 0.0;
                        $subRentabilidad = 0.0;
                    }

                    if ($isNewVenta) {
                        $ventaActual = $r->venta_id;
                        $documentoActual = $r->documento;
                    }

                    $cantidad = (float)($r->cantidad ?? 0);
                    $precio = (float)($r->precio_unitario ?? 0);
                    $importe = (float)($r->total ?? 0);
                    $costo = (float)($r->costo_unitario ?? 0);
                    $valor = (float)($r->costo_total ?? 0);
                    $rentabilidad = (float)($r->rentabilidad ?? 0);

                    $subImporte += $importe;
                    $subCosto += $costo;
                    $subValor += $valor;
                    $subRentabilidad += $rentabilidad;

                    $totalImporte += $importe;
                    $totalCosto += $costo;
                    $totalValor += $valor;
                    $totalRentabilidad += $rentabilidad;
                @endphp

                <tr>
                    <td>{{ \Carbon\Carbon::parse($r->fecha_venta)->format('d/m/Y') }}</td>
                    <td>{{ $r->documento }}</td>
                    <td>{{ $r->producto_nombre }}</td>
                    <td>{{ $r->linea_nombre ?? '-' }}</td>
                    <td class="text-end">{{ number_format($cantidad, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($precio, 4, '.', '') }}</td>
                    <td class="text-end">{{ number_format($importe, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($costo, 4, '.', '') }}</td>
                    <td class="text-end">{{ number_format($valor, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($rentabilidad, 2, '.', '') }}</td>
                    <td class="text-end">{{ $importe > 0 ? number_format(($rentabilidad / $importe) * 100, 2, '.', '') : '0.00' }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse

            @if(count($reportes) > 0)
                <tr class="table-secondary fw-bold">
                    <td colspan="6" class="text-end">SUBTOTAL {{ $documentoActual }}:</td>
                    <td class="text-end">{{ number_format($subImporte, 2, '.', '') }}</td>
                    <td class="text-end"></td>
                    <td class="text-end">{{ number_format($subValor, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($subRentabilidad, 2, '.', '') }}</td>
                    <td class="text-end">{{ $subImporte > 0 ? number_format(($subRentabilidad / $subImporte) * 100, 2, '.', '') : '0.00' }}%</td>
                </tr>
            @endif
        </tbody>

        @if(count($reportes) > 0)
            <tfoot>
                <tr class="table-dark fw-bold">
                    <td colspan="6" class="text-end">TOTAL GENERAL:</td>
                    <td class="text-end">{{ number_format($totalImporte, 2, '.', '') }}</td>
                    <td class="text-end"></td>
                    <td class="text-end">{{ number_format($totalValor, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($totalRentabilidad, 2, '.', '') }}</td>
                    <td class="text-end">{{ $totalImporte > 0 ? number_format(($totalRentabilidad / $totalImporte) * 100, 2, '.', '') : '0.00' }}%</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
