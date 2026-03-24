<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th class="text-end">Empaque</th>
                <th>Línea</th>
                <th class="text-end">Contado</th>
                <th class="text-end">Crédito</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>

        <tbody>
            @php
                $tContado = 0.0;
                $tCredito = 0.0;
                $tTotal   = 0.0;

                // Agrupar por linea_id (mantén orderBy(l.nombre) en la consulta para que salga ordenado)
                $grupos = collect($reportes)->groupBy(fn($x) => (string)($x->linea_id ?? '0'));
            @endphp

            @forelse($grupos as $lineaId => $items)
                @php
                    $lineaNombre = (string)($items->first()->linea_nombre ?? 'SIN LÍNEA');

                    $subContado = 0.0;
                    $subCredito = 0.0;
                    $subTotal   = 0.0;
                @endphp

                @foreach($items as $r)
                    @php
                        $contado = (float)($r->contado ?? 0);
                        $credito = (float)($r->credito ?? 0);
                        $total   = (float)($r->total ?? ($contado + $credito));

                        $subContado += $contado;
                        $subCredito += $credito;
                        $subTotal   += $total;

                        $tContado += $contado;
                        $tCredito += $credito;
                        $tTotal   += $total;
                    @endphp

                    <tr>
                        <td>{{ $r->producto_id }}</td>
                        <td>{{ $r->producto_nombre }}</td>
                        <td class="text-end">{{ number_format((float)($r->empaque_producto ?? 0), 2, '.', '') }}</td>
                        <td>{{ $r->linea_nombre }}</td>

                        <td class="text-end">{{ number_format($contado, 2, '.', '') }}</td>
                        <td class="text-end">{{ number_format($credito, 2, '.', '') }}</td>
                        <td class="text-end">{{ number_format($total, 2, '.', '') }}</td>
                    </tr>
                @endforeach

                {{-- Subtotal por línea --}}
                <tr class="table-secondary fw-bold">
                    <td colspan="4" class="text-end">TOTAL {{ $lineaNombre }}</td>
                    <td class="text-end">{{ number_format($subContado, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($subCredito, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($subTotal, 2, '.', '') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse
        </tbody>

        @if(count($reportes) > 0)
            <tfoot>
                <tr class="table-dark fw-bold">
                    <td colspan="4" class="text-end">TOTAL GENERAL</td>
                    <td class="text-end">{{ number_format($tContado, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($tCredito, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($tTotal, 2, '.', '') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrarProductoVentas');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {

            const fechaInicio = document.getElementById('fecha_inicio_producto_ventas').value;
            const fechaFin = document.getElementById('fecha_fin_producto_ventas').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            loader.classList.remove('d-none');
            const reporte=document.getElementById('reporteTab3')
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            const url = new URL("{{ route('reportes.rentabilidad_productos_ventas_fechas') }}", window.location.origin);
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


        document.getElementById('btnPdfProductoVentas').addEventListener('click', function (e) {
            e.preventDefault();

            const fechaInicio = document.getElementById('fecha_inicio_producto_ventas').value;
            const fechaFin = document.getElementById('fecha_fin_producto_ventas').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            let url = `{{ route('reportes.rentabilidad_productos_ventas_fechas.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            window.open(url, '_blank');
        });

        function setFechaActualInputs() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            const fechaActual = `${year}-${month}-${day}`;

            const inputInicio = document.getElementById('fecha_inicio_producto_ventas');
            const inputFin = document.getElementById('fecha_fin_producto_ventas');

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
