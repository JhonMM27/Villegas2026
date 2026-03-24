<!DOCTYPE html>
<html>

<head>
    <title>Ventas {{$nomDoc}} Emitidos
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

        /* SUBTOTAL DÍA */
        .subtotal-dia {
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
                    Ventas {{$nomDoc}} Emitidos
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
                <th class="text-right">Total</th>
                <th class="text-right">A Cuenta</th>
                <th class="text-right">Items</th>
                <th>F.Pago</th>
                <th>ID Cli</th>
                <th>Razón Social</th>
            </tr>
        </thead>

        <tbody>

            @php
                $fechaActual = null;
                $subtotalDia = 0;
                $subtotalAcuenta = 0;
                $subtotalItems = 0;

                $totalGeneral = 0;
                $totalAcuenta = 0;
                $totalItems = 0;
            @endphp

            @forelse($reportes as $item)

                @php
                    $fechaVenta = \Carbon\Carbon::parse($item->fecha_venta)->format('Y-m-d');
                @endphp

                {{-- SI CAMBIA LA FECHA → SUBTOTAL --}}
                @if($fechaActual !== null && $fechaActual !== $fechaVenta)
                    <tr class="subtotal-dia">
                        <td colspan="4" class="text-right">Subtotal {{ \Carbon\Carbon::parse($fechaActual)->format('d/m/Y') }}</td>
                        <td class="text-right">{{ number_format($subtotalDia, 2) }}</td>
                        <td class="text-right">{{ number_format($subtotalAcuenta, 2) }}</td>
                        <td class="text-right">{{ $subtotalItems }}</td>
                        <td colspan="3"></td>
                    </tr>

                    @php
                        $subtotalDia = 0;
                        $subtotalAcuenta = 0;
                        $subtotalItems = 0;
                    @endphp
                @endif

                {{-- FILA NORMAL --}}
                <tr class="linea2">
                    <td>{{ \Carbon\Carbon::parse($fechaVenta)->format('d/m/Y') }}</td>
                    <td>{{ $item->comprobante_tipo_codigo }}</td>
                    <td>{{ $item->serie }}</td>
                    <td>{{ $item->correlativo }}</td>
                    <td class="text-right">{{ number_format($item->total, 2) }}</td>
                    <td class="text-right">{{ number_format($item->acuenta, 2) }}</td>
                    <td class="text-right">{{ $item->items }}</td>
                    <td>{{ $item->pago_forma_nombre }}</td>
                    <td class="text-right">{{ $item->cliente_id }}</td>
                    <td>{{ $item->cliente_nombre }}</td>
                </tr>

                @php
                    $subtotalDia += $item->total;
                    $subtotalAcuenta += $item->acuenta;
                    $subtotalItems += $item->items;

                    $totalGeneral += $item->total;
                    $totalAcuenta += $item->acuenta;
                    $totalItems += $item->items;

                    $fechaActual = $fechaVenta;
                @endphp

                {{-- SUBTOTAL ÚLTIMA FILA --}}
                @if($loop->last)
                    <tr class="subtotal-dia">
                        <td colspan="4" class="text-right">Subtotal {{ \Carbon\Carbon::parse($fechaActual)->format('d/m/Y') }}</td>
                        <td class="text-right">{{ number_format($subtotalDia, 2) }}</td>
                        <td class="text-right">{{ number_format($subtotalAcuenta, 2) }}</td>
                        <td class="text-right">{{ $subtotalItems }}</td>
                        <td colspan="3"></td>
                    </tr>
                @endif

            @empty

                <tr>
                    <td colspan="10" class="text-center">No se encontraron registros</td>
                </tr>

            @endforelse
        </tbody>

        @if(count($reportes) > 0)
        <tfoot>
            <tr class="total-row linea">
                <td colspan="4" class="text-right"><strong>TOTAL GENERAL:</strong></td>
                <td class="text-right"><strong>{{ number_format($totalGeneral, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalAcuenta, 2) }}</strong></td>
                <td class="text-right"><strong>{{ $totalItems }}</strong></td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif

    </table>

</body>

</html>