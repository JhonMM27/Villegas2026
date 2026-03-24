<!DOCTYPE html>
<html>

<head>
    <title>Compras por Producto
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

        /* SUBTOTAL PRODUCTO */
        .subtotal-producto {
            background-color: #f0f0f0;
            font-weight: bold;
            border-top: 1px solid #000;
        }

        /* TOTAL GENERAL */
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
                    Compras Agrupadas por Producto
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
                <th>Prod ID</th>
                <th>Producto</th>
                <th>Fecha</th>
                <th class="text-right">Kg</th>
                <th class="text-right">Cant</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Total</th>
                <th>Doc</th>
                <th>Serie</th>
                <th>Corr</th>
                <th>Proveedor</th>
            </tr>
        </thead>
        <tbody>
            @php
                $productoActual = null;
                $subtotalKg = 0;
                $subtotalCantidad = 0;
                $subtotalTotal = 0;
                $contadorCompras = 0;

                $totalKg = 0;
                $totalCantidad = 0;
                $totalGeneral = 0;
                $totalCompras = 0;

                $reportesOrdenados = $reportes->sortBy('producto_nombre');
            @endphp

            @forelse($reportesOrdenados as $item)
                {{-- Subtotal por producto --}}
                @if($productoActual !== null && $productoActual !== $item->producto_nombre)
                    <tr class="subtotal-producto">
                        <td colspan="3" class="text-right">Subtotal {{ $productoActual }} ({{ $contadorCompras }} compras):</td>
                        <td class="text-right">{{ number_format($subtotalKg, 2) }}</td>
                        <td class="text-right">{{ number_format($subtotalCantidad, 2) }}</td>
                        <td></td>
                        <td class="text-right">{{ number_format($subtotalTotal, 2) }}</td>
                        <td colspan="4"></td>
                    </tr>
                    @php
                        $subtotalKg = 0;
                        $subtotalCantidad = 0;
                        $subtotalTotal = 0;
                        $contadorCompras = 0;
                    @endphp
                @endif

                <tr class="linea2">
                    <td>{{ $item->producto_id }}</td>
                    <td>{{ $item->producto_nombre }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->fecha_compra)->format('d/m/Y') }}</td>
                    <td class="text-right">{{ number_format($item->kg_total, 2) }}</td>
                    <td class="text-right">{{ number_format($item->cantidad, 2) }}</td>
                    <td class="text-right">{{ number_format($item->precio, 4) }}</td>
                    <td class="text-right">{{ number_format($item->total, 2) }}</td>
                    <td>{{ $item->comprobante_tipo_codigo }}</td>
                    <td>{{ $item->serie }}</td>
                    <td>{{ $item->correlativo }}</td>
                    <td>{{ $item->proveedor_nombre ?? '-' }}</td>
                </tr>

                @php
                    $subtotalKg += $item->kg_total;
                    $subtotalCantidad += $item->cantidad;
                    $subtotalTotal += $item->total;
                    $contadorCompras++;

                    $totalKg += $item->kg_total;
                    $totalCantidad += $item->cantidad;
                    $totalGeneral += $item->total;
                    $totalCompras++;

                    $productoActual = $item->producto_nombre;
                @endphp

                {{-- Subtotal última fila --}}
                @if($loop->last)
                    <tr class="subtotal-producto">
                        <td colspan="3" class="text-right">Subtotal {{ $productoActual }} ({{ $contadorCompras }} compras):</td>
                        <td class="text-right">{{ number_format($subtotalKg, 2) }}</td>
                        <td class="text-right">{{ number_format($subtotalCantidad, 2) }}</td>
                        <td></td>
                        <td class="text-right">{{ number_format($subtotalTotal, 2) }}</td>
                        <td colspan="4"></td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="11" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($reportes) > 0)
        <tfoot>
            <tr class="total-row linea">
                <td colspan="3" class="text-right"><strong>TOTAL GENERAL ({{ $totalCompras }} compras):</strong></td>
                <td class="text-right"><strong>{{ number_format($totalKg, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalCantidad, 2) }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($totalGeneral, 2) }}</strong></td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
        @endif
    </table>

</body>

</html>