<div class="table-responsive">
    <table class="table table-bordered table-sm table-app">

    <thead>
    <tr>
        <th>Número</th>
        <th>Fecha</th>
        <th>Item</th>
        <th>Fórmula</th>
        <th>Preparada</th>
        <th>Empaque</th>
        <th class="text-end">Total Kg</th>
        <th class="text-end">Total Sacos</th>
        <th class="text-end">Total Soles</th>
    </tr>
    </thead>

    <tbody>

    @php
        $productoActual = null;

        $subIngKg = 0;
        $subIngSaco = 0;
        $subIngSol = 0;

        $totIngKg = 0;
        $totIngSaco = 0;
        $totIngSol = 0;
    @endphp

    @forelse($reportes as $row)
        {{-- CAMBIO DE PRODUCTO --}}
        @if($productoActual !== null && $productoActual !== $row->producto_nombre)
            <tr class="fw-bold" style="border-top: 3px double #000 !important;">
                <td colspan="6" class="text-end">TOTAL {{ $productoActual }}</td>
                <td class="text-end">{{ number_format($subIngKg, 2) }}</td>
                <td class="text-end">{{ number_format($subIngSaco, 4) }}</td>
                <td class="text-end">{{ number_format($subIngSol, 4) }}</td>
            </tr>
            @php
               $subIngKg = $subIngSaco = $subIngSol = 0;
            @endphp
        @endif

        {{-- FILA NORMAL --}}
        <tr>
            <td>{{ $row->id }}</td>
            <td>{{ \Carbon\Carbon::parse($row->fecha)->format('d/m/Y') }}</td>
            <td>{{ $row->items }}</td>
            <td>{{ $row->formulacion_id }}</td>
            <td>{{ $row->producto_nombre }}</td>
            <td>{{ $row->producto_empaque }}</td>
            <td class="text-end">{{ number_format($row->ingreso_kg, 2) }}</td>
            <td class="text-end">{{ number_format($row->ingreso_saco, 4) }}</td>
            <td class="text-end">{{ number_format($row->ingreso_soles, 4) }}</td>
        </tr>

        @php
            $productoActual = $row->producto_nombre;

            $subIngKg += $row->ingreso_kg;
            $subIngSaco += $row->ingreso_saco;
            $subIngSol += $row->ingreso_soles;

            $totIngKg += $row->ingreso_kg;
            $totIngSaco += $row->ingreso_saco;
            $totIngSol += $row->ingreso_soles;
        @endphp

    @empty

        <tr>
            <td colspan="9" class="text-center">No se encontraron registros</td>
        </tr>

    @endforelse
    @if(count($reportes) > 0)
    {{-- ÚLTIMO SUBTOTAL --}}
    <tr class="fw-bold" style="border-top: 3px double #000 !important;">
        <td colspan="6" class="text-end">TOTAL {{ $productoActual }}</td>
        <td class="text-end">{{ number_format($subIngKg, 2) }}</td>
        <td class="text-end">{{ number_format($subIngSaco, 4) }}</td>
        <td class="text-end">{{ number_format($subIngSol, 4) }}</td>
    </tr>

    </tbody>
    
    <tfoot class="table-dark fw-bold">
    <tr>
        <td colspan="6" class="text-end">TOTAL GENERAL</td>
        <td class="text-end">{{ number_format($totIngKg, 2) }}</td>
        <td class="text-end">{{ number_format($totIngSaco, 4) }}</td>
        <td class="text-end">{{ number_format($totIngSol, 4) }}</td>
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

            const url = new URL("{{ route('reportes.preparadas_fechas') }}", window.location.origin);
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

            const url = new URL("{{ route('reportes.preparadas_fechas.export') }}", window.location.origin);
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

            const url = `{{ route('reportes.preparadas_fechas.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            window.open(url, '_blank');
        });
        /*
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
        */
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