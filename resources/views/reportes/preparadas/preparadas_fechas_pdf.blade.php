<!DOCTYPE html>
<html>

<head>
    <title>Reporte de Preparadas por Fechas
                {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
                al
                {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</title>
    <meta charset="utf-8">

    <style>
        body {
            font-family: Courier, monospace;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }

        /* 👇 ENCABEZADO FIJO EN CADA PÁGINA */
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

        .page-header td {
            padding: 2px 0;
        }

        .text-right {
            text-align: right;
        }

        .header-separator {
            border-bottom: 1px solid #000;
            margin: 5px 0 10px 0;
        }

        /* 👇 TABLA DE REPORTE */
        table.reporte {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
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
            border-top: 3px double #000 !important;
        }

        .linea-total {
            border-top: 2px solid #000 !important;
            border-bottom: 2px solid #000 !important;
        }

        /* Permitir que columnas de texto usen más ancho */
        table.reporte td:nth-child(3),
        table.reporte th:nth-child(3),
        table.reporte td:nth-child(5),
        table.reporte th:nth-child(5) {
            white-space: normal;
            overflow-wrap: break-word;
        }

        .fw-bold {
            font-weight: bold;
        }

        /* 👇 CONTADOR DE PÁGINAS */
        .page-number:before {
            content: "Página " counter(page);
        }
    </style>

</head>

<body>

    <!-- ================= ENCABEZADO FIJO (SE REPITE EN CADA PÁGINA) ================= -->
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
                    Reporte de Preparadas por Fechas
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

    <!-- ================= TABLA ================= -->
    <table class="reporte">
        <thead>
            <tr>
                <th>Número</th>
                <th>Fecha</th>
                <th>Item</th>
                <th>Fórmula</th>
                <th>Preparada</th>
                <th>Empaque</th>
                <th class="text-right">Total Kg</th>
                <th class="text-right">Total Sacos</th>
                <th class="text-right">Total Soles</th>
            </tr>
        </thead>

        <tbody>
            @php
                $productoActual = null;

                $subIngKg = 0;
                $subIngSaco = 0;
                $subIngSol = 0;

                $totIngKg = 0;
                $totIngSaco = 0;
                $totIngSol = 0;
            @endphp

            @forelse($reportes as $row)
                {{-- CAMBIO DE PRODUCTO --}}
                @if($productoActual !== null && $productoActual !== $row->producto_nombre)
                    <tr class="fw-bold linea">
                        <td colspan="6" class="text-right">TOTAL {{ $productoActual }}</td>
                        <td class="text-right">{{ number_format($subIngKg, 2) }}</td>
                        <td class="text-right">{{ number_format($subIngSaco, 4) }}</td>
                        <td class="text-right">{{ number_format($subIngSol, 4) }}</td>
                    </tr>
                    @php
                        $subIngKg = $subIngSaco = $subIngSol = 0;
                    @endphp
                @endif

                {{-- FILA NORMAL --}}
                <tr>
                    <td>{{ $row->id }}</td>
                    <td>{{ \Carbon\Carbon::parse($row->fecha)->format('d/m/Y') }}</td>
                    <td>{{ $row->items }}</td>
                    <td>{{ $row->formulacion_id }}</td>
                    <td>{{ $row->producto_nombre }}</td>
                    <td>{{ $row->producto_empaque }}</td>
                    <td class="text-right">{{ number_format($row->ingreso_kg, 2) }}</td>
                    <td class="text-right">{{ number_format($row->ingreso_saco, 4) }}</td>
                    <td class="text-right">{{ number_format($row->ingreso_soles, 4) }}</td>
                </tr>

                @php
                    $productoActual = $row->producto_nombre;

                    $subIngKg += $row->ingreso_kg;
                    $subIngSaco += $row->ingreso_saco;
                    $subIngSol += $row->ingreso_soles;

                    $totIngKg += $row->ingreso_kg;
                    $totIngSaco += $row->ingreso_saco;
                    $totIngSol += $row->ingreso_soles;
                @endphp

            @empty
                <tr>
                    <td colspan="11" style="text-align: center;">No se encontraron registros</td>
                </tr>
            @endforelse

            @if(count($reportes) > 0)
                {{-- ÚLTIMO SUBTOTAL --}}
                <tr class="fw-bold linea">
                    <td colspan="6" class="text-right">TOTAL {{ $productoActual }}</td>
                    <td class="text-right">{{ number_format($subIngKg, 2) }}</td>
                    <td class="text-right">{{ number_format($subIngSaco, 4) }}</td>
                    <td class="text-right">{{ number_format($subIngSol, 4) }}</td>
                </tr>
            @endif
        </tbody>

        @if(count($reportes) > 0)
        <tfoot>
            <tr class="fw-bold linea-total">
                <td colspan="6" class="text-right">TOTAL GENERAL</td>
                <td class="text-right">{{ number_format($totIngKg, 2) }}</td>
                <td class="text-right">{{ number_format($totIngSaco, 4) }}</td>
                <td class="text-right">{{ number_format($totIngSol, 4) }}</td>
            </tr>
        </tfoot>
        @endif

    </table>

</body>

</html>