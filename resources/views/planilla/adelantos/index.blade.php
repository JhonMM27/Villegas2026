@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Adelantos</h3>
                    @can('planilla_adelantos_create')
                        <button type="button" class="btn btn-primary" id="btnCreate">
                            <i class="bi bi-plus-circle"></i> Nuevo Adelanto
                        </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div id="filtroMesWrapper" class="d-flex align-items-center gap-2 mb-2">
                        <label for="filtroMes" class="form-label mb-0 text-muted small">Mes:</label>
                        <input type="month" id="filtroMes" class="form-control form-control-sm" style="width: 170px" value="{{ date('Y-m') }}">
                    </div>
                    <div class="table-responsive">
                        <table id="listadoTable" class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Opciones</th>
                                    <th>N° Interno</th>
                                    <th>Empleado</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
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
@can('planilla_adelantos_create')
    @include('planilla.adelantos.action')
@endcan
@endsection
@push('scripts')
<script>
class AdelantoManager extends CrudManager {
    constructor(baseUrl) {
        super(baseUrl);
        this.baseUrl = baseUrl;
        this.tabla = null;
        this.modal = null;
        this.form = null;
        this.initializeDataTable();
        this.setupEventListeners();
    }

    initializeDataTable() {
        this.tabla = $('#listadoTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: this.baseUrl,
                type: 'GET',
                data: (d) => { d.mes = $('#filtroMes').val() || ''; }
            },
            dom: "<'row'<'col-md-6'<'#filtroMesPlaceholder'>><'col-md-6'f>>rtip",
            initComplete: () => {
                const placeholder = document.getElementById('filtroMesPlaceholder');
                const wrapper = document.getElementById('filtroMesWrapper');
                if (placeholder && wrapper) {
                    placeholder.appendChild(wrapper);
                }
            },
            language: {
                emptyTable: "No hay adelantos en este mes.",
                zeroRecords: "No se encontraron adelantos que coincidan con el filtro.",
                info: "Mostrando _START_ a _END_ de _TOTAL_ adelantos",
                infoEmpty: "Mostrando 0 adelantos",
                infoFiltered: "(filtrado de _MAX_ adelantos totales)",
                lengthMenu: "Mostrar _MENU_ registros",
                loadingRecords: "Cargando...",
                processing: "Procesando...",
                search: "Buscar:",
                paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'numero_interno', name: 'numero_interno' },
                { data: 'empleado_nombre', name: 'empleados.nombre' },
                { data: 'monto', name: 'monto' },
                { data: 'fecha', name: 'fecha' },
                { data: 'observaciones', name: 'observaciones' }
            ]
        });
    }

    setupEventListeners() {
        const modalEl = document.getElementById('modalUpdate');
        if (modalEl) {
            this.modal = new bootstrap.Modal(modalEl);
            this.form = document.getElementById('formUpdate');
        }
        document.getElementById('btnCreate')?.addEventListener('click', () => this.showCreateModal());
        document.getElementById('filtroMes')?.addEventListener('change', () => this.tabla.ajax.reload());
        this.setupLiveSearch();
        this.setupCajaListeners();
        document.getElementById('monto')?.addEventListener('input', (e) => {
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
        const monto = parseFloat(document.getElementById('monto')?.value) || 0;
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
        const monto = parseFloat(document.getElementById('monto')?.value) || 0;
        const principal = parseFloat(document.getElementById('principal')?.value) || 0;
        const deposito = parseFloat(document.getElementById('deposito')?.value) || 0;
        const consortium = parseFloat(document.getElementById('consorcio')?.value) || 0;
        const sum = principal + deposito + consortium;

        if (sum > monto && monto > 0) {
            document.getElementById('total_caja').classList.add('is-invalid');
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'La distribución de caja no puede superar el monto del adelanto (S/ ' + monto.toFixed(2) + ')',
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
                this.showNotification('success', isEditing ? 'Adelanto actualizado' : 'Adelanto creado');
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
        document.getElementById('modalTitle').textContent = 'Nuevo Adelanto';
        document.getElementById('method_field').value = '';
        this.form.action = this.baseUrl;
        this.form.reset();
        document.getElementById('numero_interno').value = '';
        this.setFieldValue('fecha', new Date().toISOString().split('T')[0]);
        document.getElementById('empleado_id').value = '';
        document.getElementById('empleado_nombre').value = '';
        document.getElementById('monto').value = '';
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
            document.getElementById('modalTitle').textContent = 'Editar Adelanto';
            document.getElementById('method_field').value = 'PUT';
            this.form.action = `${this.baseUrl}/${id}`;
            document.getElementById('numero_interno').value = response.adelanto.numero_interno;
            document.getElementById('empleado_id').value = response.adelanto.empleado_id;
            document.getElementById('empleado_nombre').value = response.adelanto.empleado?.nombre || '';
            this.setFieldValue('fecha', response.adelanto.fecha ? response.adelanto.fecha.split('T')[0] : '');
            document.getElementById('monto').value = response.adelanto.monto;
            document.getElementById('observaciones').value = response.adelanto.observaciones || '';
            document.getElementById('principal').value = parseFloat(response.adelanto.importe_p || 0).toFixed(2);
            document.getElementById('deposito').value = parseFloat(response.adelanto.importe_d || 0).toFixed(2);
            document.getElementById('consorcio').value = parseFloat(response.adelanto.importe_c || 0).toFixed(2);
            document.getElementById('total_caja').value = (
                parseFloat(response.adelanto.importe_p || 0) +
                parseFloat(response.adelanto.importe_d || 0) +
                parseFloat(response.adelanto.importe_c || 0)
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

            const viewHtml = `
            <div class="modal fade" id="modalVerAdelanto" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title fs-5" id="modalTitle">Detalle del Adelanto: ${data.adelanto.empleado?.nombre || '-'}</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="border border-primary rounded p-3">
                                        <h6 class="text-primary mb-3"><i class="bi bi-person me-2"></i>Datos del Adelanto</h6>
                                        <div class="row mb-2">
                                            <div class="col-md-6">
                                                <label class="form-label text-muted small mb-1">Empleado</label>
                                                <p class="fw-bold mb-0">${data.adelanto.empleado?.nombre || '-'}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted small mb-1">Fecha</label>
                                                <p class="fw-bold mb-0">${new Date(data.adelanto.fecha).toLocaleDateString()}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted small mb-1">Monto Adelantado</label>
                                                <p class="fw-bold mb-0 text-primary fs-5">S/ ${parseFloat(data.adelanto.monto).toFixed(2)}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="border border-info rounded p-3">
                                        <h6 class="text-info mb-3"><i class="bi bi-wallet2 me-2"></i>Distribución de Caja</h6>
                                        <div class="row mb-2">
                                            <div class="col-md-4">
                                                <label class="form-label text-muted small mb-1">Principal</label>
                                                <p class="fw-bold mb-0">S/ ${parseFloat(data.adelanto.importe_p || 0).toFixed(2)}</p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted small mb-1">Depósito</label>
                                                <p class="fw-bold mb-0">S/ ${parseFloat(data.adelanto.importe_d || 0).toFixed(2)}</p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted small mb-1">Consorcio</label>
                                                <p class="fw-bold mb-0">S/ ${parseFloat(data.adelanto.importe_c || 0).toFixed(2)}</p>
                                            </div>
                                        </div>
                                        <hr class="my-2">
                                        <div class="row">
                                            <div class="col-12 text-end">
                                                <label class="form-label text-muted small mb-1">Total Distribuido</label>
                                                <p class="fw-bold mb-0">S/ ${(parseFloat(data.adelanto.importe_p || 0) + parseFloat(data.adelanto.importe_d || 0) + parseFloat(data.adelanto.importe_c || 0)).toFixed(2)}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                ${data.adelanto.observaciones ? `
                                <div class="col-lg-12 mt-3">
                                    <div class="border border-secondary rounded p-3">
                                        <h6 class="text-secondary mb-3"><i class="bi bi-card-text me-2"></i>Observaciones</h6>
                                        <p class="mb-0">${data.adelanto.observaciones}</p>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <small class="text-muted me-auto">
                                Creado: ${data.adelanto.created_at ? new Date(data.adelanto.created_at).toLocaleDateString() : 'N/A'}
                            </small>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>`;

            document.getElementById('modalVerContainer')?.remove();
            const container = document.createElement('div');
            container.id = 'modalVerContainer';
            container.innerHTML = viewHtml;
            document.body.appendChild(container);

            const modal = new bootstrap.Modal(document.getElementById('modalVerAdelanto'));
            modal.show();

            document.getElementById('modalVerAdelanto').addEventListener('hidden.bs.modal', () => {
                container.remove();
            });
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error(error);
        }
    }

    confirmDelete(id) {
        Swal.fire({
            title: '¿Eliminar Adelanto?',
            text: '¿Está seguro de eliminar este adelanto? Esta acción no se puede deshacer.',
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
                    const response = await fetch(`${this.baseUrl}/${id}`, {
                        method: 'POST',
                        body: new URLSearchParams({
                            _method: 'DELETE',
                            _token: document.querySelector('meta[name="csrf-token"]').content
                        }),
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.showNotification('success', 'Adelanto eliminado correctamente');
                        this.tabla.ajax.reload(null, false);
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
}

document.addEventListener('DOMContentLoaded', () => {
    window.adelantoManager = new AdelantoManager("{{ url('planilla-adelantos') }}");
});
document.getElementById('mnuPlanilla').classList.add('menu-open');
document.getElementById('itemAdelantos').classList.add('active');
</script>
@endpush
