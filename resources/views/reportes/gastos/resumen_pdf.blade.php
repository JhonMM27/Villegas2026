<!DOCTYPE html>
<html>
<head>
    <title>Reporte de Gastos</title>
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
                    Reporte de Gastos ({{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }})
                    @if($tipo && $tipo !== 'Todos')
                        - Tipo: {{ $tipo }}
                    @endif
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
                <th>Recibo</th>
                <th>Nro. Interno</th>
                <th>Tipo</th>
                <th>Fecha</th>
                <th>Descripción</th>
                <th>Responsable</th>
                <th class="text-right">Principal</th>
                <th class="text-right">Depósito</th>
                <th class="text-right">Consorcio</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalPrincipal = 0;
                $totalDeposito = 0;
                $totalConsorc = 0;

                $tipoActual = null;
                $subPrincipal = 0;
                $subDeposito = 0;
                $subConsorc = 0;
            @endphp

            @forelse($reportes as $r)
                @php
                    $tipo = $r->tipo ?? 'SIN TIPO';
                    $principal = (float)($r->importe_p ?? 0);
                    $deposito = (float)($r->importe_d ?? 0);
                    $consorc = (float)($r->importe_c ?? 0);
                    $fecha = $r->fecha_gasto ? \Carbon\Carbon::parse($r->fecha_gasto)->format('d/m/Y H:i') : '';
                @endphp

                @if($tipoActual !== null && $tipo !== $tipoActual)
                    <tr class="subtotal-row">
                        <td colspan="6" class="text-right">SUBTOTAL {{ $tipoActual }}:</td>
                        <td class="text-right">{{ number_format($subPrincipal, 2) }}</td>
                        <td class="text-right">{{ number_format($subDeposito, 2) }}</td>
                        <td class="text-right">{{ number_format($subConsorc, 2) }}</td>
                    </tr>

                    @php
                        $subPrincipal = 0;
                        $subDeposito = 0;
                        $subConsorc = 0;
                    @endphp
                @endif

                @php
                    if ($tipoActual === null) $tipoActual = $tipo;
                    if ($tipo !== $tipoActual) $tipoActual = $tipo;

                    $subPrincipal += $principal;
                    $subDeposito += $deposito;
                    $subConsorc += $consorc;

                    $totalPrincipal += $principal;
                    $totalDeposito += $deposito;
                    $totalConsorc += $consorc;
                @endphp

                <tr class="linea2">
                    <td>{{ $r->numero_recibo }}</td>
                    <td>{{ $r->numero_interno }}</td>
                    <td>{{ $tipo }}</td>
                    <td>{{ $fecha }}</td>
                    <td>{{ $r->descripcion }}</td>
                    <td>{{ $r->responsable }}</td>
                    <td class="text-right">{{ number_format($principal, 2) }}</td>
                    <td class="text-right">{{ number_format($deposito, 2) }}</td>
                    <td class="text-right">{{ number_format($consorc, 2) }}</td>
                </tr>

            @empty
                <tr>
                    <td colspan="9" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse

            @if(count($reportes) > 0)
                <tr class="subtotal-row">
                    <td colspan="6" class="text-right">SUBTOTAL {{ $tipoActual }}:</td>
                    <td class="text-right">{{ number_format($subPrincipal, 2) }}</td>
                    <td class="text-right">{{ number_format($subDeposito, 2) }}</td>
                    <td class="text-right">{{ number_format($subConsorc, 2) }}</td>
                </tr>

                <tr class="total-row">
                    <td colspan="6" class="text-right">TOTAL GENERAL:</td>
                    <td class="text-right">{{ number_format($totalPrincipal, 2) }}</td>
                    <td class="text-right">{{ number_format($totalDeposito, 2) }}</td>
                    <td class="text-right">{{ number_format($totalConsorc, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>
