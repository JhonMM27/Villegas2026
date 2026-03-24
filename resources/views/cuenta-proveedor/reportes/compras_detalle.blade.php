<!DOCTYPE html>
<html>
@include('pdf.styles', [
    'title' => "Reporte: Compras detallado ({$ini->format('d/m/Y')} al {$fin->format('d/m/Y')})"
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
                <td>
                    Reporte: Compras detallado ({{ $ini->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }})
                </td>
                <td class="text-right">
                    <small>Usuario: {{ auth()->user()->name }}</small>
                </td>
            </tr>
            @if($proveedorNombre)
                <tr>
                    <td colspan="2">
                        Proveedor: <strong>{{ $proveedorNombre }}</strong>
                    </td>
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
                <th>Descripción</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Kg</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Importe</th>
            </tr>
        </thead>

        <tbody>
            @php
                $compraActual = null;
                $documentoActual = '';

                $subCantidad = 0; $subKg = 0; $subImporte = 0;

                $totCantidad = 0; $totKg = 0; $totImporte = 0;

                $totAbonos = 0;
                $totSaldo  = 0;

                // Guardar abono/saldo de la compra actual para imprimir en el subtotal
                $abonoActual = 0;
                $saldoActual = 0;
            @endphp

            @forelse($reportes as $r)
                @php
                    $compraId  = $r->compra_id;

                    $cantidad = (float)($r->cantidad_convertida ?? 0);
                    $kg       = (float)($r->kg_detalle ?? 0);
                    $precio   = (float)($r->costo_unitario ?? 0);
                    $importe  = (float)($r->total ?? 0);

                    $abonoCompra = (float)($r->abonos ?? 0);
                    $saldoCompra = (float)($r->saldo ?? 0);

                    $isNewCompra = ($compraActual === null || $compraId !== $compraActual);
                @endphp

                {{-- Cuando cambia la compra: imprimir subtotal de la compra anterior --}}
                @if($compraActual !== null && $compraId !== $compraActual)
                    <tr class="subtotal-row">
                        <td>Abono: S/. {{ number_format($abonoActual, 2) }}</td>
                        <td>Saldo: S/. {{ number_format($saldoActual, 2) }}</td>
                        <td class="text-right">SUBTOTAL ({{ $documentoActual }}):</td>
                        <td class="text-right">{{ number_format($subCantidad, 2) }}</td>
                        <td class="text-right">{{ number_format($subKg, 2) }}</td>
                        <td></td>
                        <td class="text-right">{{ number_format($subImporte, 2) }}</td>
                    </tr>

                    @php
                        $subCantidad = 0; $subKg = 0; $subImporte = 0;
                    @endphp
                @endif

                @php
                    // Si es una compra nueva, actualiza doc + abono/saldo de esa compra (para su futuro subtotal)
                    if ($isNewCompra) {
                        $compraActual = $compraId;
                        $documentoActual = $r->documento;

                        $abonoActual = $abonoCompra;
                        $saldoActual = $saldoCompra;

                        // acumular totales generales (una vez por compra)
                        $totAbonos += $abonoCompra;
                        $totSaldo  += $saldoCompra;
                    }

                    // acumular subtotales por compra (detalle)
                    $subCantidad += $cantidad;
                    $subKg += $kg;
                    $subImporte += $importe;

                    // acumular totales generales (detalle)
                    $totCantidad += $cantidad;
                    $totKg += $kg;
                    $totImporte += $importe;
                @endphp

                <tr class="linea2">
                    <td>{{ \Carbon\Carbon::parse($r->fecha_compra)->format('d/m/Y') }}</td>
                    <td>{{ $r->documento }}</td>
                    <td>{{ $r->producto_nombre }}</td>
                    <td class="text-right">{{ number_format($cantidad, 2) }}</td>
                    <td class="text-right">{{ number_format($kg, 2) }}</td>
                    <td class="text-right">{{ number_format($precio, 4) }}</td>
                    <td class="text-right">{{ number_format($importe, 2) }}</td>
                </tr>

            @empty
                <tr>
                    <td colspan="7" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse

            {{-- Subtotal final (última compra) + Total general --}}
            @if(count($reportes) > 0)
                <tr class="subtotal-row">
                    <td>Abono: S/. {{ number_format($abonoActual, 2) }}</td>
                    <td>Saldo: S/. {{ number_format($saldoActual, 2) }}</td>
                    <td class="text-right">SUBTOTAL ({{ $documentoActual }}):</td>
                    <td class="text-right">{{ number_format($subCantidad, 2) }}</td>
                    <td class="text-right">{{ number_format($subKg, 2) }}</td>
                    <td></td>
                    <td class="text-right">{{ number_format($subImporte, 2) }}</td>
                </tr>

                <tr class="total-row">
                    <td>Tot. Abonos: S/. {{ number_format($totAbonos, 2) }}</td>
                    <td>Tot. Saldos: S/. {{ number_format($totSaldo, 2) }}</td>
                    <td class="text-right">TOTAL GENERAL:</td>
                    <td class="text-right">{{ number_format($totCantidad, 2) }}</td>
                    <td class="text-right">{{ number_format($totKg, 2) }}</td>
                    <td></td>
                    <td class="text-right">{{ number_format($totImporte, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>