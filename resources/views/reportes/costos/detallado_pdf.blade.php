<!DOCTYPE html>
<html>
<head>
    <title>Reporte Detallado de Costos</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Courier, monospace; font-size: 10px; margin: 0; padding: 0; }
        @page { margin: 60px 20px 40px 20px; }
        .page-header { position: fixed; top: -60px; left: 0; right: 0; height: 50px; font-family: Courier, monospace; font-size: 11px; padding: 5px 20px; }
        .page-header table { width: 100%; border-collapse: collapse; }
        .page-header td { padding: 2px 0; }
        .text-right { text-align: right; }
        .header-separator { border-bottom: 1px solid #000; margin: 5px 0 10px 0; }
        .page-number:before { content: "Página " counter(page); }
        table.reporte { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.reporte th, table.reporte td { padding: 2px 3px; white-space: nowrap; }
        table.reporte th { border-bottom: 1px solid #000; }
        .linea2 { border-bottom: 0.5px dashed #000; }
        .total-row { font-weight: bold; border-top: 2px solid #000; }
    </style>
</head>
<body>

    <div class="page-header">
        <table>
            <tr>
                <td>CONSORCIOS VILLEGAS EIRL</td>
                <td class="text-right"><span class="page-number"></span>&nbsp;&nbsp;{{ now()->format('d/m/Y H:i:s') }}</td>
            </tr>
            <tr>
                <td>Reporte Detallado de Costos ({{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }})</td>
                <td class="text-right"><small>Usuario: {{ auth()->user()->name }}</small></td>
            </tr>
        </table>
        <div class="header-separator"></div>
    </div>

    <table class="reporte">
        <thead>
            <tr>
                <th>Recibo</th>
                <th>Nro.Int</th>
                <th>Categoria</th>
                <th>Fecha</th>
                <th>Tipo Costo</th>
                <th>Descripcion</th>
                <th>Responsable</th>
                <th class="text-right">Principal</th>
                <th class="text-right">Deposito</th>
                <th class="text-right">Consorcio</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalPrincipal = 0;
                $totalDeposito = 0;
                $totalConsorcio = 0;
            @endphp

            @forelse($reportes as $r)
                @php
                    $principal = (float)($r->importe_p ?? 0);
                    $deposito = (float)($r->importe_d ?? 0);
                    $consorcio = (float)($r->importe_c ?? 0);
                    $fecha = $r->fecha_costo ? \Carbon\Carbon::parse($r->fecha_costo)->format('d/m/Y') : '';
                    $totalPrincipal += $principal;
                    $totalDeposito += $deposito;
                    $totalConsorcio += $consorcio;
                @endphp
                <tr class="linea2">
                    <td>{{ $r->numero_recibo }}</td>
                    <td>{{ $r->numero_interno ?? '' }}</td>
                    <td>{{ $r->categoriaCosto?->nombre ?? '' }}</td>
                    <td>{{ $fecha }}</td>
                    <td>{{ $r->costoTipo?->nombre ?? '' }}</td>
                    <td>{{ $r->descripcion ?? '' }}</td>
                    <td>{{ $r->responsable ?? '' }}</td>
                    <td class="text-right">{{ number_format($principal, 2) }}</td>
                    <td class="text-right">{{ number_format($deposito, 2) }}</td>
                    <td class="text-right">{{ number_format($consorcio, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse

            @if(count($reportes) > 0)
                <tr class="total-row">
                    <td colspan="7" class="text-right">TOTALES:</td>
                    <td class="text-right">{{ number_format($totalPrincipal, 2) }}</td>
                    <td class="text-right">{{ number_format($totalDeposito, 2) }}</td>
                    <td class="text-right">{{ number_format($totalConsorcio, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>
