<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Opciones</th>
                <th>Tipo</th>
                <th>Usuario</th>
                <th>Fecha</th>
                <th>Cliente</th>
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
                <tr>
                    <td>
                        <button class="btn btn-sm btn-info btn-view-prestamo-general" data-id="{{ $item->id }}">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                    <td>
                        <span class="badge bg-{{ $item->movimiento_tipo === 'PA' ? 'primary' : 'warning' }}">
                            {{ $item->movimiento_tipo === 'PA' ? 'A (Otorgado)' : 'DE (Recibido)' }}
                        </span>
                    </td>
                    <td>{{ $item->user_nombre }}</td>
                    <td>{{ $item->fecha_prestamo }}</td>
                    <td>{{ $item->cliente_nombre }}</td>
                    <td>{{ $item->comprobante_tipo_codigo }} {{ $item->serie }}-{{ $item->correlativo }}</td>
                    <td>{{ $item->producto_nombre }}</td>
                    <td>{{ $item->producto_empaque }}</td>
                    <td>{{ $item->unidad_nombre }}</td>
                    <td class="text-end">{{ $item->cantidad_prestada }}</td>
                    <td class="text-end">{{ $item->cantidad_devuelta }}</td>
                    <td class="text-end">{{ $item->saldo }}</td>
                </tr>
            @empty

                <tr>
                    <td colspan="12" class="text-center">No se encontraron registros</td>
                </tr>

            @endforelse
        </tbody>
    </table>
</div>
<div id="modalContainerGeneral"></div>
@push('scripts')
<script>

    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrarGeneral');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {

            const fechaInicio = document.getElementById('fecha_inicio_general').value;
            const fechaFin = document.getElementById('fecha_fin_general').value;
            const movimientoTipo = document.querySelector('#tab3 #movimiento_tipo').value;

            if (!fechaInicio || !fechaFin) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Datos incompletos',
                    text: 'Seleccione ambas fechas para filtrar',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            loader.classList.remove('d-none');
            const reporte = document.getElementById('reporteTab3');
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            const url = new URL("{{ route('reportes.prestamos_general') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);
            url.searchParams.append('movimiento_tipo', movimientoTipo);

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

        document.getElementById('btnPdfGeneral').addEventListener('click', function(e) {
            e.preventDefault();
            const fechaInicio = document.getElementById('fecha_inicio_general').value;
            const fechaFin = document.getElementById('fecha_fin_general').value;
            const movimientoTipo = document.querySelector('#tab3 #movimiento_tipo').value;

            if (!fechaInicio || !fechaFin) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Datos incompletos',
                    text: 'Seleccione ambas fechas para filtrar',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            const url = `{{ route('reportes.prestamos_general.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&movimiento_tipo=${movimientoTipo}`;

            window.open(url, '_blank');
        });

        document.body.addEventListener('click', function(e) {
            if (e.target && (e.target.matches('.btn-view-prestamo-general') || e.target.closest('.btn-view-prestamo-general'))) {
                const button = e.target.closest('.btn-view-prestamo-general');
                const prestamoId = button.getAttribute('data-id');
                if (!prestamoId) return;

                const url = "{{ route('prestamos.ver', ':id') }}".replace(':id', prestamoId);

                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('No se pudo cargar el préstamo');
                        return response.text();
                    })
                    .then(html => {
                        let modalContainer = document.getElementById('modalContainerGeneral');
                        if (!modalContainer) {
                            modalContainer = document.createElement('div');
                            modalContainer.id = 'modalContainerGeneral';
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

                const modalVerEl = document.querySelector('#modalContainerGeneral .modal.show');
                if (modalVerEl) {
                    const modalInstance = bootstrap.Modal.getInstance(modalVerEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }

                if (typeof prestamoManager !== 'undefined' && typeof prestamoManager.devolucionShowModal === 'function') {
                    prestamoManager.devolucionShowModal(prestamoId, tipo);
                } else {
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

            const inputInicio = document.getElementById('fecha_inicio_general');
            const inputFin = document.getElementById('fecha_fin_general');

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