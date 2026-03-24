<!DOCTYPE html>
<html>
@include('pdf.styles', [
  'title' => "Reporte Compras ({$ini->format('d/m/Y')} al {$fin->format('d/m/Y')})"
        . (!empty($proveedorNombre) ? " - Proveedor: {$proveedorNombre}" : '')
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
                <td>Reporte: Compras ({{ $ini->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }})</td>
                <td class="text-right">
                    <small>Usuario: {{ auth()->user()->name }}</small>
                </td>
            </tr>
            @if(!empty($proveedorNombre))
                <tr>
                    <td colspan="2">Proveedor: <strong>{{ $proveedorNombre }}</strong></td>
                </tr>
            @endif
        </table>
        <div class="header-separator"></div>
    </div>

    <!-- TABLA -->
    <table class="reporte">
        <thead>
            <tr>
                <th>Fecha</th>
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
            @forelse($reportes as $r)
                <tr class="linea2">
                    <td>{{ \Carbon\Carbon::parse($r->fecha_compra)->format('d/m/Y') }}</td>
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
            @empty
                <tr>
                    <td colspan="10" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse

            @if(count($reportes) > 0)
                <tr class="total-row">
                    <td class="text-right">
                        Registros: {{ $reportes->count() }}
                    </td>

                    <td colspan="2" class="text-right">TOTAL GENERAL:</td>

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