@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Inasistencias</h3>
                    @can('planilla_inasistencias_create')
                        <button type="button" class="btn btn-primary" id="btnCreate">
                            <i class="bi bi-plus-circle"></i> Nueva Inasistencia
                        </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="listadoTable" class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Opciones</th>
                                    <th>Empleado</th>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Observación</th>
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
@can('planilla_inasistencias_create')
    @include('planilla.inasistencias.action')
@endcan
@endsection
@push('scripts')
<script>
class InasistenciaManager {
    constructor(baseUrl) {
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
            ajax: { url: this.baseUrl, type: 'GET' },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'empleado_id', name: 'empleado_id' },
                { data: 'fecha', name: 'fecha' },
                { data: 'medio_dia', name: 'medio_dia' },
                { data: 'observacion', name: 'observacion' }
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

    handleSubmit(e) {
        e.preventDefault();

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
                this.showNotification('success', isEditing ? 'Inasistencia actualizada' : 'Inasistencia registrada');
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

    showCreateModal() {
        document.getElementById('modalTitle').textContent = 'Nueva Inasistencia';
        document.getElementById('method_field').value = '';
        this.form.action = this.baseUrl;
        this.form.reset();
        document.getElementById('empleado_id').value = '';
        document.getElementById('empleado_nombre').value = '';
        document.getElementById('fecha').value = new Date().toISOString().split('T')[0];
        document.getElementById('medio_dia').checked = false;
        document.getElementById('observacion').value = '';
        this.modal.show();
        document.getElementById('empleado_nombre').focus();
    }

    async showEditModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}/edit`);
            document.getElementById('modalTitle').textContent = 'Editar Inasistencia';
            document.getElementById('method_field').value = 'PUT';
            this.form.action = `${this.baseUrl}/${id}`;
            document.getElementById('empleado_id').value = response.inasistencia.empleado_id;
            document.getElementById('empleado_nombre').value = response.inasistencia.empleado?.nombre || '';
            document.getElementById('fecha').value = response.inasistencia.fecha ? response.inasistencia.fecha.split('T')[0] : '';
            document.getElementById('medio_dia').checked = response.inasistencia.medio_dia;
            document.getElementById('observacion').value = response.inasistencia.observacion || '';
            this.modal.show();
        } catch (error) {
            this.showNotification('error', 'Error al cargar datos');
            console.error(error);
        }
    }

    confirmDelete(id) {
        Swal.fire({
            title: '¿Eliminar Inasistencia?',
            text: '¿Está seguro de eliminar esta inasistencia? Se recalculará el pago del mes.',
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
                        this.showNotification('success', 'Inasistencia eliminada correctamente');
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
    window.inasistenciaManager = new InasistenciaManager("{{ url('planilla-inasistencias') }}");
});
document.getElementById('mnuPlanilla').classList.add('menu-open');
</script>
@endpush
