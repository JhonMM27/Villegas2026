<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Opción</th>
                <th>ID</th>
                <th>Fecha</th>
                <th class="text-end">Items</th>
                <th class="text-end">Salida Kg</th>
                <th>Producto</th>
                <th>Empaque</th>
                <th>Cliente</th>
            </tr>
        </thead>

        <tbody>

            @php
                $totalItemsGeneral = 0;
                $totalRegistros = 0;
                $totalSalidaKg = 0;
            @endphp

            @forelse($reportes as $item)

                @php
                    $fechaformulacion = \Carbon\Carbon::parse($item->fecha)->format('Y-m-d');
                @endphp

                <tr>
                    <td>
                        <button class="btn btn-sm btn-info btn-view-formulacion" data-id="{{ $item->id }}">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                    <td>{{ $item->id }}</td>
                    <td>{{ $fechaformulacion }}</td>
                    <td class="text-end">{{ $item->detalles_count }}</td>
                    <td class="text-end">{{ number_format($item->salida_kg, 2) }}</td>
                    <td>{{ $item->producto_nombre }}</td>
                    <td>{{ $item->producto_empaque }}</td>
                    <td>{{ $item->cliente_nombre }}</td>
                </tr>

                @php
                    $totalRegistros++;
                    $totalItemsGeneral += (int) $item->detalles_count;
                    $totalSalidaKg += (float) $item->salida_kg;
                @endphp

            @empty
                <tr>
                    <td colspan="8" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse
        </tbody>

        @if(count($reportes) > 0)
        <tfoot>
            <tr class="table-dark fw-bold">
                <td class="text-end">TOTAL</td>
                <td class="text-end">{{ $totalRegistros }}</td>
                <td></td>
                <td class="text-end">{{ $totalItemsGeneral }}</td>
                <td class="text-end">{{ number_format($totalSalidaKg, 2) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif

    </table>
</div>

<div id="modalContainerFechas"></div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrarFechas');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {

            const fechaInicio = document.getElementById('fecha_inicio_fechas').value;
            const fechaFin = document.getElementById('fecha_fin_fechas').value;

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

            const url = new URL("{{ route('reportes.formulaciones_fecha') }}", window.location.origin);
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

        const btnExportar = document.getElementById('btnExportarFechas');

        btnExportar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio_fechas').value;
            const fechaFin = document.getElementById('fecha_fin_fechas').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas para exportar');
                return;
            }

            const url = new URL("{{ route('reportes.formulaciones_fecha.export') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);

            window.open(url.toString(), '_blank'); // abre en nueva pestaña y descarga
        });

        document.getElementById('btnPdfFechas').addEventListener('click', function (e) {
            e.preventDefault();
            const fechaInicio = document.getElementById('fecha_inicio_fechas').value;
            const fechaFin = document.getElementById('fecha_fin_fechas').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            const url = `{{ route('reportes.formulaciones_fecha.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            window.open(url, '_blank');
        });

        document.body.addEventListener('click', function(e) {
            if (e.target && (e.target.matches('.btn-view-formulacion') || e.target.closest('.btn-view-formulacion'))) {
                const button = e.target.closest('.btn-view-formulacion');
                const formulacionId = button.getAttribute('data-id');
                if (!formulacionId) return;

                const url = "{{ route('formulaciones.ver', ':id') }}".replace(':id', formulacionId);

                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('No se pudo cargar la formulacion');
                        return response.text();
                    })
                    .then(html => {
                        let modalContainer = document.getElementById('modalContainer');
                        if (!modalContainer) {
                            modalContainer = document.createElement('div');
                            modalContainer.id = 'modalContainerFechas';
                            document.body.appendChild(modalContainer);
                        }
                        modalContainer.innerHTML = html;

                        const modalEl = modalContainer.querySelector('.modal');
                        const modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Ocurrió un error al cargar el detalle.');
                    });
            }
        });

        function setFechaActualInputs() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            const fechaActual = `${year}-${month}-${day}`;

            const inputInicio = document.getElementById('fecha_inicio_fechas');
            const inputFin = document.getElementById('fecha_fin_fechas');

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
