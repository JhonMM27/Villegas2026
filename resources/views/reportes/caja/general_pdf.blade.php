<!DOCTYPE html>
<html lang="es">
@include('pdf.styles', ['title' => "Reporte General de Caja"])
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
                <td>
                    Reporte General de Caja
                    @if(!empty($fechaInicio) && !empty($fechaFin))
                        ({{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
                        al {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }})
                    @endif
                </td>
                <td class="text-right">
                    <small>Usuario: {{ auth()->user()->name }}</small>
                </td>
            </tr>
        </table>
        <div class="header-separator"></div>
    </div>

    @php
        // ======================
        // RESUMEN (CABECERA)
        // ======================
        $vTotal   = (float)($ventas->total ?? 0);
        $vAcuenta = (float)($ventas->acuenta ?? 0);
        $vP = (float)($ventas->importe_p ?? 0);
        $vD = (float)($ventas->importe_d ?? 0);
        $vC = (float)($ventas->importe_c ?? 0);

        $cTotal   = (float)($compras->total ?? 0);
        $cAcuenta = (float)($compras->acuenta ?? 0);
        $cP = (float)($compras->importe_p ?? 0);
        $cD = (float)($compras->importe_d ?? 0);
        $cC = (float)($compras->importe_c ?? 0);

        // Provisionales Venta (OJO: debes mandar $ventaProvisionales desde el controlador)
        $pvTotal = (float)($ventaProvisionales->total ?? 0);
        $pP = (float)($ventaProvisionales->importe_p ?? 0);
        $pD = (float)($ventaProvisionales->importe_d ?? 0);
        $pC = (float)($ventaProvisionales->importe_c ?? 0);

        // Provisionales Compra
        $pcTotal = (float)($compraProvisionales->total ?? 0);
        $pcP = (float)($compraProvisionales->importe_p ?? 0);
        $pcD = (float)($compraProvisionales->importe_d ?? 0);
        $pcC = (float)($compraProvisionales->importe_c ?? 0);

        //Gastos
        $gastoTotal = (float)($gastos->total ?? 0); // suma(monto) lo estás mandando como "total"
        $gastoP = (float)($gastos->importe_p ?? 0);
        $gastoD = (float)($gastos->importe_d ?? 0);
        $gastoC = (float)($gastos->importe_c ?? 0);

        // Usar el resumen calculado en controlador (evita descalces)
        $ingP = (float)($resumen['ing_p'] ?? 0);
        $ingD = (float)($resumen['ing_d'] ?? 0);
        $ingC = (float)($resumen['ing_c'] ?? 0);

        $egrP = (float)($resumen['egr_p'] ?? ($cP + $pcP +$gastoP));
        $egrD = (float)($resumen['egr_d'] ?? ($cD + $pcD + $gastoD));
        $egrC = (float)($resumen['egr_c'] ?? ($cC + $pcC + $gastoC));

        $netP = (float)($resumen['net_p'] ?? 0);
        $netD = (float)($resumen['net_d'] ?? 0);
        $netC = (float)($resumen['net_c'] ?? 0);

        $netTotal = $netP + $netD + $netC;
    @endphp

    {{-- ======================
         TABLA RESUMEN
    ======================= --}}
    <table class="reporte">
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="text-right">Total</th>
                <th class="text-right">A cuenta</th>
                <th class="text-right">Principal</th>
                <th class="text-right">Depósito</th>
                <th class="text-right">Consorcio</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Ventas</strong></td>
                <td class="text-right">{{ number_format($vTotal, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($vAcuenta, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($vP, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($vD, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($vC, 2, '.', '') }}</td>
            </tr>

            <tr>
                <td><strong>Compras</strong></td>
                <td class="text-right">{{ number_format($cTotal, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($cAcuenta, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($cP, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($cD, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($cC, 2, '.', '') }}</td>
            </tr>

            <tr>
                <td><strong>Provisionales Venta</strong></td>
                <td class="text-right">{{ number_format($pvTotal, 2, '.', '') }}</td>
                <td class="text-right">—</td>
                <td class="text-right">{{ number_format($pP, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($pD, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($pC, 2, '.', '') }}</td>
            </tr>

            <tr>
                <td><strong>Provisionales Compra</strong></td>
                <td class="text-right">{{ number_format($pcTotal, 2, '.', '') }}</td>
                <td class="text-right">—</td>
                <td class="text-right">{{ number_format($pcP, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($pcD, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($pcC, 2, '.', '') }}</td>
            </tr>

            <tr>
                <td><strong>Gastos</strong></td>
                <td class="text-right">{{ number_format($gastoTotal, 2, '.', '') }}</td>
                <td class="text-right">—</td>
                <td class="text-right">{{ number_format($gastoP, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($gastoD, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($gastoC, 2, '.', '') }}</td>
            </tr>

            <tr class="total-row">
                <td>Ingresos (Ventas + Provisionales)</td>
                <td class="text-right" colspan="2"></td>
                <td class="text-right">{{ number_format($ingP, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($ingD, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($ingC, 2, '.', '') }}</td>
            </tr>

            <tr class="total-row">
                <td>Egresos (Compras + Provisionales + Gastos)</td>
                <td class="text-right" colspan="2"></td>
                <td class="text-right">{{ number_format($egrP, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($egrD, 2, '.', '') }}</td>
                <td class="text-right">{{ number_format($egrC, 2, '.', '') }}</td>
            </tr>

            <tr class="total-row">
                <td><strong>SALDO NETO</strong></td>
                <td class="text-right" colspan="2"><strong>{{ number_format($netTotal, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($netP, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($netD, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($netC, 2, '.', '') }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>