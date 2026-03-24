<!DOCTYPE html>
<html lang="es">
<head>
    <title>Reporte de Clientes</title>
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

        .text-right { text-align: right; }
        .text-center { text-align: center; }

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

        .linea { border-bottom: 1px solid #000; }
        .linea2 { border-bottom: 0.5px dashed #000; }

        /* TOTAL GENERAL */
        .total-row {
            font-weight: bold;
            border-top: 2px solid #000;
        }

        /* Para que columnas largas no rompan todo */
        .wrap {
            white-space: normal;
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
                <td>Reporte de Clientes</td>
                <td class="text-right">
                    <small>Usuario: {{ auth()->user()->name ?? '---' }}</small>
                </td>
            </tr>
        </table>
        <div class="header-separator"></div>
    </div>

    @php
        $totalRegistros = $reportes->count();
    @endphp

    <!-- TABLA -->
    <table class="reporte">
        <thead>
            <tr>
                <th class="text-right">ID</th>
                <th>Tipo Doc</th>
                <th>N° Doc</th>
                <th>Razón Social</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Email</th>
            </tr>
        </thead>

        <tbody>
            @forelse($reportes as $item)
                <tr class="linea2">
                    <td class="text-right">{{ $item->id }}</td>

                    {{-- Si tienes relación documentoTipo, muestra su descripción; si no, muestra el código --}}
                    <td>
                        {{ $item->documentoTipo->descripcion ?? $item->documento_tipo_codigo }}
                    </td>

                    <td>{{ $item->documento_numero }}</td>

                    <td class="wrap">{{ $item->razon_social }}</td>
                    <td class="wrap">{{ $item->direccion }}</td>

                    <td>{{ $item->telefono }}</td>
                    <td class="wrap">{{ $item->email }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse
        </tbody>

        @if($totalRegistros > 0)
            <tfoot>
                <tr class="total-row linea">
                    <td colspan="6" class="text-right"><strong>TOTAL REGISTROS:</strong></td>
                    <td class="text-right"><strong>{{ $totalRegistros }}</strong></td>
                </tr>
            </tfoot>
        @endif

    </table>

</body>
</html>