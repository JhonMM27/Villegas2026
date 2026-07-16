<!DOCTYPE html>
<html>
<head>
    <title>Kardex por fechas</title>
    <meta charset="utf-8">

    <style>
        body {
            font-family: Courier, monospace;
            font-size: 10.5px;
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
                    Reporte: Kardex por fechas ({{ $fechaInicio->format('d/m/Y') }} - {{ $fechaFin->format('d/m/Y') }})
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
                <th>Operación</th>
                <th>ID</th>
                <th>Producto</th>
                <th class="text-right">Empaque</th>
                <th>Línea</th>
                <th>Unidad</th>
                <th>Documento</th>

                <th class="text-right">Entrada</th>
                <th class="text-right">Salida</th>
                <th class="text-right">P. Unit</th>

                <th class="text-right">Stock</th>

                <th>Referencia</th>
            </tr>
        </thead>

        <tbody>
            @php
                $operacionesOrden = ['COMPRA', 'VENTA', 'VENTA_ENTREGA', 'PREPARADA', 'PREPARADA_NUCLEO', 'PRESTAMO'];
                $agrupado = $reportes->groupBy('operacion');

                $totalEntrada = 0;
                $totalSalida = 0;
            @endphp

            @foreach($operacionesOrden as $operacion)
                @if($agrupado->has($operacion))
                    @php
                        $grupo = $agrupado->get($operacion);
                        $subEntrada = 0;
                        $subSalida = 0;
                    @endphp

                    @foreach($grupo as $r)
                        @php
                            $entrada = (float)($r->entrada_und ?? 0);
                            $salida  = (float)($r->salida_und ?? 0);
                            $subEntrada += $entrada;
                            $subSalida  += $salida;
                            $totalEntrada += $entrada;
                            $totalSalida  += $salida;
                        @endphp
                        <tr class="linea2">
                            <td>
                                @php $f = $r->fecha ?? null; @endphp
                                {{ $f ? \Carbon\Carbon::parse($f)->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td>{{ $r->operacion ?? '-' }}</td>
                            <td>{{ $r->id ?? '-' }}</td>
                            <td>{{ $r->producto ?? '-' }}</td>

                            <td class="text-right">{{ number_format((float)($r->empaque ?? 0), 2) }}</td>
                            <td>{{ $r->linea ?? '-' }}</td>
                            <td>{{ $r->unidad ?? '-' }}</td>
                            <td>{{ $r->documento ?? '-' }}</td>

                            <td class="text-right">{{ number_format((float)($r->entrada_und ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float)($r->salida_und ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float)($r->precio_unitario ?? 0), 4) }}</td>

                            <td class="text-right">{{ number_format((float)($r->stock_und ?? 0), 2) }}</td>

                            <td>{{ $r->referencia ?? '-' }}</td>
                        </tr>
                    @endforeach

                    <tr class="linea2 fw-bold" style="background-color: #e9ecef;">
                        <td colspan="8">{{ $operacion }} - SUBTOTAL</td>
                        <td class="text-right">{{ number_format($subEntrada, 2) }}</td>
                        <td class="text-right">{{ number_format($subSalida, 2) }}</td>
                        <td colspan="3"></td>
                    </tr>
                @endif
            @endforeach

            @if($reportes->isEmpty())
                <tr>
                    <td colspan="13" class="text-center">No se encontraron registros</td>
                </tr>
            @endif
        </tbody>

        <tfoot>
            <tr class="fw-bold">
                <td colspan="8" class="text-right">TOTALES</td>
                <td class="text-right">{{ number_format($totalEntrada, 2) }}</td>
                <td class="text-right">{{ number_format($totalSalida, 2) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

</body>
</html>