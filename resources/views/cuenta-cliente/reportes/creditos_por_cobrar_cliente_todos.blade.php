<!DOCTYPE html>
<html>
@include('pdf.styles', [
  'title' => "Créditos por cobrar" . (!empty($clienteNombre) ? " - Cliente: {$clienteNombre}" : '')
])
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
                <td>Reporte: Créditos por cobrar</td>
                <td class="text-right">
                    <small>Usuario: {{ auth()->user()->name }}</small>
                </td>
            </tr>
            @if(!empty($clienteNombre))
                <tr>
                    <td colspan="2">Cliente: <strong>{{ $clienteNombre }}</strong></td>
                </tr>
            @endif
        </table>
        <div class="header-separator"></div>
    </div>

    <!-- TABLA -->
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
            @forelse($reportes as $r)
                <tr class="linea2">
                    <td>{{ \Carbon\Carbon::parse($r->fecha_venta)->format('d/m/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->fecha_vencimiento)->format('d/m/Y') }}</td>
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
            @empty
                <tr>
                    <td colspan="10" class="text-center">No se encontraron créditos por cobrar</td>
                </tr>
            @endforelse

            @if(count($reportes) > 0)
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

    {{-- CUADRO DE ABONOS SUELTOS --}}
    <div class="section-title">Saldo a favor (abonos sin venta asociada)</div>

    <table class="reporte">
        <thead>
        <tr>
            <th class="nowrap">Fecha</th>
            <th>Documento</th>
            <th>Detalle</th>
            <th class="text-right">Pago</th>
        </tr>
        </thead>
        <tbody>
        @forelse($abonosSueltos ?? [] as $p)
            <tr class="row-abono">
                <td class="nowrap">{{ \Carbon\Carbon::parse($p->fecha_provisional)->format('d/m/Y') }}</td>
                <td>{{ 'REC ' . ($p->numero_recibo ?? '') }}</td>
                <td>ABONO</td>
                <td class="text-right">{{ number_format((float)($p->monto ?? 0), 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center">Sin abonos sueltos en el rango.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

</body>
</html>