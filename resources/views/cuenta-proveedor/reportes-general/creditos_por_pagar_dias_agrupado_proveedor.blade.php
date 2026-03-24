<!DOCTYPE html>
<html>
@include('pdf.styles', ['title' => "Créditos por pagar (Agrupado por proveedor)"])
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
                <td>Relación de documentos de compra, con saldo por pagar >={{ $dias ?? 30 }} días (Agrupado por proveedor)</td>
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
                <th>Fecha Compra</th>
                <th>Fecha Venc.</th>
                <th>Documento</th>
                <th>F. Pago</th>
                <th class="text-right">Total</th>
                <th class="text-right">A cuenta</th>
                <th class="text-right">Abonos</th>
                <th class="text-right">Saldo</th>
                <th class="text-right">Ítems</th>
                <th>Proveedor</th>
                <th>Usuario</th>
            </tr>
        </thead>

        <tbody>
            @forelse($grupos as $g)

                {{-- Encabezado de proveedor --}}
                <tr class="linea2">
                    <td colspan="11">
                        <strong>Proveedor:</strong> {{ $g['proveedor_nombre'] }}
                        <small>(ID: {{ $g['proveedor_id'] }})</small>
                    </td>
                </tr>

                {{-- Detalle de compras del proveedor --}}
                @foreach($g['compras'] as $r)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($r->fecha_compra)->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($r->fecha_vencimiento)->format('d/m/Y') }}</td>
                        <td>{{ $r->documento }}</td>
                        <td>{{ $r->tipo_venta }}</td>

                        <td class="text-right">{{ number_format((float)$r->total, 2) }}</td>
                        <td class="text-right">{{ number_format((float)$r->acuenta, 2) }}</td>
                        <td class="text-right">{{ number_format((float)$r->abonos, 2) }}</td>
                        <td class="text-right">{{ number_format((float)$r->saldo, 2) }}</td>

                        <td class="text-right">{{ (int)$r->items }}</td>
                        <td>{{ $r->proveedor_nombre }}</td>
                        <td>{{ $r->user_nombre }}</td>
                    </tr>
                @endforeach

                {{-- Subtotal por proveedor --}}
                <tr class="total-row">
                    <td colspan="4" class="text-right">SUBTOTAL PROVEEDOR:</td>

                    <td class="text-right">{{ number_format((float)$g['totTotal'], 2) }}</td>
                    <td class="text-right">{{ number_format((float)$g['totAcuenta'], 2) }}</td>
                    <td class="text-right">{{ number_format((float)$g['totAbonos'], 2) }}</td>
                    <td class="text-right">{{ number_format((float)$g['totSaldo'], 2) }}</td>

                    <td class="text-right">{{ (int)$g['totItems'] }}</td>
                    <td colspan="2"></td>
                </tr>

            @empty
                <tr>
                    <td colspan="11" class="text-center">No se encontraron créditos por pagar</td>
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
