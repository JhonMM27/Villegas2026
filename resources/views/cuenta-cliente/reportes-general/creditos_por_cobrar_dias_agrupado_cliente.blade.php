<!DOCTYPE html>
<html>
@include('pdf.styles', ['title' => "Créditos por cobrar (Agrupado por cliente)"])
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
                <td>Relación de documentos de venta, con saldo por cobrar >={{$dias}} días (Agrupado por cliente)</td>
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
                <th>Fecha Venta</th>
                <th>Fecha Venc.</th>
                <th>Documento</th>
                <th>Tipo venta</th>
                <th class="text-right">Total</th>
                <th class="text-right">A cuenta</th>
                <th class="text-right">Abonos</th>
                <th class="text-right">Saldo</th>
                <th class="text-right">Ítems</th>
                <th>Cliente</th>
                <th>Usuario</th>
            </tr>
        </thead>

        <tbody>
            @forelse($grupos as $g)

                {{-- Encabezado de cliente --}}
                <tr class="linea2">
                    <td colspan="11">
                        <strong>Cliente:</strong> {{ $g['cliente_nombre'] }}
                        <small>(ID: {{ $g['cliente_id'] }})</small>
                    </td>
                </tr>

                {{-- Detalle de ventas del cliente --}}
                @foreach($g['ventas'] as $r)
                    <tr>
                        <td>{{ $r->fecha_venta_fmt }}</td>
                        <td>{{ $r->fecha_vencimiento_fmt }}</td>
                        <td>{{ $r->documento }}</td>
                        <td>{{ $r->tipo_venta }}</td>

                        <td class="text-right">{{ number_format((float)$r->total, 2) }}</td>
                        <td class="text-right">{{ number_format((float)$r->acuenta, 2) }}</td>
                        <td class="text-right">{{ number_format((float)$r->abonos, 2) }}</td>
                        <td class="text-right">{{ number_format((float)$r->saldo, 2) }}</td>

                        <td class="text-right">{{ (int)$r->items }}</td>
                        <td>{{ $r->cliente_nombre }}</td>
                        <td>{{ $r->user_nombre }}</td>
                    </tr>
                @endforeach

                {{-- Subtotal por cliente --}}
                <tr class="total-row">
                    <td colspan="4" class="text-right">SUBTOTAL CLIENTE:</td>

                    <td class="text-right">{{ number_format((float)$g['totTotal'], 2) }}</td>
                    <td class="text-right">{{ number_format((float)$g['totAcuenta'], 2) }}</td>
                    <td class="text-right">{{ number_format((float)$g['totAbonos'], 2) }}</td>
                    <td class="text-right">{{ number_format((float)$g['totSaldo'], 2) }}</td>

                    <td class="text-right">{{ (int)$g['totItems'] }}</td>
                    <td colspan="2"></td>
                </tr>

            @empty
                <tr>
                    <td colspan="11" class="text-center">No se encontraron créditos por cobrar</td>
                </tr>
            @endforelse

            @if(count($grupos) > 0)
                <tr class="total-row">
                    <td colspan="4" class="text-right">TOTAL GENERAL:</td>

                    <td class="text-right">{{ number_format((float)$totTotal, 2) }}</td>
                    <td class="text-right">{{ number_format((float)$totAcuenta, 2) }}</td>
                    <td class="text-right">{{ number_format((float)$totAbonos, 2) }}</td>
                    <td class="text-right">{{ number_format((float)$totSaldo, 2) }}</td>

                    <td class="text-right">{{ (int)$totItems }}</td>
                    <td colspan="2"></td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>
