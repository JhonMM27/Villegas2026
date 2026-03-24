<!DOCTYPE html>
<html>

<head>
    <title>Reporte Compras Acumuladas por Producto
                {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
                al
                {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</title>
    <meta charset="utf-8">

    <style>
        body {
            font-family: Courier, monospace;
            font-size: 11.5px;
            margin: 0;
            padding: 0;
        }

        /* ENCABEZADO FIJO EN CADA PÁGINA */
        @page {
            margin-top: 60px;
            margin-bottom: 40px;
            margin-left: 20px;
            margin-right: 20px;
        }

        .page-header {
            position: fixed;
            top: -60px;
            left: 0;
            right: 0;
            height: 50px;
            font-family: Courier, monospace;
            font-size: 11.5px;
            padding: 5px 20px;
        }

        .page-header table {
            width: 100%;
            border-collapse: collapse;
        }

        .page-header td {
            padding: 2px 0;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .header-separator {
            border-bottom: 1px solid #000;
            margin: 5px 0 10px 0;
        }

        /* CONTADOR DE PÁGINAS */
        .page-number:before {
            content: "Página " counter(page);
        }

        /* TABLA DE REPORTE */
        table.reporte {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.reporte th,
        table.reporte td {
            padding: 2px 3px;
            white-space: nowrap;
            overflow: hidden;
        }

        table.reporte th {
            border-bottom: 1px solid #000;
        }

        .linea {
            border-bottom: 1px solid #000;
        }

        .linea2 {
            border-bottom: 0.5px dashed #000;
        }

        .subtotal-row {
            background-color: #f0f0f0;
            font-weight: bold;
            border-top: 1px solid #000;
        }

        .total-row {
            font-weight: bold;
            border-top: 2px solid #000;
        }
    </style>

</head>

<body>

    <!-- ENCABEZADO FIJO (SE REPITE EN CADA PÁGINA) -->
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
                    Reporte compras acumuladas por producto desde 
                    {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
                    al
                    {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
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
                <th>ID</th>
                <th>Empaque</th>
                <th>Producto</th>
                <th>Línea</th>
                <th class="text-right">Kg</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Importe</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalKg = 0;
                $totalCantidad = 0;
                $totalImporte = 0;
                $lineaActual = null;
                $subtotalKg = 0;
                $subtotalCantidad = 0;
                $subtotalImporte = 0;
                $reportesAgrupados = $reportes->sortBy('linea');
            @endphp

            @forelse($reportesAgrupados as $item)
                {{-- Si cambia la línea, mostrar subtotal de la línea anterior --}}
                @if($lineaActual !== null && $lineaActual !== $item->linea)
                <tr class="subtotal-row">
                    <td colspan="4" class="text-right">Subtotal {{ $lineaActual ?? 'Sin línea' }}:</td>
                    <td class="text-right">{{ number_format($subtotalKg, 2) }}</td>
                    <td class="text-right">{{ number_format($subtotalCantidad, 2) }}</td>
                    <td></td>
                    <td class="text-right">{{ number_format($subtotalImporte, 2) }}</td>
                </tr>
                @php
                    $subtotalKg = 0;
                    $subtotalCantidad = 0;
                    $subtotalImporte = 0;
                @endphp
                @endif

                <tr class="linea2">
                    <td>{{ $item->producto_id }}</td>
                    <td class="text-right">{{ $item->producto_empaque }}</td>
                    <td>{{ $item->producto_nombre }}</td>
                    <td>{{ $item->linea ?? '-' }}</td>                
                    <td class="text-right">{{ number_format($item->kg_total, 2) }}</td>
                    <td class="text-right">{{ number_format($item->cantidad_total, 2) }}</td>
                    <td class="text-right">{{ number_format($item->costo_unitario, 4) }}</td>
                    <td class="text-right">{{ number_format($item->importe_total, 2) }}</td>
                </tr>

                @php
                    $subtotalKg += $item->kg_total;
                    $subtotalCantidad += $item->cantidad_total;
                    $subtotalImporte += $item->importe_total;
                    $totalKg += $item->kg_total;
                    $totalCantidad += $item->cantidad_total;
                    $totalImporte += $item->importe_total;
                    $lineaActual = $item->linea;
                @endphp

                {{-- Subtotal de la última línea --}}
                @if($loop->last)
                <tr class="subtotal-row">
                    <td colspan="4" class="text-right">Subtotal {{ $lineaActual ?? 'Sin línea' }}:</td>
                    <td class="text-right">{{ number_format($subtotalKg, 2) }}</td>
                    <td class="text-right">{{ number_format($subtotalCantidad, 2) }}</td>
                    <td></td>
                    <td class="text-right">{{ number_format($subtotalImporte, 2) }}</td>
                </tr>
                @endif

            @empty
            <tr>
                <td colspan="8" class="text-center">No se encontraron registros</td>
            </tr>
            @endforelse
        </tbody>

        @if(count($reportes) > 0)
        <tfoot>
            <tr class="total-row linea">
                <td colspan="4" class="text-right"><strong>TOTAL GENERAL:</strong></td>
                <td class="text-right"><strong>{{ number_format($totalKg, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalCantidad, 2) }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($totalImporte, 2) }}</strong></td>
            </tr>
        </tfoot>
        @endif

    </table>

</body>

</html>