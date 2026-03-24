<!DOCTYPE html>
<html>
@include('pdf.styles', [
    'title' => "Saldos acumulados por cliente ({$ini->format('d/m/Y')} al {$fin->format('d/m/Y')})"
])

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
            <td>Reporte: Saldos acumulados por cliente ({{$ini->format('d/m/Y')}} al {{$fin->format('d/m/Y')}})</td>
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
            <th>Cliente</th>
            <th class="text-right">Saldo</th>
            <th class="text-right">Ventas</th>
            <th>Domicilio</th>
            <th>Teléfono</th>
        </tr>
    </thead>
    <tbody>
        @forelse($reportes as $r)
            <tr class="linea2">
                <td class="text-right">{{ (int)$r->cliente_id }}</td>
                <td>{{ $r->cliente_nombre }}</td>
                <td class="text-right">{{ number_format((float)$r->saldo, 2) }}</td>
                <td class="text-right">{{ (int)$r->items }}</td>
                <td>{{ $r->domicilio }}</td>
                <td>{{ $r->telefono }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center">No se encontraron clientes con saldo (>=30 días)</td>
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