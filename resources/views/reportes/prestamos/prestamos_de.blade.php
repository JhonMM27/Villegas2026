<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Opciones</th>
                <th>Usuario</th>
                <th>Fecha</th>
                <th>Origen</th>
                <th>Comprobante</th>
                <th>Producto</th>
                <th>Empaque</th>
                <th>Unidad</th>                
                <th>Cantidad Prestada</th>
                <th>Cantidad Devuelta</th>
                <th>Saldo</th>
            </tr>
        </thead>

        <tbody>

            @forelse($reportes as $item)
                {{-- FILA NORMAL --}}
                <tr>
                    <td>
                        <button class="btn btn-sm btn-info btn-view-prestamo-origen" data-id="{{ $item->id }}">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                    <td>{{ $item->user_nombre}}</td>
                    <td>{{ $item->fecha_prestamo }}</td>
                    <td>{{ $item->cliente_nombre }}</td>
                    <td>{{ $item->comprobante_tipo_codigo }} {{ $item->serie }}-{{ $item->correlativo }}</td>
                    <td>{{ $item->producto_nombre}}</td>
                    <td>{{ $item->producto_empaque}}</td>
                    <td>{{ $item->unidad_nombre}}</td>
                    <td class="text-end">{{ $item->cantidad_prestada}}</td>
                    <td class="text-end">{{ $item->cantidad_devuelta}}</td>
                    <td class="text-end">{{ $item->saldo }}</td>
                </tr>
            @empty

                <tr>
                    <td colspan="11" class="text-center">No se encontraron registros</td>
                </tr>

            @endforelse
        </tbody>
    </table>
</div>
<div id="modalContainerOrigen"></div>
@push('scripts')
<script>

    document.addEventListener('DOMContentLoaded', function() {        
        const btnFiltrar = document.getElementById('btnFiltrarOrigen');
        const loader = document.getElementById('loadingOverlay');
        
        btnFiltrar.addEventListener('click', function() {

            const fechaInicio = document.getElementById('fecha_inicio_origen').value;
            const fechaFin = document.getElementById('fecha_fin_origen').value;
            const clienteOrigenId = document.getElementById('cliente_origen_id').value;

            if (!fechaInicio || !fechaFin || !clienteOrigenId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Datos incompletos',
                    text: 'Seleccione ambas fechas y el cliente origen para filtrar',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            loader.classList.remove('d-none');
            const reporte=document.getElementById('reporteTab2')
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            const url = new URL("{{ route('reportes.prestamos_de_pendientes') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);
            url.searchParams.append('cliente_origen_id', clienteOrigenId);

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

        document.getElementById('btnPdfOrigen').addEventListener('click', function (e) {
            e.preventDefault();
            const fechaInicio = document.getElementById('fecha_inicio_origen').value;
            const fechaFin = document.getElementById('fecha_fin_origen').value;
            const clienteOrigenId = document.getElementById('cliente_origen_id').value;

            if (!fechaInicio || !fechaFin || !clienteOrigenId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Datos incompletos',
                    text: 'Seleccione ambas fechas y el cliente origen para filtrar',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            const url = `{{ route('reportes.prestamos_de_pendientes.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&cliente_origen_id=${clienteOrigenId}`;

            window.open(url, '_blank');
        });

        document.body.addEventListener('click', function(e) {
            if (e.target && (e.target.matches('.btn-view-prestamo-origen') || e.target.closest('.btn-view-prestamo-origen'))) {
                const button = e.target.closest('.btn-view-prestamo-origen');
                const prestamoId = button.getAttribute('data-id');
                if (!prestamoId) return;

                const url = "{{ route('prestamos.ver', ':id') }}".replace(':id', prestamoId);

                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('No se pudo cargar la préstamo');
                        return response.text();
                    })
                    .then(html => {
                        let modalContainer = document.getElementById('modalContainerOrigen');
                        if (!modalContainer) {
                            modalContainer = document.createElement('div');
                            modalContainer.id = 'modalContainerOrigen';
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

            const inputInicio = document.getElementById('fecha_inicio_origen');
            const inputFin = document.getElementById('fecha_fin_origen');

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