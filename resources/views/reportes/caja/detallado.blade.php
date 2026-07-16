@php
    // Ingresos manuales a caja (nuevo módulo)
    $icP = (float)($ingresosCaja->importe_p ?? 0);
    $icD = (float)($ingresosCaja->importe_d ?? 0);
    $icC = (float)($ingresosCaja->importe_c ?? 0);
    $icTotal = $icP + $icD + $icC;

    // Helpers para evitar warnings
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

    $pvTotal = (float)($ventaProvisionales->total ?? 0); // suma(monto) lo estás mandando como "total"
    $pP = (float)($ventaProvisionales->importe_p ?? 0);
    $pD = (float)($ventaProvisionales->importe_d ?? 0);
    $pC = (float)($ventaProvisionales->importe_c ?? 0);

    $pcTotal = (float)($compraProvisionales->total ?? 0); // suma(monto) lo estás mandando como "total"
    $pcP = (float)($compraProvisionales->importe_p ?? 0);
    $pcD = (float)($compraProvisionales->importe_d ?? 0);
    $pcC = (float)($compraProvisionales->importe_c ?? 0);

    $gastoTotal = (float)($gastos->total ?? 0); // suma(monto) lo estás mandando como "total"
    $gastoP = (float)($gastos->importe_p ?? 0);
    $gastoD = (float)($gastos->importe_d ?? 0);
    $gastoC = (float)($gastos->importe_c ?? 0);

    // COSTOS (se restan del neto de cada caja, NO se suman a Egresos)
    $costoTotal = (float)($costos->total ?? 0);
    $costoP = (float)($costos->importe_p ?? 0);
    $costoD = (float)($costos->importe_d ?? 0);
    $costoC = (float)($costos->importe_c ?? 0);

    // Planilla - GASTOS EMPLEADOS
    $adelantoTotal = (float)($adelantos->total ?? 0);
    $adelantoP = (float)($adelantos->importe_p ?? 0);
    $adelantoD = (float)($adelantos->importe_d ?? 0);
    $adelantoC = (float)($adelantos->importe_c ?? 0);

    $prestamoTotal = (float)($prestamos->total ?? 0);
    $prestamoP = (float)($prestamos->importe_p ?? 0);
    $prestamoD = (float)($prestamos->importe_d ?? 0);
    $prestamoC = (float)($prestamos->importe_c ?? 0);

    $pagoPrestamoTotal = (float)($pagosPrestamos->total ?? 0);
    $pagoPrestamoP = (float)($pagosPrestamos->importe_p ?? 0);
    $pagoPrestamoD = (float)($pagosPrestamos->importe_d ?? 0);
    $pagoPrestamoC = (float)($pagosPrestamos->importe_c ?? 0);

    $pagoPlanillaTotal = (float)($pagosPlanilla->total ?? 0);
    $pagoPlanillaP = (float)($pagosPlanilla->importe_p ?? 0);
    $pagoPlanillaD = (float)($pagosPlanilla->importe_d ?? 0);
    $pagoPlanillaC = (float)($pagosPlanilla->importe_c ?? 0);

    // GASTOS EMPLEADOS = adelantos + prestamos + pagoPrestamos + pagoPlanilla
    // Solo P + D (C es ingreso de consorcio, no egreso)
    $gastosEmpleadosTotal = ($adelantoP + $adelantoD + $prestamoP + $prestamoD + $pagoPrestamoP + $pagoPrestamoD + $pagoPlanillaP + $pagoPlanillaD);
    $gastosEmpleadosP = $adelantoP + $prestamoP + $pagoPrestamoP + $pagoPlanillaP;
    $gastosEmpleadosD = $adelantoD + $prestamoD + $pagoPrestamoD + $pagoPlanillaD;
    $gastosEmpleadosC = $adelantoC + $prestamoC + $pagoPrestamoC + $pagoPlanillaC;

    // Si no te mandan resumen, lo calculamos aquí también (por si acaso)
    $ingP = (float)($resumen['ing_p'] ?? ($vP + $pP));
    $ingD = (float)($resumen['ing_d'] ?? ($vD + $pD));
    $ingC = (float)($resumen['ing_c'] ?? ($vC + $pC));

    $egrP = (float)($resumen['egr_p'] ?? ($cP + $pcP +$gastoP));
    $egrD = (float)($resumen['egr_d'] ?? ($cD + $pcD + $gastoD));
    $egrC = (float)($resumen['egr_c'] ?? ($cC + $pcC + $gastoC));

    $netP = (float)($resumen['net_p'] ?? ($ingP - $egrP));
    $netD = (float)($resumen['net_d'] ?? ($ingD - $egrD));
    $netC = (float)($resumen['net_c'] ?? ($ingC - $egrC));

    $netTotal = $netP + $netD + $netC;
@endphp

@if(!empty($fechaInicio) && !empty($fechaFin))
<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app align-middle">
        <thead class="table-light">
            <tr>
                <th style="min-width: 180px;">Concepto</th>
                <th class="text-end" style="min-width: 110px;">Total</th>
                <th class="text-end" style="min-width: 110px;">A cuenta</th>
                <th class="text-end" style="min-width: 120px;">Principal</th>
                <th class="text-end" style="min-width: 120px;">Depósito</th>
                <th class="text-end" style="min-width: 120px;">Consorcio</th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td><strong>Ventas</strong></td>
                <td class="text-end">{{ number_format($vTotal, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($vAcuenta, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($vP, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($vD, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($vC, 2, '.', '') }}</td>
            </tr>

            <tr>
                <td><strong>Compras</strong></td>
                <td class="text-end">{{ number_format($cTotal, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($cAcuenta, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($cP, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($cD, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($cC, 2, '.', '') }}</td>
            </tr>

            <tr class="table-info">
                <td><strong>Ingresos Caja (manuales)</strong></td>
                <td class="text-end">{{ number_format($icTotal, 2, '.', '') }}</td>
                <td class="text-end">—</td>
                <td class="text-end">{{ number_format($icP, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($icD, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($icC, 2, '.', '') }}</td>
            </tr>

            <tr>
                <td><strong>Provisionales Venta</strong></td>
                <td class="text-end">{{ number_format($pvTotal, 2, '.', '') }}</td>
                <td class="text-end">—</td>
                <td class="text-end">{{ number_format($pP, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($pD, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($pC, 2, '.', '') }}</td>
            </tr>

            <tr>
                <td><strong>Provisionales Compra</strong></td>
                <td class="text-end">{{ number_format($pcTotal, 2, '.', '') }}</td>
                <td class="text-end">—</td>
                <td class="text-end">{{ number_format($pcP, 2, '.', '') }}</td>  <!-- ✅ -->
                <td class="text-end">{{ number_format($pcD, 2, '.', '') }}</td>  <!-- ✅ -->
                <td class="text-end">{{ number_format($pcC, 2, '.', '') }}</td>  <!-- ✅ -->
            </tr>

             <tr>
                <td><strong>Gastos</strong></td>
                <td class="text-end">{{ number_format($gastoTotal, 2, '.', '') }}</td>
                <td class="text-end">—</td>
                <td class="text-end">{{ number_format($gastoP, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($gastoD, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($gastoC, 2, '.', '') }}</td>
            </tr>

            <tr>
                <td><strong>GASTOS - EMPLEADOS</strong></td>
                <td class="text-end">{{ number_format($gastosEmpleadosTotal, 2, '.', '') }}</td>
                <td class="text-end">—</td>
                <td class="text-end">{{ number_format($gastosEmpleadosP, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($gastosEmpleadosD, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($gastosEmpleadosC, 2, '.', '') }}</td>
            </tr>

            <tr>
                <td><strong>Costos</strong></td>
                <td class="text-end">{{ number_format($costoTotal, 2, '.', '') }}</td>
                <td class="text-end">—</td>
                <td class="text-end">{{ number_format($costoP, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($costoD, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($costoC, 2, '.', '') }}</td>
            </tr>

            <tr class="table-secondary fw-bold">
                <td>Ingresos (Ventas + Provisionales)</td>
                <td class="text-end" colspan="2"></td>
                <td class="text-end">{{ number_format($ingP, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($ingD, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($ingC, 2, '.', '') }}</td>
            </tr>

            <tr class="table-secondary fw-bold">
                <td>Egresos (Compras + Provisionales + Gastos + Planilla + Costos)</td>
                <td class="text-end" colspan="2"></td>
                <td class="text-end">{{ number_format($egrP, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($egrD, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($egrC, 2, '.', '') }}</td>
            </tr>

            <tr class="table-secondary fw-bold">
                <td>SALDO NETO</td>
                <td class="text-end" colspan="2">{{ number_format($netTotal, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($netP, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($netD, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($netC, 2, '.', '') }}</td>
            </tr>
        </tbody>
    </table>
    
    <div class="accordion" id="accReporteCaja">
        {{-- ===================================================== --}}
        {{-- VENTAS --}}
        {{-- ===================================================== --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="hVentas">
                <button class="accordion-button" type="button"
                    data-bs-toggle="collapse" data-bs-target="#cVentas"
                    aria-expanded="true">

                    <div class="w-100 d-flex justify-content-between">
                        <div>
                            <strong>Ventas</strong>
                            <small class="text-muted ms-2">
                                ({{ ($ventasList ?? collect())->count() }} registros)
                            </small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Total:</small>
                            <strong>
                                {{ number_format((float)(($ventasList ?? collect())->sum('total')),2,'.','') }}
                            </strong>
                        </div>
                    </div>

                </button>
            </h2>

            <div id="cVentas" class="accordion-collapse collapse show"
                data-bs-parent="#accReporteCaja">
                <div class="accordion-body p-2">

                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:40px;">Ver</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Documento</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">A cuenta</th>
                                <th class="text-end">Principal</th>
                                <th class="text-end">Depósito</th>
                                <th class="text-end">Consorcio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ventasList ?? [] as $v)
                            <tr>
                                <td class="text-center">
                                    <a href="javascript:void(0)"
                                    class="btn-view-venta text-muted"
                                    data-id="{{ $v->id }}"
                                    title="Ver documento">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                                <td>{{ $v->fecha_venta}}</td>
                                <td>{{ $v->cliente_nombre }}</td>
                                <td>{{ trim(($v->comprobante_tipo_codigo ? $v->comprobante_tipo_codigo.' ' : '').$v->serie.'-'.$v->correlativo) }}</td>
                                <td class="text-end">{{ number_format((float)$v->total,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$v->acuenta,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$v->importe_p,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$v->importe_d,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$v->importe_c,2,'.','') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted">Sin ventas</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="6" class="text-end">TOTALES:</td>
                                <td class="text-end">{{ number_format((float)(($ventasList ?? collect())->sum('importe_p')),2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)(($ventasList ?? collect())->sum('importe_d')),2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)(($ventasList ?? collect())->sum('importe_c')),2,'.','') }}</td>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        </div>


        {{-- ===================================================== --}}
        {{-- COMPRAS --}}
        {{-- ===================================================== --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="hCompras">
                <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#cCompras">

                    <div class="w-100 d-flex justify-content-between">
                        <div>
                            <strong>Compras</strong>
                            <small class="text-muted ms-2">
                                ({{ ($comprasList ?? collect())->count() }} registros)
                            </small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Total:</small>
                            <strong>
                                {{ number_format((float)(($comprasList ?? collect())->sum('total')),2,'.','') }}
                            </strong>
                        </div>
                    </div>

                </button>
            </h2>

            <div id="cCompras" class="accordion-collapse collapse"
                data-bs-parent="#accReporteCaja">
                <div class="accordion-body p-2">

                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:40px;">Ver</th>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Documento</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">A cuenta</th>
                                <th class="text-end">Principal</th>
                                <th class="text-end">Depósito</th>
                                <th class="text-end">Consorcio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($comprasList ?? [] as $c)
                            <tr>
                                <td class="text-center">
                                    <a href="javascript:void(0)"
                                    class="btn-view-compra text-muted"
                                    data-id="{{ $c->id }}"
                                    title="Ver documento">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                                <td>{{ $c->fecha_compra }}</td>
                                <td>{{ $c->proveedor_nombre }}</td>
                                <td>{{ trim(($c->comprobante_tipo_codigo ? $c->comprobante_tipo_codigo.' ' : '').$c->serie.'-'.$c->correlativo) }}</td>
                                <td class="text-end">{{ number_format((float)$c->total,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$c->acuenta,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$c->importe_p,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$c->importe_d,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$c->importe_c,2,'.','') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted">Sin compras</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="6" class="text-end">TOTALES:</td>
                                <td class="text-end">{{ number_format((float)(($comprasList ?? collect())->sum('importe_p')),2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)(($comprasList ?? collect())->sum('importe_d')),2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)(($comprasList ?? collect())->sum('importe_c')),2,'.','') }}</td>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- INGRESOS MANUALES A CAJA --}}
        {{-- ===================================================== --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="hIngresosCaja">
                <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#cIngresosCaja">

                    <div class="w-100 d-flex justify-content-between">
                        <div>
                            <strong>Ingresos Caja (manuales)</strong>
                            <small class="text-muted ms-2">
                                ({{ ($ingresosCajaList ?? collect())->count() }} registros)
                            </small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Total:</small>
                            <strong>
                                {{ number_format((float)(($ingresosCajaList ?? collect())->sum('monto')), 2, '.', '') }}
                            </strong>
                        </div>
                    </div>

                </button>
            </h2>

            <div id="cIngresosCaja" class="accordion-collapse collapse"
                data-bs-parent="#accReporteCaja">
                <div class="accordion-body p-2">

                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Caja</th>
                                <th>Monto</th>
                                <th>Usuario</th>
                                <th>Comentario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ingresosCajaList ?? [] as $ic)
                            <tr>
                                <td>{{ $ic->fecha ? \Carbon\Carbon::parse($ic->fecha)->format('d/m/Y') : '—' }}</td>
                                <td>{{ $ic->caja_destino }}</td>
                                <td class="text-end">S/ {{ number_format((float)$ic->monto, 2) }}</td>
                                <td>{{ $ic->user_nombre ?? '—' }}</td>
                                <td>{{ $ic->comentario ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted">Sin ingresos en este rango</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="2" class="text-end">TOTALES:</td>
                                <td class="text-end">S/ {{ number_format((float)(($ingresosCajaList ?? collect())->sum('monto')), 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        </div>


        {{-- ===================================================== --}}
        {{-- PROVISIONALES VENTA --}}
        {{-- ===================================================== --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="hProvV">
                <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#cProvV">

                    <div class="w-100 d-flex justify-content-between">
                        <div>
                            <strong>Provisionales Venta</strong>
                            <small class="text-muted ms-2">
                                ({{ ($ventaProvisionalesList ?? collect())->count() }} registros)
                            </small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Total:</small>
                            <strong>
                                {{ number_format((float)(($ventaProvisionalesList ?? collect())->sum('monto')),2,'.','') }}
                            </strong>
                        </div>
                    </div>

                </button>
            </h2>

            <div id="cProvV" class="accordion-collapse collapse"
                data-bs-parent="#accReporteCaja">
                <div class="accordion-body p-2">

                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:40px;">Ver</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end">Principal</th>
                                <th class="text-end">Depósito</th>
                                <th class="text-end">Consorcio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ventaProvisionalesList ?? [] as $p)
                            <tr>
                                <td class="text-center">
                                    <a href="javascript:void(0)"
                                    class="btn-view-venta-provisional text-muted"
                                    data-id="{{ $p->id }}"
                                    title="Ver documento">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                                <td>{{ $p->fecha_provisional }}</td>
                                <td>{{ $p->cliente_nombre }}</td>
                                <td class="text-end">{{ number_format((float)$p->monto,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$p->importe_p,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$p->importe_d,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$p->importe_c,2,'.','') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">Sin provisionales</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="4" class="text-end">TOTALES:</td>
                                <td class="text-end">{{ number_format((float)(($ventaProvisionalesList ?? collect())->sum('importe_p')),2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)(($ventaProvisionalesList ?? collect())->sum('importe_d')),2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)(($ventaProvisionalesList ?? collect())->sum('importe_c')),2,'.','') }}</td>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        </div>


        {{-- ===================================================== --}}
        {{-- PROVISIONALES COMPRA --}}
        {{-- ===================================================== --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="hProvC">
                <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#cProvC">

                    <div class="w-100 d-flex justify-content-between">
                        <div>
                            <strong>Provisionales Compra</strong>
                            <small class="text-muted ms-2">
                                ({{ ($compraProvisionalesList ?? collect())->count() }} registros)
                            </small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Total:</small>
                            <strong>
                                {{ number_format((float)(($compraProvisionalesList ?? collect())->sum('monto')),2,'.','') }}
                            </strong>
                        </div>
                    </div>

                </button>
            </h2>

            <div id="cProvC" class="accordion-collapse collapse"
                data-bs-parent="#accReporteCaja">
                <div class="accordion-body p-2">

                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:40px;">Ver</th>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end">Principal</th>
                                <th class="text-end">Depósito</th>
                                <th class="text-end">Consorcio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($compraProvisionalesList ?? [] as $pc)
                            <tr>
                                <td class="text-center">
                                    <a href="javascript:void(0)"
                                    class="btn-view-compra-provisional text-muted"
                                    data-id="{{ $pc->id }}"
                                    title="Ver documento">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                                <td>{{ $pc->fecha_provisional}}</td>
                                <td>{{ $pc->proveedor_nombre }}</td>
                                <td class="text-end">{{ number_format((float)$pc->monto,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$pc->importe_p,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$pc->importe_d,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$pc->importe_c,2,'.','') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">Sin provisionales</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="4" class="text-end">TOTALES:</td>
                                <td class="text-end">{{ number_format((float)(($compraProvisionalesList ?? collect())->sum('importe_p')),2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)(($compraProvisionalesList ?? collect())->sum('importe_d')),2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)(($compraProvisionalesList ?? collect())->sum('importe_c')),2,'.','') }}</td>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- GASTOS --}}
        {{-- ===================================================== --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="cGasto">
                <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#cGastoContent">

                    <div class="w-100 d-flex justify-content-between">
                        <div>
                            <strong>Gastos</strong>
                            <small class="text-muted ms-2">
                                ({{ ($gastosList ?? collect())->count() }} registros)
                            </small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Total:</small>
                            <strong>
                                {{ number_format((float)(($gastosList?? collect())->sum('monto')),2,'.','') }}
                            </strong>
                        </div>
                    </div>

                </button>
            </h2>

            <div id="cGastoContent" class="accordion-collapse collapse"
                data-bs-parent="#accReporteCaja">
                <div class="accordion-body p-2">

                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:40px;">Ver</th>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end">Principal</th>
                                <th class="text-end">Depósito</th>
                                <th class="text-end">Consorcio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gastosList ?? [] as $pc)
                            <tr>
                                <td class="text-center">
                                    <a href="javascript:void(0)"
                                    class="btn-view-gasto text-muted"
                                    data-id="{{ $pc->id }}"
                                    title="Ver documento">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                                <td>{{ $pc->fecha_gasto}}</td>
                                <td>{{ $pc->descripcion }}</td>
                                <td class="text-end">{{ number_format((float)$pc->monto,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$pc->importe_p,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$pc->importe_d,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$pc->importe_c,2,'.','') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">Sin gastos</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="4" class="text-end">TOTALES:</td>
                                <td class="text-end">{{ number_format((float)(($gastosList ?? collect())->sum('importe_p')),2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)(($gastosList ?? collect())->sum('importe_d')),2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)(($gastosList ?? collect())->sum('importe_c')),2,'.','') }}</td>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- GASTOS - EMPLEADOS (Planilla) --}}
        {{-- ===================================================== --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="hGastosEmpleados">
                <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#cGastosEmpleados">

                    <div class="w-100 d-flex justify-content-between">
                        <div>
                            <strong>GASTOS - EMPLEADOS</strong>
                            <small class="text-muted ms-2">
                                ({{ (($adelantosList ?? collect())->count() + ($prestamosList ?? collect())->count() + ($pagosPrestamosList ?? collect())->count() + ($pagosPlanillaList ?? collect())->count()) }} registros)
                            </small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Total:</small>
                            <strong>
                                {{ number_format($gastosEmpleadosTotal, 2, '.', '') }}
                            </strong>
                        </div>
                    </div>

                </button>
            </h2>

            <div id="cGastosEmpleados" class="accordion-collapse collapse"
                data-bs-parent="#accReporteCaja">
                <div class="accordion-body p-2">

                    {{-- Adelantos --}}
                    @if(($adelantosList ?? collect())->count() > 0)
                    <h6 class="mb-2 text-muted"><strong>Adelantos</strong></h6>
                    <table class="table table-sm table-bordered mb-3">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Empleado</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end">Principal</th>
                                <th class="text-end">Depósito</th>
                                <th class="text-end">Consorcio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($adelantosList ?? [] as $a)
                            <tr>
                                <td>{{ $a->fecha }}</td>
                                <td>{{ $a->empleado->nombre ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float)$a->monto, 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)$a->importe_p, 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)$a->importe_d, 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)$a->importe_c, 2, '.', '') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="3" class="text-end">TOTAL:</td>
                                <td class="text-end">{{ number_format((float)($adelantoP), 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)($adelantoD), 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)($adelantoC), 2, '.', '') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                    @endif

                    {{-- Prestamos Otorgados --}}
                    @if(($prestamosList ?? collect())->count() > 0)
                    <h6 class="mb-2 text-muted"><strong>Préstamos Otorgados</strong></h6>
                    <table class="table table-sm table-bordered mb-3">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Empleado</th>
                                <th class="text-end">Monto Original</th>
                                <th class="text-end">Principal</th>
                                <th class="text-end">Depósito</th>
                                <th class="text-end">Consorcio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prestamosList ?? [] as $p)
                            <tr>
                                <td>{{ $p->fecha_prestamo }}</td>
                                <td>{{ $p->empleado->nombre ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float)$p->monto_original, 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)$p->importe_p, 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)$p->importe_d, 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)$p->importe_c, 2, '.', '') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="3" class="text-end">TOTAL:</td>
                                <td class="text-end">{{ number_format((float)($prestamoP), 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)($prestamoD), 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)($prestamoC), 2, '.', '') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                    @endif

                    {{-- Pagos de Prestamos (van a consortium) --}}
                    @if(($pagosPrestamosList ?? collect())->count() > 0)
                    <h6 class="mb-2 text-muted"><strong>Pagos de Préstamos (Consorcio)</strong></h6>
                    <table class="table table-sm table-bordered mb-3">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Empleado</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end">Principal</th>
                                <th class="text-end">Depósito</th>
                                <th class="text-end">Consorcio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pagosPrestamosList ?? [] as $pp)
                            <tr>
                                <td>{{ $pp->fecha_pago }}</td>
                                <td>{{ $pp->prestamo?->empleado?->nombre ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float)$pp->monto_pagado, 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)$pp->importe_p, 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)$pp->importe_d, 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)$pp->importe_c, 2, '.', '') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="3" class="text-end">TOTAL:</td>
                                <td class="text-end">{{ number_format((float)($pagoPrestamoP), 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)($pagoPrestamoD), 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)($pagoPrestamoC), 2, '.', '') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                    @endif

                    {{-- Pago Planilla --}}
                    @if(($pagosPlanillaList ?? collect())->count() > 0)
                    <h6 class="mb-2 text-muted"><strong>Pago Planilla</strong></h6>
                    <table class="table table-sm table-bordered mb-3">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Empleado</th>
                                <th class="text-end">Total Pagar</th>
                                <th class="text-end">Principal</th>
                                <th class="text-end">Depósito</th>
                                <th class="text-end">Consorcio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pagosPlanillaList ?? [] as $pg)
                            <tr>
                                <td>{{ $pg->fecha_pago }}</td>
                                <td>{{ $pg->empleado->nombre ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float)$pg->total_pagar, 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)$pg->importe_p, 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)$pg->importe_d, 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)$pg->importe_c, 2, '.', '') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="3" class="text-end">TOTAL:</td>
                                <td class="text-end">{{ number_format((float)($pagoPlanillaP), 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)($pagoPlanillaD), 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)($pagoPlanillaC), 2, '.', '') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                    @endif

                </div>
            </div>
        </div>


        {{-- ===================================================== --}}
        {{-- COSTOS --}}
        {{-- ===================================================== --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="hCostos">
                <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#cCostos">

                    <div class="w-100 d-flex justify-content-between">
                        <div>
                            <strong>Costos</strong>
                            <small class="text-muted ms-2">
                                ({{ ($costosList ?? collect())->count() }} registros)
                            </small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Total:</small>
                            <strong>
                                {{ number_format((float)(($costosList ?? collect())->sum('monto')),2,'.','') }}
                            </strong>
                        </div>
                    </div>

                </button>
            </h2>

            <div id="cCostos" class="accordion-collapse collapse"
                data-bs-parent="#accReporteCaja">
                <div class="accordion-body p-2">

                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:40px;">Ver</th>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Responsable</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end">Principal</th>
                                <th class="text-end">Depósito</th>
                                <th class="text-end">Consorcio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($costosList ?? [] as $co)
                            <tr>
                                <td class="text-center">
                                    <a href="javascript:void(0)"
                                    class="btn-view-costo text-muted"
                                    data-id="{{ $co->id }}"
                                    title="Ver documento">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                                <td>{{ $co->fecha_costo }}</td>
                                <td>{{ $co->descripcion }}</td>
                                <td>{{ $co->responsable }}</td>
                                <td class="text-end">{{ number_format((float)$co->monto,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$co->importe_p,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$co->importe_d,2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)$co->importe_c,2,'.','') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center text-muted">Sin costos</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="5" class="text-end">TOTALES:</td>
                                <td class="text-end">{{ number_format((float)(($costosList ?? collect())->sum('importe_p')),2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)(($costosList ?? collect())->sum('importe_d')),2,'.','') }}</td>
                                <td class="text-end">{{ number_format((float)(($costosList ?? collect())->sum('importe_c')),2,'.','') }}</td>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        </div>
    </div>

</div>
@else
    <div class="text-muted small mt-1">
        Seleccione un rango de fechas y presione <strong>Filtrar</strong>.
    </div>
@endif
<div id="modalContainer"></div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrarDetallado');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {

            const fechaInicio = document.getElementById('fecha_inicio_detallado').value;
            const fechaFin = document.getElementById('fecha_fin_detallado').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            loader.classList.remove('d-none');
            const reporte=document.getElementById('reporteTab2')
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            const url = new URL("{{ route('reportes.caja.detallado') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.text())
            .then(html => {
                reporte.innerHTML = html;
            })
            .catch(error => {
                console.error('Error AJAX:', error);
                alert('Ocurrió un error al cargar el reporte');
            })
            .finally(() => {
                loader.classList.add('d-none'); // ✅ OCULTAR
            });
        });
        
        function setFechaActualInputs() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            const fechaActual = `${year}-${month}-${day}`;

            const inputInicio = document.getElementById('fecha_inicio_detallado');
            const inputFin = document.getElementById('fecha_fin_detallado');

            if(inputInicio && !inputInicio.value) {
                inputInicio.value = fechaActual;
            }
            if(inputFin && !inputFin.value) {
                inputFin.value = fechaActual;
            }
        }

        setFechaActualInputs();        
    });

    document.body.addEventListener('click', function(e) {
        // ===========================
        // VENTAS
        // ===========================
        if (e.target.closest('.btn-view-venta')) {
            const id = e.target.closest('.btn-view-venta').dataset.id;
            const url = "{{ route('ventas.ver', ':id') }}".replace(':id', id);
            loadModal(url);
        }

        // ===========================
        // COMPRAS
        // ===========================
        if (e.target.closest('.btn-view-compra')) {
            const id = e.target.closest('.btn-view-compra').dataset.id;
            const url = "{{ route('compras.ver', ':id') }}".replace(':id', id);
            loadModal(url);
        }

        // ===========================
        // PROVISIONALES VENTA
        // ===========================
        if (e.target.closest('.btn-view-venta-provisional')) {
            const id = e.target.closest('.btn-view-venta-provisional').dataset.id;
            const url = "{{ route('venta-provisionales.ver', ':id') }}".replace(':id', id);
            loadModal(url);
        }

        // ===========================
        // PROVISIONALES COMPRA
        // ===========================
        if (e.target.closest('.btn-view-compra-provisional')) {
            const id = e.target.closest('.btn-view-compra-provisional').dataset.id;
            const url = "{{ route('compra-provisionales.ver', ':id') }}".replace(':id', id);
            loadModal(url);
        }

        if (e.target.closest('.btn-view-gasto')) {
            const id = e.target.closest('.btn-view-gasto').dataset.id;
            const url = "{{ route('gastos.ver', ':id') }}".replace(':id', id);
            loadModal(url);
        }

        // ===========================
        // COSTOS
        // ===========================
        if (e.target.closest('.btn-view-costo')) {
            const id = e.target.closest('.btn-view-costo').dataset.id;
            const url = "{{ route('costos.ver', ':id') }}".replace(':id', id);
            loadModal(url);
        }

    });


    function loadModal(url) {
        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Error al cargar');
                return response.text();
            })
            .then(html => {
                let container = document.getElementById('modalContainer');
                container.innerHTML = html;

                const modalEl = container.querySelector('.modal');
                const modal = new bootstrap.Modal(modalEl);
                container.querySelector('.btn-duplicate-venta')?.remove();
                modal.show();
            })
            .catch(err => {
                console.error(err);
                alert('No se pudo cargar el documento.');
            });
    }


</script>
@endpush
