<!DOCTYPE html>
<html>

<head>
    <title>Formulaciones {{ $numeroInicial }} al {{ $numeroFinal }}</title>
    <meta charset="utf-8">

    <style>
        body {
            font-family: Courier, monospace;
            font-size: 11px;
            margin: 0;
            padding: 0;
            color: #000;
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

        .page-header td {
            padding: 2px 0;
        }

        .text-right { text-align: right; }
        .text-end   { text-align: right; }

        .header-separator {
            border-bottom: 1px solid #000;
            margin: 5px 0 10px 0;
        }

        .page-number:before {
            content: "Página " counter(page);
        }

        table.formulacion-header,
        table.formulacion-detalle {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        table.formulacion-header th,
        table.formulacion-header td,
        table.formulacion-detalle th,
        table.formulacion-detalle td {
            padding: 2px 3px;
            border: 1px solid #000;
            background-color: transparent;
        }

        table.formulacion-header .titulo-formulacion {
            font-weight: bold;
            text-align: left;
            border-bottom: 2px solid #000;
        }

        table.formulacion-header .header-cols {
            font-weight: bold;
            border-bottom: 1px solid #000;
        }

        table.formulacion-detalle thead th {
            font-weight: bold;
            border-bottom: 1px solid #000;
        }

        table.formulacion-detalle tfoot td {
            font-weight: bold;
            border-top: 2px solid #000;
        }

        .separador-formulacion {
            border-bottom: 1px dashed #000;
            margin: 10px 0;
            page-break-after: avoid;
        }

        .fw-bold { font-weight: bold; }

        .formulacion-block {
            page-break-inside: avoid;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
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
                    Reporte de Formulaciones - Rango {{ $numeroInicial }} al {{ $numeroFinal }}
                </td>
                <td class="text-right">
                    <small>Usuario: {{ auth()->user()->name }}</small>
                </td>
            </tr>
        </table>
        <div class="header-separator"></div>
    </div>

    <!-- CONTENIDO -->
    @forelse($reportes as $formulacion)

        @php
            $fecha = $formulacion->fecha ? \Carbon\Carbon::parse($formulacion->fecha)->format('d/m/Y') : '';
            $totalSalidaKg = 0;
        @endphp

        <div class="formulacion-block">

            <!-- CABECERA / RESUMEN -->
            <table class="formulacion-header">
                <thead>
                    <tr>
                        <th colspan="4" class="titulo-formulacion">
                            FORMULACIÓN Nº {{ $formulacion->id }} - {{ $fecha }} - Cliente: {{ $formulacion->cliente_nombre }}
                        </th>
                    </tr>
                    <tr class="header-cols">
                        <th>Producto</th>
                        <th>Empaque</th>
                        <th class="text-end">Salida Kg</th>
                        <th class="text-end">Items</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $formulacion->producto_nombre }}</td>
                        <td>{{ $formulacion->producto_empaque }}</td>
                        <td class="text-end">{{ number_format((float)$formulacion->salida_kg, 2) }}</td>
                        <td class="text-end">
                            {{ $formulacion->detalles_count ?? $formulacion->detalles->count() }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- DETALLE -->
            <table class="formulacion-detalle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Empaque</th>
                        <th class="text-end">Salida Kg</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($formulacion->detalles as $detalle)
                        @php
                            $totalSalidaKg += (float) $detalle->salida_kg;
                        @endphp

                        <tr>
                            <td>{{ $detalle->producto_id }}</td>
                            <td>{{ $detalle->producto_nombre }}</td>
                            <td>{{ $detalle->producto_empaque }}</td>
                            <td class="text-end">{{ number_format((float)$detalle->salida_kg, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="3" class="text-end">TOTALES</td>
                        <td class="text-end">{{ number_format((float)$totalSalidaKg, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            @if(!$loop->last)
                <div class="separador-formulacion"></div>
            @endif
        </div>

    @empty
        <p style="text-align: center; margin-top: 50px;">No se encontraron formulaciones en el rango especificado.</p>
    @endforelse

</body>
</html>