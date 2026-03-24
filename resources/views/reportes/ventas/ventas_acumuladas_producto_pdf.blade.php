<!DOCTYPE html>
<html>
@include('pdf.styles', [
    'title' => "Reporte: Ventas Acumuladas por Producto ({$ini->format('d/m/Y')} al {$fin->format('d/m/Y')})"
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
                    Reporte: Ventas acumuladas por producto 
                    ({{ $ini->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }})
                </td>
                <td class="text-right">
                    <small>Usuario: {{ auth()->user()->name }}</small>
                </td>
            </tr>
        </table>
        <div class="header-separator"></div>
    </div>

    <!-- TABLA -->
    <table class="reporte">
        <thead>
            <tr>
                <th>Empaque</th>
                <th>Producto</th>
                <th class="text-right">Cantidad Total</th>
                <th class="text-right">Kg Total</th>
                <th class="text-right">Precio Prom.</th>
                <th class="text-right">Importe Total</th>
            </tr>
        </thead>

        <tbody>
            @forelse($reportes as $r)
                <tr class="linea2">
                    <td class="text-end">{{ number_format($r->empaque_producto, 2) }}</td>
                    <td>{{ $r->producto_nombre }}</td>

                    <td class="text-right">
                        {{ number_format((float)$r->cantidad_total, 2) }}
                    </td>

                    <td class="text-right">
                        {{ number_format((float)$r->kg_total, 2) }}
                    </td>

                    <td class="text-right">
                        {{ number_format((float)$r->precio_promedio, 4) }}
                    </td>

                    <td class="text-right">
                        {{ number_format((float)$r->importe_total, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">
                        No se encontraron registros en el rango seleccionado
                    </td>
                </tr>
            @endforelse

            @if(count($reportes) > 0)
                <tr class="total-row">
                    <td class="text-right"><strong>TOTAL GENERAL:</strong></td>
                    <td class="text-right">
                        <strong>{{ number_format($totCantidad, 2) }}</strong>
                    </td>
                    <td class="text-right">
                        <strong>{{ number_format($totKg, 2) }}</strong>
                    </td>
                    <td></td>
                    <td class="text-right">
                        <strong>{{ number_format($totImporte, 2) }}</strong>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>