<!DOCTYPE html>
<html>

<head>
    <title>
        Formulaciones por Fecha
        {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
        al
        {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
    </title>
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

        .text-right { text-align: right; }
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

        .linea { border-bottom: 1px solid #000; }
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
                    Formulaciones por Fecha
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
                <th>Fecha</th>
                <th>Items</th>
                <th class="text-right">Sal Kg</th>
                <th>Producto</th>
                <th>Emp</th>
                <th>Cliente</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalRegistros = 0;
                $totalItems = 0;
                $totalSalKg = 0;
            @endphp

            @forelse($reportes as $r)
                <tr class="linea2">
                    <td>{{ $r->id }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->fecha)->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $r->detalles_count }}</td>
                    <td class="text-right">{{ number_format((float)$r->salida_kg, 2) }}</td>
                    <td>{{ $r->producto_nombre }}</td>
                    <td>{{ $r->producto_empaque }}</td>
                    <td>{{ $r->cliente_nombre }}</td>
                </tr>

                @php
                    $totalRegistros++;
                    $totalItems += (int) $r->detalles_count;
                    $totalSalKg += (float) $r->salida_kg;
                @endphp
            @empty
                <tr>
                    <td colspan="7" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse
        </tbody>

        @if(count($reportes) > 0)
        <tfoot>
            <tr class="linea">
                <td><strong>{{ $totalRegistros }}</strong></td>
                <td class="text-right"><strong>TOTALES</strong></td>
                <td class="text-center"><strong>{{ $totalItems }}</strong></td>
                <td class="text-right"><strong>{{ number_format((float)$totalSalKg, 2) }}</strong></td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif

    </table>

</body>

</html>