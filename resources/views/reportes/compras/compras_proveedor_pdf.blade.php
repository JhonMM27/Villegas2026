<!DOCTYPE html>
<html>

<head>
    <title>Compras por Proveedor
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

        /* SUBTOTAL PROVEEDOR */
        .subtotal-proveedor {
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
                    Compras Agrupadas por Proveedor
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
                <th>Fecha</th>
                <th>Doc</th>
                <th>Serie</th>
                <th>Corr</th>
                <th>F.Pago</th>
                <th class="text-right">Ítems</th>
                <th class="text-right">Total</th>
                <th>ID Prov</th>
                <th>Razón Social</th>
            </tr>
        </thead>

        <tbody>

            @php
                // Agrupar por proveedor y ordenar alfabéticamente por razón social
                $reportesAgrupados = $reportes->groupBy('proveedor_id')
                    ->sortBy(fn($grupo) => strtolower($grupo->first()->razon_social));

                $totalGeneral = 0;
                $totalItemsGeneral = 0;
            @endphp

            @forelse($reportesAgrupados as $proveedorId => $comprasProveedor)

                @php
                    $subtotalProveedor = 0;
                    $subtotalItemsProveedor = 0;
                    $razonSocial = $comprasProveedor->first()->razon_social;
                @endphp

                @foreach($comprasProveedor as $item)

                    @php
                        $fechaCompra = \Carbon\Carbon::parse($item->fecha_compra)->format('d/m/Y');
                    @endphp

                    {{-- FILA NORMAL --}}
                    <tr class="linea2">
                        <td>{{ $fechaCompra }}</td>
                        <td>{{ $item->comprobante_tipo_codigo }}</td>
                        <td>{{ $item->serie }}</td>
                        <td>{{ $item->correlativo }}</td>
                        <td>{{ $item->pago_forma_codigo }}</td>

                        <td class="text-right">{{ $item->total_items }}</td>
                        <td class="text-right">{{ number_format($item->total, 2) }}</td>

                        <td class="text-right">{{ $item->proveedor_id }}</td>
                        <td>{{ $item->razon_social }}</td>
                    </tr>

                    @php
                        $subtotalProveedor += $item->total;
                        $subtotalItemsProveedor += $item->total_items;

                        $totalGeneral += $item->total;
                        $totalItemsGeneral += $item->total_items;
                    @endphp

                @endforeach

                {{-- SUBTOTAL POR PROVEEDOR --}}
                <tr class="subtotal-proveedor">
                    <td colspan="5" class="text-right">Subtotal {{ $razonSocial }}</td>
                    <td class="text-right">{{ $subtotalItemsProveedor }}</td>
                    <td class="text-right">{{ number_format($subtotalProveedor, 2) }}</td>
                    <td colspan="2"></td>
                </tr>

            @empty

                <tr>
                    <td colspan="9" class="text-center">No se encontraron registros</td>
                </tr>

            @endforelse
        </tbody>

        @if(count($reportes) > 0)
        <tfoot>
            <tr class="total-row linea">
                <td colspan="5" class="text-right"><strong>TOTAL GENERAL:</strong></td>
                <td class="text-right"><strong>{{ $totalItemsGeneral }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalGeneral, 2) }}</strong></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
        @endif

    </table>

</body>

</html>