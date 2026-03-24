<!DOCTYPE html>
<html>
@include('pdf.styles', [
    'title' => "Reporte: Compras Agrupadas producto ({$ini->format('d/m/Y')} al {$fin->format('d/m/Y')})"
        . (!empty($proveedorNombre) ? " - Proveedor: {$proveedorNombre}" : '')
])
<body>

    <!-- ENCABEZADO FIJO -->
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
                    Reporte: Compras agrupadas producto ({{ $ini->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }})
                </td>
                <td class="text-right">
                    <small>Usuario: {{ auth()->user()->name }}</small>
                </td>
            </tr>
            @if($proveedorNombre)
                <tr>
                    <td colspan="2">
                        Proveedor: <strong>{{ $proveedorNombre }}</strong>
                    </td>
                </tr>
            @endif
        </table>
        <div class="header-separator"></div>
    </div>

    <!-- TABLA -->
    <table class="reporte">
        <thead>
            <tr>
                <th>Fecha Compra</th>
                <th>Documento</th>
                <th>Descripción</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Kg</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Importe</th>
            </tr>
        </thead>

        <tbody>
            @php
                // Totales generales
                $totCantidad = 0;
                $totKg = 0;
                $totImporte = 0;

                // Control de agrupación por producto
                $productoActual = null;

                // Subtotales por producto
                $subCantidad = 0;
                $subKg = 0;
                $subImporte = 0;
            @endphp

            @forelse($reportes as $r)

                @php
                    $producto = $r->producto_nombre; // o $r->producto_id si prefieres

                    $cantidad = (float)($r->cantidad_convertida ?? 0);
                    $kg = (float)($r->kg_detalle ?? 0);
                    $precio = (float)($r->costo_unitario ?? 0);
                    $importe = (float)($r->total ?? 0);
                @endphp

                {{-- Si cambia el producto, imprimimos subtotal del producto anterior --}}
                @if($productoActual !== null && $productoActual !== $producto)
                    <tr class="total-row">
                        <td colspan="3" class="text-right">
                            SUBTOTAL: {{ $productoActual }}
                        </td>
                        <td class="text-right">{{ number_format($subCantidad, 2) }}</td>
                        <td class="text-right">{{ number_format($subKg, 2) }}</td>
                        <td></td>
                        <td class="text-right">{{ number_format($subImporte, 2) }}</td>
                    </tr>

                    @php
                        // Reiniciar subtotales
                        $subCantidad = 0;
                        $subKg = 0;
                        $subImporte = 0;
                    @endphp
                @endif

                {{-- Si es un producto nuevo, actualizamos el "productoActual" --}}
                @php
                    if ($productoActual === null || $productoActual !== $producto) {
                        $productoActual = $producto;
                    }

                    // Acumular subtotales
                    $subCantidad += $cantidad;
                    $subKg += $kg;
                    $subImporte += $importe;

                    // Acumular totales generales
                    $totCantidad += $cantidad;
                    $totKg += $kg;
                    $totImporte += $importe;
                @endphp

                <tr class="linea2">
                    {{-- Si ya tienes fecha_compra_fmt en el backend, usa esa y evitas Carbon --}}
                    {{-- <td>{{ $r->fecha_compra_fmt }}</td> --}}
                    <td>{{ \Carbon\Carbon::parse($r->fecha_compra)->format('d/m/Y') }}</td>

                    <td>{{ $r->documento }}</td>
                    <td>{{ $r->producto_nombre }}</td>
                    <td class="text-right">{{ number_format($cantidad, 2) }}</td>
                    <td class="text-right">{{ number_format($kg, 2) }}</td>
                    <td class="text-right">{{ number_format($precio, 4) }}</td>
                    <td class="text-right">{{ number_format($importe, 2) }}</td>
                </tr>

            @empty
                <tr>
                    <td colspan="7" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse

            {{-- Subtotal del último producto --}}
            @if($productoActual !== null)
                <tr class="total-row">
                    <td colspan="3" class="text-right">
                        SUBTOTAL: {{ $productoActual }}
                    </td>
                    <td class="text-right">{{ number_format($subCantidad, 2) }}</td>
                    <td class="text-right">{{ number_format($subKg, 2) }}</td>
                    <td></td>
                    <td class="text-right">{{ number_format($subImporte, 2) }}</td>
                </tr>
            @endif

            {{-- Total general --}}
            @if(count($reportes) > 0)
                <tr class="total-row">
                    <td colspan="3" class="text-right">TOTAL GENERAL:</td>
                    <td class="text-right">{{ number_format($totCantidad, 2) }}</td>
                    <td class="text-right">{{ number_format($totKg, 2) }}</td>
                    <td></td>
                    <td class="text-right">{{ number_format($totImporte, 2) }}</td>
                </tr>
            @endif

        </tbody>
    </table>

</body>
</html>