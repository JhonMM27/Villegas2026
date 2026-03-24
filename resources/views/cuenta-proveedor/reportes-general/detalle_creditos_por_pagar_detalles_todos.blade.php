<!DOCTYPE html>
<html>
@include('pdf.styles', [
  'title' => "Detalle créditos por pagar ({$ini->format('d/m/Y')} al {$fin->format('d/m/Y')})"
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
                    Reporte: Detalle créditos por pagar ({{ $ini->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }})
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
                <th>Código</th>
                <th>Descripción</th>
                <th class="text-right">Empaque</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Kg</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Importe</th>
            </tr>
        </thead>

        <tbody>
            @php
                $compraActual = null;

                $subCantidad = 0; $subKg = 0; $subImporte = 0;

                $totAbonos = 0; $totSaldo = 0;
                $totKg = 0; $totImporte = 0;

                $docActual = '';
                $fechaActual = null;
                $pagoActual = '';
                $abonoActual = 0;
                $saldoActual = 0;
            @endphp

            @forelse($reportes as $r)
                @php
                    $isNewCompra = ($compraActual === null || $r->compra_id != $compraActual);
                @endphp

                {{-- Cerrar compra anterior --}}
                @if($compraActual !== null && $isNewCompra)
                    <tr class="subtotal-row">
                        <td colspan="2">
                            Abono: S/. {{ number_format($abonoActual,2) }}
                            &nbsp;&nbsp;|&nbsp;&nbsp;
                            Saldo: S/. {{ number_format($saldoActual,2) }}
                        </td>
                        <td class="text-right"></td>
                        <td class="text-right">{{ number_format($subCantidad, 2) }}</td>
                        <td class="text-right">{{ number_format($subKg, 2) }}</td>
                        <td class="text-right">TOTAL:</td>
                        <td class="text-right">{{ number_format($subImporte, 2) }}</td>
                    </tr>

                    @php
                        $subCantidad = 0; $subKg = 0; $subImporte = 0;
                    @endphp
                @endif

                {{-- Cabecera por compra --}}
                @if($isNewCompra)
                    @php
                        $compraActual = $r->compra_id;

                        $docActual = $r->documento;
                        $fechaActual = $r->fecha_compra;
                        $pagoActual = $r->pago_forma_nombre;

                        $abonoActual = (float)($r->abonos ?? 0);
                        $saldoActual = (float)($r->saldo ?? 0);

                        $totAbonos += $abonoActual;
                        $totSaldo  += $saldoActual;
                    @endphp

                    <tr>
                        <td colspan="7">
                            <strong>{{ \Carbon\Carbon::parse($fechaActual)->format('d/m/Y') }}</strong>
                            &nbsp;|&nbsp;
                            <strong>{{ $docActual }}</strong>
                            &nbsp;|&nbsp;
                            Forma pago: <strong>{{ $pagoActual }}</strong>
                        </td>
                    </tr>
                @endif

                @php
                    $cantidad = (float)($r->cantidad ?? 0);
                    $kg = (float)($r->kg ?? 0);
                    $precio = (float)($r->precio ?? 0);
                    $importe = (float)($r->importe ?? 0);
                    $empaque = (float)($r->producto_empaque ?? 0);

                    $subCantidad += $cantidad;
                    $subKg += $kg;
                    $subImporte += $importe;

                    $totKg += $kg;
                    $totImporte += $importe;
                @endphp

                <tr class="linea2">
                    <td>{{ $r->producto_id }}</td>
                    <td>{{ $r->producto_nombre }}</td>
                    <td class="text-right">{{ number_format($empaque, 2) }}</td>
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

            {{-- Cerrar última compra + Total general --}}
            @if(count($reportes) > 0)
                <tr class="subtotal-row">
                    <td colspan="2">
                        Abono: S/. {{ number_format($abonoActual,2) }}
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        Saldo: S/. {{ number_format($saldoActual,2) }}
                    </td>
                    <td class="text-right"></td>
                    <td class="text-right">{{ number_format($subCantidad, 2) }}</td>
                    <td class="text-right">{{ number_format($subKg, 2) }}</td>
                    <td class="text-right">TOTAL:</td>
                    <td class="text-right">{{ number_format($subImporte, 2) }}</td>
                </tr>

                <tr class="total-row">
                    <td colspan="2">
                        Tot. Abonos: S/. {{ number_format($totAbonos,2) }}
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        Tot. Saldos: S/. {{ number_format($totSaldo,2) }}
                    </td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                    <td class="text-right">{{ number_format($totKg, 2) }}</td>
                    <td class="text-right">TOTAL GRAL:</td>
                    <td class="text-right">{{ number_format($totImporte, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>