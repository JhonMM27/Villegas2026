<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Opciones</th>
                <th>Usuario</th>
                <th>Fecha</th>
                <th>Destino</th>
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
                        <button class="btn btn-sm btn-info btn-view-prestamo" data-id="{{ $item->id }}">
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
<div id="modalContainer"></div>
@push('scripts')
<script>

    document.addEventListener('DOMContentLoaded', function() {        
        const btnFiltrar = document.getElementById('btnFiltrar');
        const loader = document.getElementById('loadingOverlay');
        
        btnFiltrar.addEventListener('click', function() {

            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;
            const clienteDestinoId = document.getElementById('cliente_destino_id').value;

            if (!fechaInicio || !fechaFin || !clienteDestinoId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Datos incompletos',
                    text: 'Seleccione ambas fechas y el cliente destino para filtrar',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            loader.classList.remove('d-none');
            const reporte=document.getElementById('reporteTab1')
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            const url = new URL("{{ route('reportes.prestamos_a_pendientes') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);
            url.searchParams.append('cliente_destino_id', clienteDestinoId);

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
            const clienteDestinoId = document.getElementById('cliente_destino_id').value;

            if (!fechaInicio || !fechaFin || !clienteDestinoId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Datos incompletos',
                    text: 'Seleccione ambas fechas y el cliente destino para filtrar',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            const url = `{{ route('reportes.prestamos_a_pendientes.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&cliente_destino_id=${clienteDestinoId}`;

            window.open(url, '_blank');
        });

        document.body.addEventListener('click', function(e) {
            // VER - Mostrar detalle
            if (e.target && (e.target.matches('.btn-view-prestamo') || e.target.closest('.btn-view-prestamo'))) {
                const button = e.target.closest('.btn-view-prestamo');
                const prestamoId = button.getAttribute('data-id');
                if (!prestamoId) return;

                const url = "{{ route('prestamos.ver', ':id') }}".replace(':id', prestamoId);

                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('No se pudo cargar la préstamo');
                        return response.text();
                    })
                    .then(html => {
                        let modalContainer = document.getElementById('modalContainer');
                        if (!modalContainer) {
                            modalContainer = document.createElement('div');
                            modalContainer.id = 'modalContainer';
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

            // REGISTRAR DEVOLUCION
            if (e.target && (e.target.matches('.btn-registrar-devolucion') || e.target.closest('.btn-registrar-devolucion'))) {
                const button = e.target.closest('.btn-registrar-devolucion');
                const prestamoId = button.getAttribute('data-id');
                const tipo = button.getAttribute('data-tipo');
                if (!prestamoId) return;

                // Cerrar modal Ver (prestamos.view)
                const modalVerEl = document.querySelector('#modalContainer .modal.show');
                if (modalVerEl) {
                    const modalInstance = bootstrap.Modal.getInstance(modalVerEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }

                // Verificar que existe prestamoManager (definido en prestamos/index.blade.php)
                if (typeof prestamoManager !== 'undefined' && typeof prestamoManager.devolucionShowModal === 'function') {
                    prestamoManager.devolucionShowModal(prestamoId, tipo);
                } else {
                    // Si no existe, ir a la pagina de prestamos y abrir ahi
                    alert('Redirigiendo a gestión de préstamos...');
                    window.location.href = "{{ url('prestamos') }}/" + prestamoId + '/ver';
                }
            }
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
