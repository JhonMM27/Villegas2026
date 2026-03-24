<!DOCTYPE html>
<html>
@include('pdf.styles', ['title' => "Saldos agrupados por proveedor"])
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
            <td>Reporte: Saldos agrupados por proveedor (compras con antigüedad >= {{ $dias ?? 30 }} días)</td>
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
            <th class="text-right">Código</th>
            <th>Proveedor</th>
            <th class="text-right">Saldo</th>
            <th class="text-right">Compras</th>
            <th>Domicilio</th>
            <th>Teléfono</th>
        </tr>
    </thead>
    <tbody>
        @forelse($reportes as $r)
            <tr class="linea2">
                <td class="text-right">{{ (int)$r->proveedor_id }}</td>
                <td>{{ $r->proveedor_nombre }}</td>
                <td class="text-right">{{ number_format((float)$r->saldo, 2) }}</td>
                <td class="text-right">{{ (int)$r->items }}</td>
                <td>{{ $r->domicilio }}</td>
                <td>{{ $r->telefono }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center">No se encontraron proveedores con saldo</td>
            </tr>
        @endforelse

        @if(count($reportes) > 0)
            <tr class="total-row">
                <td colspan="4" class="text-right">TOTAL GENERAL:</td>
                <td class="text-right">{{ (int)$totItems }}</td>
                <td class="text-right">{{ number_format((float)$totSaldo, 2) }}</td>
            </tr>
        @endif
    </tbody>
</table>

</body>
</html>