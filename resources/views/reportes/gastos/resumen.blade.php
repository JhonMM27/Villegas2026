<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Recibo</th>
                <th>Nro. Interno</th>
                <th>Tipo</th>
                <th>Fecha</th>
                <th>Descripción</th>
                <th>Responsable</th>
                <th class="text-end">Principal</th>
                <th class="text-end">Depósito</th>
                <th class="text-end">Consorcio</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalPrincipal = 0;
                $totalDeposito = 0;
                $totalConsorc = 0;

                $tipoActual = null;
                $subPrincipal = 0;
                $subDeposito = 0;
                $subConsorc = 0;
            @endphp

            @forelse($reportes as $r)
                @php
                    $tipo = $r->tipo ?? 'SIN TIPO';
                    $principal = (float)($r->importe_p ?? 0);
                    $deposito = (float)($r->importe_d ?? 0);
                    $consorc = (float)($r->importe_c ?? 0);
                    $fecha = $r->fecha_gasto ? \Carbon\Carbon::parse($r->fecha_gasto)->format('d/m/Y H:i') : '';
                @endphp

                @if($tipoActual !== null && $tipo !== $tipoActual)
                    <tr style="font-weight:bold; background:#f8f9fa;">
                        <td colspan="6" class="text-end">SUBTOTAL {{ $tipoActual }}:</td>
                        <td class="text-end">{{ number_format($subPrincipal, 2) }}</td>
                        <td class="text-end">{{ number_format($subDeposito, 2) }}</td>
                        <td class="text-end">{{ number_format($subConsorc, 2) }}</td>
                    </tr>

                    @php
                        $subPrincipal = 0;
                        $subDeposito = 0;
                        $subConsorc = 0;
                    @endphp
                @endif

                @php
                    if ($tipoActual === null) $tipoActual = $tipo;
                    if ($tipo !== $tipoActual) $tipoActual = $tipo;

                    $subPrincipal += $principal;
                    $subDeposito += $deposito;
                    $subConsorc += $consorc;

                    $totalPrincipal += $principal;
                    $totalDeposito += $deposito;
                    $totalConsorc += $consorc;
                @endphp

                <tr>
                    <td>{{ $r->numero_recibo }}</td>
                    <td>{{ $r->numero_interno }}</td>
                    <td>{{ $tipo }}</td>
                    <td>{{ $fecha }}</td>
                    <td>{{ $r->descripcion }}</td>
                    <td>{{ $r->responsable }}</td>
                    <td class="text-end">{{ number_format($principal, 2) }}</td>
                    <td class="text-end">{{ number_format($deposito, 2) }}</td>
                    <td class="text-end">{{ number_format($consorc, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse

            @if(count($reportes) > 0)
                <tr style="font-weight:bold; background:#f8f9fa;">
                    <td colspan="6" class="text-end">SUBTOTAL {{ $tipoActual }}:</td>
                    <td class="text-end">{{ number_format($subPrincipal, 2) }}</td>
                    <td class="text-end">{{ number_format($subDeposito, 2) }}</td>
                    <td class="text-end">{{ number_format($subConsorc, 2) }}</td>
                </tr>
            @endif
        </tbody>

        @if(count($reportes) > 0)
        <tfoot>
            <tr style="font-weight:bold; border-top:2px solid #000;">
                <th colspan="6" class="text-end">TOTAL GENERAL:</th>
                <th class="text-end">{{ number_format($totalPrincipal, 2) }}</th>
                <th class="text-end">{{ number_format($totalDeposito, 2) }}</th>
                <th class="text-end">{{ number_format($totalConsorc, 2) }}</th>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<div id="modalContainerGastos"></div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrar');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio')?.value;
            const fechaFin = document.getElementById('fecha_fin')?.value;
            const tipo = document.getElementById('tipo')?.value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione fecha inicio y fecha fin');
                return;
            }

            loader.classList.remove('d-none');
            const reporte = document.getElementById('reporteTab1');
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            const url = new URL("{{ route('reportes.gastos.resumen') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);
            if (tipo) url.searchParams.append('tipo', tipo);

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
                loader.classList.add('d-none');
            });
        });

        const btnExportar = document.getElementById('btnExportar');

        btnExportar.addEventListener('click', function () {
            const fechaInicio = document.getElementById('fecha_inicio')?.value;
            const fechaFin = document.getElementById('fecha_fin')?.value;
            const tipo = document.getElementById('tipo')?.value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione fecha inicio y fecha fin');
                return;
            }

            const url = new URL(
                "{{ route('reportes.gastos.resumen.export') }}",
                window.location.origin
            );

            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);
            if (tipo) url.searchParams.append('tipo', tipo);

            window.open(url.toString(), '_blank');
        });

        document.getElementById('btnPdf').addEventListener('click', function (e) {
            e.preventDefault();

            const fechaInicio = document.getElementById('fecha_inicio')?.value;
            const fechaFin = document.getElementById('fecha_fin')?.value;
            const tipo = document.getElementById('tipo')?.value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione fecha inicio y fecha fin');
                return;
            }

            const url = new URL("{{ route('reportes.gastos.resumen.imprimir') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);
            if (tipo) url.searchParams.append('tipo', tipo);

            window.open(url.toString(), '_blank');
        });

        function setFechaActualInputs() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            const fechaActual = `${year}-${month}-${day}`;

            const inputInicio = document.getElementById('fecha_inicio');
            const inputFin = document.getElementById('fecha_fin');

            if (inputInicio && !inputInicio.value) {
                inputInicio.value = fechaActual;
            }
            if (inputFin && !inputFin.value) {
                inputFin.value = fechaActual;
            }
        }
        setFechaActualInputs();
    });
</script>
@endpush
