<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rentabilidad por Cliente</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h2 { margin: 0; }
        .header p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .subtotal { background-color: #e0e0e0; font-weight: bold; }
        .total { background-color: #333; color: white; font-weight: bold; }
        .footer { margin-top: 15px; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Rentabilidad por Cliente</h2>
        <p><strong>Cliente:</strong> {{ $clienteNombre }}</p>
        <p><strong>Rango:</strong> {{ $ini->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Documento</th>
                <th>Producto</th>
                <th>Línea</th>
                <th class="text-right">Cant</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Importe</th>
                <th class="text-right">Costo</th>
                <th class="text-right">Valor</th>
                <th class="text-right">Rentab</th>
                <th class="text-right">%</th>
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
                $subValor = 0.0;
                $subRentabilidad = 0.0;
                $documentoActual = '';
            @endphp

            @forelse($reportes as $r)
                @php
                    $isNewVenta = $ventaActual === null || $r->venta_id !== $ventaActual;

                    if ($isNewVenta && $ventaActual !== null) {
                        @endphp
                        <tr class="subtotal">
                            <td colspan="6" class="text-right">SUBTOTAL {{ $documentoActual }}:</td>
                            <td class="text-right">{{ number_format($subImporte, 2, '.', '') }}</td>
                            <td class="text-right"></td>
                            <td class="text-right">{{ number_format($subValor, 2, '.', '') }}</td>
                            <td class="text-right">{{ number_format($subRentabilidad, 2, '.', '') }}</td>
                            <td class="text-right">{{ $subImporte > 0 ? number_format(($subRentabilidad / $subImporte) * 100, 2, '.', '') : '0.00' }}%</td>
                        </tr>
                        @php
                        $subImporte = 0.0;
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
                    $subValor += $valor;
                    $subRentabilidad += $rentabilidad;

                    $totalImporte += $importe;
                    $totalValor += $valor;
                    $totalRentabilidad += $rentabilidad;
                @endphp

                <tr>
                    <td>{{ \Carbon\Carbon::parse($r->fecha_venta)->format('d/m/Y') }}</td>
                    <td>{{ $r->documento }}</td>
                    <td>{{ $r->producto_nombre }}</td>
                    <td>{{ $r->linea_nombre ?? '-' }}</td>
                    <td class="text-right">{{ number_format($cantidad, 2, '.', '') }}</td>
                    <td class="text-right">{{ number_format($precio, 4, '.', '') }}</td>
                    <td class="text-right">{{ number_format($importe, 2, '.', '') }}</td>
                    <td class="text-right">{{ number_format($costo, 4, '.', '') }}</td>
                    <td class="text-right">{{ number_format($valor, 2, '.', '') }}</td>
                    <td class="text-right">{{ number_format($rentabilidad, 2, '.', '') }}</td>
                    <td class="text-right">{{ $importe > 0 ? number_format(($rentabilidad / $importe) * 100, 2, '.', '') : '0.00' }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse

            @if(count($reportes) > 0)
                <tr class="subtotal">
                    <td colspan="6" class="text-right">SUBTOTAL {{ $documentoActual }}:</td>
                    <td class="text-right">{{ number_format($subImporte, 2, '.', '') }}</td>
                    <td class="text-right"></td>
                    <td class="text-right">{{ number_format($subValor, 2, '.', '') }}</td>
                    <td class="text-right">{{ number_format($subRentabilidad, 2, '.', '') }}</td>
                    <td class="text-right">{{ $subImporte > 0 ? number_format(($subRentabilidad / $subImporte) * 100, 2, '.', '') : '0.00' }}%</td>
                </tr>
            @endif
        </tbody>

        @if(count($reportes) > 0)
            <tfoot>
                <tr class="total">
                    <td colspan="6" class="text-right">TOTAL GENERAL:</td>
                    <td class="text-right">{{ number_format($totalImporte, 2, '.', '') }}</td>
                    <td class="text-right"></td>
                    <td class="text-right">{{ number_format($totalValor, 2, '.', '') }}</td>
                    <td class="text-right">{{ number_format($totalRentabilidad, 2, '.', '') }}</td>
                    <td class="text-right">{{ $totalImporte > 0 ? number_format(($totalRentabilidad / $totalImporte) * 100, 2, '.', '') : '0.00' }}%</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        <p>Generado: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
