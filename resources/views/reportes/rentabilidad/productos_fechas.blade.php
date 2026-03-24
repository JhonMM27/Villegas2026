<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Línea</th>

                <th class="text-end">Empaque Prod</th>
                <th>Empaques vendidos</th>

                <th class="text-end">Sacos</th>
                <th class="text-end">Kg</th>

                <th class="text-end">Importe</th>
                <th class="text-end">Costo</th>
                <th class="text-end">Valor</th>
                <th class="text-end">Rentab %</th>
            </tr>
        </thead>

        <tbody>
            @php
                $tSacos = 0.0;
                $tKg = 0.0;
                $tImporte = 0.0;
                $tCosto = 0.0;
                $tValor = 0.0;
            @endphp

            @forelse($reportes as $r)
                @php
                    $tSacos   += (float)($r->salida_saco ?? 0);
                    $tKg      += (float)($r->salida_kg ?? 0);
                    $tImporte += (float)($r->importe ?? 0);
                    $tCosto   += (float)($r->costo ?? 0);
                    $tValor   += (float)($r->valor ?? 0);
                @endphp

                <tr>
                    <td>{{ $r->producto_nombre }}</td>
                    <td>{{ $r->producto_linea_nombre }}</td>

                    <td class="text-end">{{ number_format((float)($r->empaque_producto ?? 0), 2, '.', '') }}</td>
                    <td class="text-muted">{{ $r->empaques_vendidos ?? '' }}</td>

                    <td class="text-end">{{ number_format((float)($r->salida_saco ?? 0), 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format((float)($r->salida_kg ?? 0), 2, '.', '') }}</td>

                    <td class="text-end">{{ number_format((float)($r->importe ?? 0), 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format((float)($r->costo ?? 0), 4, '.', '') }}</td>
                    <td class="text-end">{{ number_format((float)($r->valor ?? 0), 4, '.', '') }}</td>
                    <td class="text-end">{{ number_format((float)($r->rentab_pct ?? 4), 2, '.', '') }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse
        </tbody>

        @if($reportes->count() > 0)
            @php
                $rentabGen = $tImporte > 0 ? ($tValor / $tImporte) * 100 : 0;
            @endphp
            <tfoot>
                <tr class="table-dark fw-bold">
                    <td colspan="4" class="text-end">TOTAL GENERAL</td>
                    <td class="text-end">{{ number_format($tSacos, 4, '.', '') }}</td>
                    <td class="text-end">{{ number_format($tKg, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($tImporte, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($tCosto, 4, '.', '') }}</td>
                    <td class="text-end">{{ number_format($tValor, 4, '.', '') }}</td>
                    <td class="text-end">{{ number_format($rentabGen, 2, '.', '') }}%</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrarProducto');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {

            const fechaInicio = document.getElementById('fecha_inicio_producto').value;
            const fechaFin = document.getElementById('fecha_fin_producto').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            loader.classList.remove('d-none');
            const reporte=document.getElementById('reporteTab4')
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            const url = new URL("{{ route('reportes.rentabilidad_productos_fechas') }}", window.location.origin);
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


        document.getElementById('btnPdfProducto').addEventListener('click', function (e) {
            e.preventDefault();

            const fechaInicio = document.getElementById('fecha_inicio_producto').value;
            const fechaFin = document.getElementById('fecha_fin_producto').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            let url = `{{ route('reportes.rentabilidad_productos_fechas.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            window.open(url, '_blank');
        });

        function setFechaActualInputs() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            const fechaActual = `${year}-${month}-${day}`;

            const inputInicio = document.getElementById('fecha_inicio_producto');
            const inputFin = document.getElementById('fecha_fin_producto');

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
