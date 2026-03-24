<!DOCTYPE html>
<html>
@include('pdf.styles', [
    'title' => "RESUMEN DE CREDITOS POR PAGAR A PROVEEDORES"
])
<body>

<div class="page-header">
    <table width="100%">
        <tr>
            <td>CONSORCIOS VILLEGAS EIRL</td>
            <td class="text-right">
                PAGINA : <span class="page-number"></span>
                &nbsp;&nbsp; {{ now()->format('d/m/Y H:i:s') }}
            </td>
        </tr>
    </table>
</div>

<h3 style="text-align:center;">RESUMEN DE CREDITOS POR PAGAR A PROVEEDORES</h3>

<table class="reporte">
    <thead>
        <tr>
            <th>PROVEEDOR</th>
            <th>&lt;= 3 DIAS</th>
            <th>4 A 7 DIAS</th>
            <th>8 A 15 DIAS</th>
            <th>16 A 30 DIAS</th>
            <th>&gt;= 31 DIAS</th>
            <th>ACUMULADO</th>
        </tr>
    </thead>
    <tbody>

    @php
        $tot3 = $tot7 = $tot15 = $tot30 = $tot31 = $totAcum = 0;
    @endphp

    @foreach($reportes as $r)

        @php
            $tot3 += $r->d_3;
            $tot7 += $r->d_7;
            $tot15 += $r->d_15;
            $tot30 += $r->d_30;
            $tot31 += $r->d_31;
            $totAcum += $r->acumulado;
        @endphp

        <tr>
            <td>{{ $r->proveedor_nombre }}</td>
            <td class="text-right">{{ number_format($r->d_3, 2) }}</td>
            <td class="text-right">{{ number_format($r->d_7, 2) }}</td>
            <td class="text-right">{{ number_format($r->d_15, 2) }}</td>
            <td class="text-right">{{ number_format($r->d_30, 2) }}</td>
            <td class="text-right">{{ number_format($r->d_31, 2) }}</td>
            <td class="text-right">{{ number_format($r->acumulado, 2) }}</td>
        </tr>

    @endforeach

    <tr class="total-row">
        <td><strong>TOTAL GENERAL</strong></td>
        <td class="text-right"><strong>{{ number_format($tot3,2) }}</strong></td>
        <td class="text-right"><strong>{{ number_format($tot7,2) }}</strong></td>
        <td class="text-right"><strong>{{ number_format($tot15,2) }}</strong></td>
        <td class="text-right"><strong>{{ number_format($tot30,2) }}</strong></td>
        <td class="text-right"><strong>{{ number_format($tot31,2) }}</strong></td>
        <td class="text-right"><strong>{{ number_format($totAcum,2) }}</strong></td>
    </tr>

    </tbody>
</table>

</body>
</html>