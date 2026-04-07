@extends('plantilla.app')
@section('contenido')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center">
                        <a href="{{ route('planilla-prestamos.index') }}" class="btn btn-secondary btn-sm me-2">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <h3 class="card-title flex-grow-1">Pagos del Préstamo N° {{ $prestamo->numero_interno }}</h3>
                        @can('planilla_prestamos_edit')
                            <button type="button" class="btn btn-success" id="btnRegistrarPago">
                                <i class="bi bi-plus-circle"></i> Registrar Pago
                            </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="border border-primary rounded p-3">
                                    <h6 class="text-primary mb-2"><i class="bi bi-person me-2"></i>Empleado</h6>
                                    <p class="fw-bold mb-0">{{ $prestamo->empleado->nombre ?? '-' }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-warning rounded p-3">
                                    <h6 class="text-warning mb-2"><i class="bi bi-currency-dollar me-2"></i>Montos</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label text-muted small mb-1">Original</label>
                                            <p id="pagosMontoOriginal" class="fw-bold mb-0 text-primary">S/
                                                {{ number_format((float) $prestamo->monto_original, 2) }}</p>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label text-muted small mb-1">Saldo</label>
                                            <p id="pagosMontoSaldo"
                                                class="fw-bold mb-0 {{ (float) $prestamo->saldo_pendiente > 0 ? 'text-danger' : 'text-success' }}">
                                                S/ {{ number_format((float) $prestamo->saldo_pendiente, 2) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-info rounded p-3">
                                    <h6 class="text-info mb-2"><i class="bi bi-info-circle me-2"></i>Estado</h6>
                                    @if ($prestamo->estado === 'activo')
                                        <span class="badge bg-success">Activo</span>
                                    @elseif($prestamo->estado === 'pagado')
                                        <span class="badge bg-primary">Pagado</span>
                                    @else
                                        <span class="badge bg-secondary">Anulado</span>
                                    @endif
                                    <p class="text-muted small mb-0 mt-2">Fecha:
                                        {{ $prestamo->fecha_prestamo->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="pagosTable" class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Opciones</th>
                                        <th>N° Interno</th>
                                        <th>Fecha</th>
                                        <th>Monto Pagado</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="modalPagoContainer"></div>
@endsection
@push('scripts')
    <script>
        class PagoManager {
            constructor(baseUrl, prestamoId, prestamoData) {
                this.baseUrl = baseUrl;
                this.prestamoId = prestamoId;
                this.prestamo = prestamoData;
                this.tabla = null;
                this.modal = null;
                this.pagoEditando = null;
            }

            initialize() {
                this.tabla = $('#pagosTable').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: `${this.baseUrl}/${this.prestamoId}/pagos/data`,
                        type: 'GET',
                        dataSrc: 'data'
                    },
                    columns: [{
                            data: 'action'
                        },
                        {
                            data: 'numero_interno'
                        },
                        {
                            data: 'fecha_pago'
                        },
                        {
                            data: 'monto_pagado'
                        },
                        {
                            data: 'observaciones'
                        }
                    ],
                    language: {
                        url: '/datatables/i18n/es-ES.json'
                    }
                });

                document.getElementById('btnRegistrarPago')?.addEventListener('click', () => this
                    .mostrarRegistrarPago());
            }

            showNotification(type, message) {
                const icons = {
                    success: 'success',
                    error: 'error',
                    warning: 'warning',
                    info: 'info'
                };
                Swal.fire({
                    icon: icons[type] || 'info',
                    title: message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }

            async actualizarMontos() {
                try {
                    const response = await fetch(`${this.baseUrl}/${this.prestamoId}/montos`);
                    const data = await response.json();

                    if (data.success === false) {
                        return;
                    }

                    const elOriginal = document.getElementById('pagosMontoOriginal');
                    const elSaldo = document.getElementById('pagosMontoSaldo');

                    if (elOriginal) elOriginal.textContent = `S/ ${data.monto_original.toFixed(2)}`;
                    if (elSaldo) {
                        elSaldo.textContent = `S/ ${data.saldo_pendiente.toFixed(2)}`;
                        elSaldo.className = `fw-bold mb-0 ${data.saldo_pendiente > 0 ? 'text-danger' : 'text-success'}`;
                    }
                } catch (error) {
                    console.error('Error al actualizar montos:', error);
                }
            }

            mostrarRegistrarPago() {
                this.pagoEditando = null;
                const html = `
        <div class="modal fade" id="modalRegistrarPago" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <form id="formPago">
                        @csrf
                        <div class="modal-header">
                            <h4 class="modal-title fs-5">Registrar Pago</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-2">
                                <label for="pago_numero_interno" class="form-label">N° Pago <span class="text-danger">*</span></label>
                                <input type="number" id="pago_numero_interno" name="numero_interno" class="form-control form-control-sm" min="1" required autocomplete="off>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-muted small">Préstamo</label>
                                <p class="fw-bold mb-0">N° ${this.prestamo.numero_interno} - ${this.prestamo.empleado?.nombre || '-'}</p>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6">
                                    <label class="form-label text-muted small">Monto Original</label>
                                    <p class="fw-bold mb-0 text-primary">S/ ${parseFloat(this.prestamo.monto_original).toFixed(2)}</p>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small">Saldo Pendiente</label>
                                    <p class="fw-bold mb-0 text-danger">S/ ${parseFloat(this.prestamo.saldo_pendiente).toFixed(2)}</p>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="mb-2">
                                <label for="pago_monto" class="form-label">Monto a Pagar <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" id="pago_monto" name="monto_pagado" class="form-control" step="0.01" min="0.01" required>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label for="pago_fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
                                <input type="date" id="pago_fecha" name="fecha_pago" class="form-control form-control-sm" value="${new Date().toISOString().split('T')[0]}" required>
                            </div>
                            <div class="mb-2">
                                <label for="pago_observaciones" class="form-label">Observaciones</label>
                                <input type="text" id="pago_observaciones" name="observaciones" class="form-control form-control-sm" placeholder="Observaciones...">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle me-1"></i> Registrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>`;

                document.getElementById('modalPagoContainer').innerHTML = html;
                this.modal = new bootstrap.Modal(document.getElementById('modalRegistrarPago'));
                this.modal.show();

                document.getElementById('formPago').addEventListener('submit', (e) => this.handleSubmit(e));
            }

            mostrarEditarPago(id) {
                fetch(`${this.baseUrl}/pagos/${id}/editar`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success === false) {
                            this.showNotification('error', data.message);
                            return;
                        }
                        this.pagoEditando = data.pago;
                        this.mostrarFormularioEditar(data.pago);
                    })
                    .catch(err => {
                        console.error(err);
                        this.showNotification('error', 'Error al cargar datos');
                    });
            }

            mostrarFormularioEditar(pago) {
                const html = `
        <div class="modal fade" id="modalEditarPago" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <form id="formPago">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h4 class="modal-title fs-5">Editar Pago N° ${pago.numero_interno}</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-2">
                                <label for="pago_numero_interno" class="form-label">N° Pago <span class="text-danger">*</span></label>
                                <input type="number" id="pago_numero_interno" name="numero_interno" class="form-control form-control-sm" value="${pago.numero_interno}" min="1" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-muted small">Préstamo</label>
                                <p class="fw-bold mb-0">N° ${this.prestamo.numero_interno} - ${this.prestamo.empleado?.nombre || '-'}</p>
                            </div>
                            <div class="mb-2">
                                <label for="pago_monto" class="form-label">Monto Pagado <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" id="pago_monto" name="monto_pagado" class="form-control" step="0.01" min="0.01" value="${parseFloat(pago.monto_pagado)}" required>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label for="pago_fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
                                <input type="date" id="pago_fecha" name="fecha_pago" class="form-control form-control-sm" value="${pago.fecha_pago}" required>
                            </div>
                            <div class="mb-2">
                                <label for="pago_observaciones" class="form-label">Observaciones</label>
                                <input type="text" id="pago_observaciones" name="observaciones" class="form-control form-control-sm" value="${pago.observaciones || ''}" placeholder="Observaciones...">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Actualizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>`;

                document.getElementById('modalPagoContainer').innerHTML = html;
                this.modal = new bootstrap.Modal(document.getElementById('modalEditarPago'));
                this.modal.show();

                document.getElementById('formPago').addEventListener('submit', (e) => this.handleUpdate(e, pago.id));
            }

            handleSubmit(e) {
                e.preventDefault();

                const formData = new FormData(e.target);
                const data = {
                    numero_interno: formData.get('numero_interno'),
                    monto_pagado: formData.get('monto_pagado'),
                    fecha_pago: formData.get('fecha_pago'),
                    observaciones: formData.get('observaciones')
                };

                fetch(`${this.baseUrl}/${this.prestamoId}/pagar`, {
                        method: 'POST',
                        body: new URLSearchParams(data),
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(result => {
                        if (result.success) {
                            this.showNotification('success', 'Pago registrado correctamente');
                            this.modal.hide();
                            this.tabla.ajax.reload(null, false);
                            this.actualizarMontos();
                        } else {
                            this.showNotification('error', result.message);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.showNotification('error', 'Error al registrar');
                    });
            }

            handleUpdate(e, pagoId) {
                e.preventDefault();

                const formData = new FormData(e.target);
                const data = {
                    numero_interno: formData.get('numero_interno'),
                    monto_pagado: formData.get('monto_pagado'),
                    fecha_pago: formData.get('fecha_pago'),
                    observaciones: formData.get('observaciones')
                };

                fetch(`${this.baseUrl}/pagos/${pagoId}`, {
                        method: 'PUT',
                        body: new URLSearchParams(data),
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(result => {
                        if (result.success) {
                            this.showNotification('success', 'Pago actualizado correctamente');
                            this.modal.hide();
                            this.tabla.ajax.reload(null, false);
                            this.actualizarMontos();
                        } else {
                            this.showNotification('error', result.message);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.showNotification('error', 'Error al actualizar');
                    });
            }

            confirmDelete(id) {
                Swal.fire({
                    title: '¿Eliminar Pago?',
                    text: '¿Está seguro de eliminar este pago? Esta acción recalculará el saldo del préstamo.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-trash me-1"></i> Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const response = await fetch(`${this.baseUrl}/pagos/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json'
                                }
                            });

                            const data = await response.json();
                            if (data.success) {
                                this.showNotification('success', 'Pago eliminado correctamente');
                                this.tabla.ajax.reload(null, false);
                                this.actualizarMontos();
                            } else {
                                this.showNotification('error', data.message);
                            }
                        } catch (err) {
                            console.error(err);
                            this.showNotification('error', 'Error al eliminar');
                        }
                    }
                });
            }

            verPago(id) {
                fetch(`${this.baseUrl}/pagos/${id}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success === false) {
                            this.showNotification('error', data.message);
                            return;
                        }
                        this.mostrarVerPago(data.pago);
                    })
                    .catch(err => {
                        console.error(err);
                        this.showNotification('error', 'Error al cargar datos');
                    });
            }

            mostrarVerPago(pago) {
                const html = `
        <div class="modal fade" id="modalVerPago" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title fs-5">Detalle del Pago N° ${pago.numero_interno}</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label text-muted small">Préstamo</label>
                            <p class="fw-bold mb-0">N° ${this.prestamo.numero_interno} - ${this.prestamo.empleado?.nombre || '-'}</p>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6">
                                <label class="form-label text-muted small">Monto Pagado</label>
                                <p class="fw-bold mb-0 text-success">S/ ${parseFloat(pago.monto_pagado).toFixed(2)}</p>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small">Fecha</label>
                                <p class="fw-bold mb-0">${new Date(pago.fecha_pago).toLocaleDateString()}</p>
                            </div>
                        </div>
                        ${pago.observaciones ? `
                                <div class="mb-2">
                                    <label class="form-label text-muted small">Observaciones</label>
                                    <p class="mb-0">${pago.observaciones}</p>
                                </div>
                                ` : ''}
                        <hr class="my-2">
                        <div class="text-muted small">
                            <p class="mb-0">Creado: ${pago.created_at ? new Date(pago.created_at).toLocaleString() : 'N/A'}</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="${this.baseUrl}/pagos/${pago.id}/imprimir" target="_blank" class="btn btn-secondary">
                            <i class="bi bi-printer me-1"></i> Imprimir
                        </a>
                    </div>
                </div>
            </div>
        </div>`;

                document.getElementById('modalPagoContainer').innerHTML = html;
                this.modal = new bootstrap.Modal(document.getElementById('modalVerPago'));
                this.modal.show();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const prestamoData = @json($prestamo);
            window.pagoManager = new PagoManager("{{ url('planilla-prestamos') }}", {{ $prestamo->id }},
                prestamoData);
            window.pagoManager.initialize();
        });
    </script>
@endpush
