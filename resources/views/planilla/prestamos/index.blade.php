@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Préstamos a Empleados</h3>
                    @can('planilla_prestamos_create')
                        <button type="button" class="btn btn-primary" id="btnCreate">
                            <i class="bi bi-plus-circle"></i> Nuevo Préstamo
                        </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="listadoTable" class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Opciones</th>
                                    <th>ID</th>
                                    <th>Empleado</th>
                                    <th>Monto Original</th>
                                    <th>Saldo Pendiente</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
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
@can('planilla_prestamos_create')
    @include('planilla.prestamos.action')
@endcan
<div id="modalPagoContainer"></div>
<div id="modalVerContainer"></div>
@endsection
@push('scripts')
<script>
class PrestamoManager {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
        this.tabla = null;
        this.prestamoActual = null;
        this.modal = null;
        this.form = null;
        this.initializeDataTable();
        this.setupEventListeners();
    }

    initializeDataTable() {
        this.tabla = $('#listadoTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: this.baseUrl, type: 'GET' },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'id', name: 'id' },
                { data: 'empleado_id', name: 'empleado_id' },
                { data: 'monto_original', name: 'monto_original' },
                { data: 'saldo_pendiente', name: 'saldo_pendiente' },
                { data: 'fecha_prestamo', name: 'fecha_prestamo' },
                { data: 'estado', name: 'estado' }
            ]
        });
    }

    setupEventListeners() {
        const modalEl = document.getElementById('modalUpdate');
        if (modalEl) {
            this.modal = new bootstrap.Modal(modalEl);
            this.form = document.getElementById('formUpdate');
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
        document.getElementById('btnCreate')?.addEventListener('click', () => this.showCreateModal());
        this.setupLiveSearch();
        this.setupCajaListeners();
        document.getElementById('monto_original')?.addEventListener('input', (e) => {
            const monto = parseFloat(e.target.value) || 0;
            if (monto > 0) {
                document.getElementById('principal').value = monto.toFixed(2);
            }
            this.recalcularTotalCaja();
        });
    }

    setupCajaListeners() {
        ['principal', 'deposito', 'consorcio'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', () => this.recalcularTotalCaja());
        });
    }

    recalcularTotalCaja() {
        const monto = parseFloat(document.getElementById('monto_original')?.value) || 0;
        const principal = parseFloat(document.getElementById('principal')?.value) || 0;
        const deposito = parseFloat(document.getElementById('deposito')?.value) || 0;
        const consortium = parseFloat(document.getElementById('consorcio')?.value) || 0;
        const total = principal + deposito + consortium;

        document.getElementById('total_caja').value = total.toFixed(2);

        if (total > monto && monto > 0) {
            document.getElementById('total_caja').classList.add('is-invalid');
        } else {
            document.getElementById('total_caja').classList.remove('is-invalid');
        }
    }

    async fetchData(url) {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    }

    showNotification(type, message) {
        const icons = { success: 'success', error: 'error', warning: 'warning', info: 'info' };
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

    validateCajaDistribution() {
        const monto = parseFloat(document.getElementById('monto_original')?.value) || 0;
        const principal = parseFloat(document.getElementById('principal')?.value) || 0;
        const deposito = parseFloat(document.getElementById('deposito')?.value) || 0;
        const consortium = parseFloat(document.getElementById('consorcio')?.value) || 0;
        const sum = principal + deposito + consortium;

        if (sum > monto && monto > 0) {
            document.getElementById('total_caja').classList.add('is-invalid');
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'La distribución de caja no puede superar el monto del préstamo (S/ ' + monto.toFixed(2) + ')',
                toast: true,
                position: 'top-end',
                showConfirmButton: true,
                timer: false
            });
            return false;
        }
        return true;
    }

    handleSubmit(e) {
        e.preventDefault();
        
        if (!this.validateCajaDistribution()) {
            document.getElementById('btnSubmit').disabled = false;
            document.getElementById('btnSubmit').innerHTML = '<i class="bi bi-check-circle"></i> Guardar';
            return;
        }

        const formData = new FormData(this.form);
        const action = this.form.action;
        const isEditing = document.getElementById('method_field')?.value === 'PUT';

        fetch(action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.showNotification('success', isEditing ? 'Préstamo actualizado' : 'Préstamo creado');
                this.modal.hide();
                this.tabla.ajax.reload(null, false);
            } else {
                this.showNotification('error', data.message || 'Error');
            }
        })
        .catch(err => {
            console.error(err);
            this.showNotification('error', 'Error al guardar');
        })
        .finally(() => {
            document.getElementById('btnSubmit').disabled = false;
            document.getElementById('btnSubmit').innerHTML = '<i class="bi bi-check-circle"></i> Guardar';
        });
    }

    setupLiveSearch() {
        const input = document.getElementById('empleado_nombre');
        const hidden = document.getElementById('empleado_id');
        if (!input || !hidden) return;

        let timeout = null;
        input.addEventListener('input', () => {
            const q = input.value.trim();
            if (q.length < 1) { hidden.value = ''; return; }
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                fetch(`{{ route('empleados.buscar') }}?q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(data => {
                        let list = input.parentNode.querySelector('ul.search-list');
                        if (list) list.remove();
                        if (!data.length) return;
                        list = document.createElement('ul');
                        list.className = 'list-group position-absolute w-100 search-list';
                        list.style.zIndex = '1050';
                        data.forEach(item => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item list-group-item-action';
                            li.textContent = `${item.nombre} (DNI: ${item.dni})`;
                            li.onclick = () => {
                                input.value = item.nombre;
                                hidden.value = item.id;
                                list.remove();
                            };
                            list.appendChild(li);
                        });
                        input.parentNode.style.position = 'relative';
                        input.parentNode.appendChild(list);
                    });
            }, 300);
        });
        input.addEventListener('blur', () => setTimeout(() => input.parentNode.querySelector('ul.search-list')?.remove(), 200));
    }

    showCreateModal() {
        document.getElementById('modalTitle').textContent = 'Nuevo Préstamo';
        document.getElementById('method_field').value = '';
        this.form.action = this.baseUrl;
        this.form.reset();
        document.getElementById('fecha_prestamo').value = new Date().toISOString().split('T')[0];
        document.getElementById('empleado_id').value = '';
        document.getElementById('empleado_nombre').value = '';
        document.getElementById('monto_original').value = '';
        document.getElementById('observaciones').value = '';
        document.getElementById('principal').value = '0.00';
        document.getElementById('deposito').value = '0.00';
        document.getElementById('consorcio').value = '0.00';
        document.getElementById('total_caja').value = '0.00';
        document.getElementById('total_caja').classList.remove('is-invalid');
        this.modal.show();
        document.getElementById('empleado_nombre').focus();
    }

    async showEditModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}/edit`);
            document.getElementById('modalTitle').textContent = 'Editar Préstamo';
            document.getElementById('method_field').value = 'PUT';
            this.form.action = `${this.baseUrl}/${id}`;
            document.getElementById('empleado_id').value = response.prestamo.empleado_id;
            document.getElementById('empleado_nombre').value = response.prestamo.empleado?.nombre || '';
            document.getElementById('fecha_prestamo').value = response.prestamo.fecha_prestamo;
            document.getElementById('monto_original').value = response.prestamo.monto_original;
            document.getElementById('estado').value = response.prestamo.estado;
            document.getElementById('observaciones').value = response.prestamo.observaciones || '';
            document.getElementById('principal').value = parseFloat(response.prestamo.importe_p || 0).toFixed(2);
            document.getElementById('deposito').value = parseFloat(response.prestamo.importe_d || 0).toFixed(2);
            document.getElementById('consorcio').value = parseFloat(response.prestamo.importe_c || 0).toFixed(2);
            document.getElementById('total_caja').value = (
                parseFloat(response.prestamo.importe_p || 0) +
                parseFloat(response.prestamo.importe_d || 0) +
                parseFloat(response.prestamo.importe_c || 0)
            ).toFixed(2);
            document.getElementById('total_caja').classList.remove('is-invalid');
            this.modal.show();
        } catch (error) {
            this.showNotification('error', 'Error al cargar datos');
            console.error(error);
        }
    }

    async verDetalle(id) {
        try {
            const response = await fetch(`${this.baseUrl}/${id}`);
            const data = await response.json();

            const p = data.prestamo;
            const totalPagado = parseFloat(p.monto_original) - parseFloat(p.saldo_pendiente);

            const viewHtml = `
            <div class="modal fade" id="modalVerPrestamo" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title fs-5" id="modalTitle">Detalle del Préstamo: ${p.empleado?.nombre || '-'}</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="border border-primary rounded p-3 h-100">
                                        <h6 class="text-primary mb-3"><i class="bi bi-person me-2"></i>Datos del Préstamo</h6>
                                        <div class="row mb-2">
                                            <div class="col-12">
                                                <label class="form-label text-muted small mb-1">Empleado</label>
                                                <p class="fw-bold mb-2">${p.empleado?.nombre || '-'}</p>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-muted small mb-1">Fecha</label>
                                                <p class="fw-bold mb-0">${new Date(p.fecha_prestamo).toLocaleDateString()}</p>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-muted small mb-1">Estado</label>
                                                <p class="mb-0">
                                                    <span class="badge ${p.estado === 'activo' ? 'bg-success' : p.estado === 'pagado' ? 'bg-primary' : 'bg-secondary'}">
                                                        ${p.estado}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                        ${p.observaciones ? `
                                        <hr class="my-2">
                                        <div class="row">
                                            <div class="col-12">
                                                <label class="form-label text-muted small mb-1">Observaciones</label>
                                                <p class="mb-0">${p.observaciones}</p>
                                            </div>
                                        </div>
                                        ` : ''}
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="border border-warning rounded p-3 h-100">
                                        <h6 class="text-warning mb-3"><i class="bi bi-currency-dollar me-2"></i>Montos del Préstamo</h6>
                                        <div class="row mb-2">
                                            <div class="col-12">
                                                <label class="form-label text-muted small mb-1">Monto Original</label>
                                                <p class="fw-bold mb-2 text-primary fs-5">S/ ${parseFloat(p.monto_original).toFixed(2)}</p>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-muted small mb-1">Total Pagado</label>
                                                <p class="fw-bold mb-0 text-success">S/ ${totalPagado.toFixed(2)}</p>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-muted small mb-1">Saldo Pendiente</label>
                                                <p class="fw-bold mb-0 ${parseFloat(p.saldo_pendiente) > 0 ? 'text-danger' : 'text-success'}">S/ ${parseFloat(p.saldo_pendiente).toFixed(2)}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="border border-info rounded p-3 h-100">
                                        <h6 class="text-info mb-3"><i class="bi bi-wallet2 me-2"></i>Distribución de Caja</h6>
                                        <div class="row mb-2">
                                            <div class="col-md-4">
                                                <label class="form-label text-muted small mb-1">Principal</label>
                                                <p class="fw-bold mb-0">S/ ${parseFloat(p.importe_p || 0).toFixed(2)}</p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted small mb-1">Depósito</label>
                                                <p class="fw-bold mb-0">S/ ${parseFloat(p.importe_d || 0).toFixed(2)}</p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted small mb-1">Consorcio</label>
                                                <p class="fw-bold mb-0">S/ ${parseFloat(p.importe_c || 0).toFixed(2)}</p>
                                            </div>
                                        </div>
                                        <hr class="my-2">
                                        <div class="row">
                                            <div class="col-12 text-end">
                                                <label class="form-label text-muted small mb-1">Total Distribuido</label>
                                                <p class="fw-bold mb-0">S/ ${(parseFloat(p.importe_p || 0) + parseFloat(p.importe_d || 0) + parseFloat(p.importe_c || 0)).toFixed(2)}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-lg-12">
                                    <div class="border border-secondary rounded p-3">
                                        <h6 class="text-secondary mb-3"><i class="bi bi-clock-history me-2"></i>Historial de Pagos</h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm table-hover mb-0">
                                                <thead class="table-light text-center">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Fecha de Pago</th>
                                                        <th>Monto Pagado</th>
                                                        <th>Observaciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${p.pagos && p.pagos.length > 0 ? p.pagos.map((pago, index) => `
                                                        <tr>
                                                            <td class="text-center">${index + 1}</td>
                                                            <td class="text-center">${new Date(pago.fecha_pago).toLocaleDateString()}</td>
                                                            <td class="text-end text-success fw-bold">S/ ${parseFloat(pago.monto_pagado).toFixed(2)}</td>
                                                            <td>${pago.observaciones || '-'}</td>
                                                        </tr>
                                                    `).join('') : '<tr><td colspan="4" class="text-center text-muted py-3">No hay pagos registrados</td></tr>'}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <small class="text-muted me-auto">
                                Creado: ${p.created_at ? new Date(p.created_at).toLocaleDateString() : 'N/A'}
                            </small>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>`;

            document.getElementById('modalVerContainer').innerHTML = viewHtml;
            const modal = new bootstrap.Modal(document.getElementById('modalVerPrestamo'));
            modal.show();
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error(error);
        }
    }

    mostrarPago(id) {
        this.prestamoActual = id;
        const pagoHtml = `
        <div class="modal fade" id="modalPagoPrestamo" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <form id="formPagoPrestamo">
                        @csrf
                        <div class="modal-header">
                            <h4 class="modal-title fs-5">Registrar Pago</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="monto_pagado" class="form-label">Monto a Pagar</label>
                                <input type="number" id="monto_pagado" name="monto_pagado" class="form-control form-control-sm" step="0.01" min="0.01" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="fecha_pago" class="form-label">Fecha</label>
                                <input type="date" id="fecha_pago" name="fecha_pago" class="form-control form-control-sm" value="${new Date().toISOString().split('T')[0]}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="observaciones_pago" class="form-label">Observaciones</label>
                                <input type="text" id="observaciones_pago" name="observaciones" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">Registrar Pago</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>`;

        document.getElementById('modalPagoContainer').innerHTML = pagoHtml;
        const modal = new bootstrap.Modal(document.getElementById('modalPagoPrestamo'));
        modal.show();

        document.getElementById('formPagoPrestamo').addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('monto_pagado', document.getElementById('monto_pagado').value);
            formData.append('fecha_pago', document.getElementById('fecha_pago').value);
            formData.append('observaciones', document.getElementById('observaciones_pago').value);

            fetch(`/planilla-prestamos/${this.prestamoActual}/pagar`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    this.showNotification('success', 'Pago registrado correctamente');
                    modal.hide();
                    this.tabla.ajax.reload(null, false);
                } else {
                    this.showNotification('error', result.message);
                }
            })
            .catch(err => {
                console.error(err);
                this.showNotification('error', 'Error al registrar el pago');
            });
        });
    }

    confirmDelete(id) {
        if (!confirm('¿Está seguro de eliminar este préstamo?')) {
            return;
        }

        fetch(`${this.baseUrl}/${id}`, {
            method: 'POST',
            body: new URLSearchParams({
                _method: 'DELETE',
                _token: document.querySelector('meta[name="csrf-token"]').content
            }),
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.showNotification('success', 'Préstamo eliminado correctamente');
                this.tabla.ajax.reload(null, false);
            } else {
                this.showNotification('error', data.message);
            }
        })
        .catch(err => {
            console.error(err);
            this.showNotification('error', 'Error al eliminar');
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.prestamoManager = new PrestamoManager("{{ url('planilla-prestamos') }}");
});
document.getElementById('mnuPlanilla').classList.add('menu-open');
document.getElementById('itemPrestamosPlanilla').classList.add('active');
</script>
@endpush
