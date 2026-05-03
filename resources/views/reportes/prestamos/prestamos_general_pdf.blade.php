<!DOCTYPE html>
<html>

<head>
    <title>Reporte General de Préstamos
        @if($fechaInicio && $fechaFin)
            {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
            al
            {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
        @endif
    </title>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>

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

        .linea {
            border-bottom: 1px solid #000;
        }

        .linea2 {
            border-bottom: 0.5px dashed #000;
        }

        .tipo-pa {
            color: #000080;
        }

        .tipo-pd {
            color: #8B4513;
        }
    </style>

</head>

<body>

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
                    Reporte General de Préstamos
                    @if($fechaInicio && $fechaFin)
                        {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
                        al
                        {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
                        @if($movimientoTipo && $movimientoTipo !== 'ALL')
                            - Tipo: {{ $movimientoTipo === 'PA' ? 'Otorgados (A)' : 'Recibidos (DE)' }}
                        @endif
                    @endif
                </td>
                <td class="text-right">
                    <small>Usuario: {{ auth()->user()->name }}</small>
                </td>
            </tr>
        </table>
        <div class="header-separator"></div>
    </div>

    <table class="reporte">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tipo</th>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Cliente</th>
                <th>Comprobante</th>
                <th>Producto</th>
                <th>Unidad</th>
                <th>Emp</th>
                <th class="text-right">Prestado</th>
                <th class="text-right">Devuelto</th>
                <th class="text-right">Saldo</th>
            </tr>
        </thead>

        <tbody>
            @php
            $totalRegistros = 0;
            $totalPrestado = 0;
            $totalDevuelto = 0;
            $totalSaldo = 0;
            @endphp

            @forelse($reportes as $r)
            <tr class="linea2">
                <td>{{ $r->id }}</td>
                <td class="{{ $r->movimiento_tipo === 'PA' ? 'tipo-pa' : 'tipo-pd' }}">
                    {{ $r->movimiento_tipo }}
                </td>
                <td>{{ \Carbon\Carbon::parse($r->fecha_prestamo)->format('d/m/Y') }}</td>
                <td>{{ $r->user_nombre }}</td>
                <td>{{ $r->cliente_nombre }}</td>
                <td>{{ $r->comprobante_tipo_codigo }}-{{ $r->serie }}-{{ $r->correlativo }}</td>
                <td>{{ $r->producto_nombre }}</td>
                <td>{{ $r->unidad_nombre }}</td>
                <td>{{ $r->producto_empaque }}</td>
                <td class="text-right">{{ number_format($r->cantidad_prestada, 2) }}</td>
                <td class="text-right">{{ number_format($r->cantidad_devuelta, 2) }}</td>
                <td class="text-right">{{ number_format($r->saldo, 2) }}</td>
            </tr>

            @php
            $totalRegistros++;
            $totalPrestado += $r->cantidad_prestada;
            $totalDevuelto += $r->cantidad_devuelta;
            $totalSaldo += $r->saldo;
            @endphp

            @empty
            <tr>
                <td colspan="12" class="text-center">No se encontraron registros</td>
            </tr>
            @endforelse
        </tbody>

        @if(count($reportes) > 0)
        <tfoot>
            <tr class="linea">
                <td><strong>{{ $totalRegistros }}</strong></td>
                <td colspan="8" class="text-right"><strong>TOTALES</strong></td>
                <td class="text-right"><strong>{{ number_format($totalPrestado, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalDevuelto, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalSaldo, 2) }}</strong></td>
            </tr>
        </tfoot>
        @endif

    </table>

</body>

</html>