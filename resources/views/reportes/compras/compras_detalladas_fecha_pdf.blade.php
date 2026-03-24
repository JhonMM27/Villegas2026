<!DOCTYPE html>
<html>

<head>
    <title>Compras Detalladas desde
                {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
                al
                {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</title>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>

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

        /* SUBTOTAL DÍA */
        .subtotal-dia {
            background-color: #f0f0f0;
            font-weight: bold;
            border-top: 1px solid #000;
        }

        .linea-separacion {
            border-top: 2px solid #000;
            height: 2px;
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
                Compras Detalladas desde {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
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
                <th>Fecha</th>
                <th>Doc</th>
                <th>Serie</th>
                <th>Corr</th>
                <th>Producto</th>
                <th class="text-right">Ing Kg</th>
                <th class="text-right">Cant</th>
                <th class="text-right">Costo Unitario</th>
                <th class="text-right">Importe</th>
                <th>Proveedor</th>
            </tr>
        </thead>

        <tbody>
            @php
                $fechaActual = null;

                $subIng = 0;
                $subCant = 0;
                $subImporte = 0;
                $subCount = 0;

                $totalIng = 0;
                $totalCant = 0;
                $totalImporte = 0;
                $totalCount = 0;

                $items = $reportes->sortBy('fecha_compra');
            @endphp

            @foreach($items as $item)

                {{-- Cuando cambia la fecha -> mostramos subtotales --}}
                @if($fechaActual && $fechaActual != $item->fecha_compra)
                    <tr class="subtotal-dia">
                        <td class="text-center">{{ $subCount }}</td>
                        <td colspan="4"></td>
                        <td class="text-right">{{ number_format($subIng, 2) }}</td>
                        <td class="text-right">{{ number_format($subCant, 2) }}</td>
                        <td></td>
                        <td class="text-right">{{ number_format($subImporte, 2) }}</td>
                        <td></td>
                    </tr>

                    <tr class="linea-separacion">
                        <td colspan="10"></td>
                    </tr>

                    @php
                        $subIng = 0;
                        $subCant = 0;
                        $subImporte = 0;
                        $subCount = 0;
                    @endphp
                @endif

                @php $fechaActual = $item->fecha_compra; @endphp

                {{-- Fila normal --}}
                <tr class="linea2">
                    <td>{{ \Carbon\Carbon::parse($item->fecha_compra)->format('d/m/Y') }}</td>
                    <td>{{ $item->comprobante_tipo_codigo }}</td>
                    <td>{{ $item->serie }}</td>
                    <td>{{ $item->correlativo }}</td>
                    <td>{{ $item->producto_nombre }}</td>
                    <td class="text-right">{{ number_format($item->ing_kg, 2) }}</td>
                    <td class="text-right">{{ number_format($item->cantidad, 2) }}</td>
                    <td class="text-right">{{ number_format($item->p_lista, 4) }}</td>
                    <td class="text-right">{{ number_format($item->importe, 2) }}</td>
                    <td>{{ $item->proveedor_nombre }}</td>
                </tr>

                {{-- Acumuladores --}}
                @php
                    $subIng += $item->ing_kg;
                    $subCant += $item->cantidad;
                    $subImporte += $item->importe;
                    $subCount++;

                    $totalIng += $item->ing_kg;
                    $totalCant += $item->cantidad;
                    $totalImporte += $item->importe;
                    $totalCount++;
                @endphp

                {{-- Última fila -> imprimir subtotal final --}}
                @if($loop->last)
                    <tr class="subtotal-dia">
                        <td class="text-center">{{ $subCount }}</td>
                        <td colspan="4"></td>
                        <td class="text-right">{{ number_format($subIng, 2) }}</td>
                        <td class="text-right">{{ number_format($subCant, 2) }}</td>
                        <td></td>
                        <td class="text-right">{{ number_format($subImporte, 2) }}</td>
                        <td></td>
                    </tr>
                @endif

            @endforeach
        </tbody>

        {{-- TOTAL GENERAL --}}
        @if(count($items) > 0)
            <tfoot>
                <tr class="total-row linea">
                    <td colspan="5" class="text-right"><strong>TOTAL GENERAL ({{ $totalCount }} ítems):</strong></td>
                    <td class="text-right"><strong>{{ number_format($totalIng, 2) }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($totalCant, 2) }}</strong></td>
                    <td></td>
                    <td class="text-right"><strong>{{ number_format($totalImporte, 2) }}</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        @endif

    </table>

</body>

</html>