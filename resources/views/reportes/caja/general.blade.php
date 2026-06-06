@php
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

            <tr class="table-secondary fw-bold">
                <td>Ingresos (Ventas + Provisionales)</td>
                <td class="text-end" colspan="2"></td>
                <td class="text-end">{{ number_format($ingP, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($ingD, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($ingC, 2, '.', '') }}</td>
            </tr>

            <tr class="table-secondary fw-bold">
                <td>Egresos (Compras + Provisionales + Gastos + Planilla)</td>
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
</div>
@else
    <div class="text-muted small mt-1">
        Seleccione un rango de fechas y presione <strong>Filtrar</strong>.
    </div>
@endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrar');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {

            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            loader.classList.remove('d-none');
            const reporte=document.getElementById('reporteTab1')
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            const url = new URL("{{ route('reportes.caja.general') }}", window.location.origin);
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

        document.getElementById('btnPdf').addEventListener('click', function (e) {
            e.preventDefault();

            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            const url = `{{ route('reportes.caja.general.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            window.open(url, '_blank');
        });

        function setFechaActualInputs() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            const fechaActual = `${year}-${month}-${day}`;

            const inputInicio = document.getElementById('fecha_inicio');
            const inputFin = document.getElementById('fecha_fin');

            if(inputInicio && !inputInicio.value) {
                inputInicio.value = fechaActual;
            }
            if(inputFin && !inputFin.value) {
                inputFin.value = fechaActual;
            }
        }

        setFechaActualInputs();        
    });


</script>
@endpush
