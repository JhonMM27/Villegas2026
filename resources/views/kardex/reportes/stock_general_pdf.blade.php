<!DOCTYPE html>
<html>
<head>
    <title>Stock valorizado al corte</title>
    <meta charset="utf-8">

    <style>
        body {
            font-family: Courier, monospace;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }

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
            font-size: 11px;
            padding: 5px 20px;
        }

        .page-header table {
            width: 100%;
            border-collapse: collapse;
        }

        .page-header td { padding: 2px 0; }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }

        .header-separator {
            border-bottom: 1px solid #000;
            margin: 5px 0 10px 0;
        }

        .page-number:before {
            content: "Página " counter(page);
        }

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

        .linea2 { border-bottom: 0.5px dashed #000; }

        .subtotal-row {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .total-row {
            font-weight: bold;
            border-top: 2px solid #000;
        }
    </style>
</head>

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
                    Reporte: Stock valorizado al corte ({{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }})
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
                <th>Código</th>
                <th>Producto</th>
                <th class="text-right">Empaque</th>
                <th>Línea</th>
                <th class="text-right">Stock</th>
                <th class="text-right">Costo Unit.</th>
                <th class="text-right">Valor total</th>
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

                {{-- Subtotal cuando cambia la línea --}}
                @if($lineaActual !== null && $linea !== $lineaActual)
                    <tr class="subtotal-row">
                        <td colspan="4" class="text-right">SUBTOTAL {{ $lineaActual }}:</td>
                        <td class="text-right">{{ number_format($subStock, 2) }}</td>
                        <td></td>
                        <td class="text-right">{{ number_format($subValor, 2) }}</td>
                    </tr>

                    @php
                        $subStock = 0;
                        $subValor = 0;
                    @endphp
                @endif

                @php
                    // set / update línea actual
                    if ($lineaActual === null) $lineaActual = $linea;
                    if ($linea !== $lineaActual) $lineaActual = $linea;

                    // acumular
                    $subStock += $stock;
                    $subValor += $valor;

                    $totalStock += $stock;
                    $totalValor += $valor;
                @endphp

                <tr class="linea2">
                    <td>{{ $r->producto_id }}</td>
                    <td>{{ $r->producto }}</td>
                    <td class="text-right">{{ number_format((float)($r->empaque ?? 0), 2) }}</td>
                    <td>{{ $linea }}</td>
                    <td class="text-right">{{ number_format($stock, 2) }}</td>
                    <td class="text-right">{{ number_format($costo, 4) }}</td>
                    <td class="text-right">{{ number_format($valor, 2) }}</td>
                </tr>

            @empty
                <tr>
                    <td colspan="7" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse

            {{-- Subtotal final (última línea) --}}
            @if(count($reportes) > 0)
                <tr class="subtotal-row">
                    <td colspan="4" class="text-right">SUBTOTAL {{ $lineaActual }}:</td>
                    <td class="text-right">{{ number_format($subStock, 2) }}</td>
                    <td></td>
                    <td class="text-right">{{ number_format($subValor, 2) }}</td>
                </tr>

                <tr class="total-row">
                    <td colspan="4" class="text-right">TOTAL GENERAL:</td>
                    <td class="text-right">{{ number_format($totalStock, 2) }}</td>
                    <td></td>
                    <td class="text-right">{{ number_format($totalValor, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>