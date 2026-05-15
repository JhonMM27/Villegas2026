@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Empleados</h3>
                    @can('empleados_create')
                        <button type="button" class="btn btn-primary" id="btnCreate">
                            <i class="bi bi-plus-circle"></i> Nuevo
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
                                    <th>Nombre</th>
                                    <th>DNI</th>
                                    <th>Teléfono</th>
                                    <th>Sueldo Planilla</th>
                                    <th>Sueldo Real</th>
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
@canany(['empleados_create', 'empleados_edit'])
    @include('planilla.empleados.action')
@endcanany
@endsection
@push('scripts')
<script>
class EmpleadoManager extends CrudManager {
    constructor() {
        super("{{ url('empleados') }}");
        this.initializeDataTable();
    }

    initializeDataTable() {
        this.tabla = $(this.elements.table).DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: this.baseUrl, type: 'GET' },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'id', name: 'id' },
                { data: 'nombre', name: 'nombre' },
                { data: 'dni', name: 'dni' },
                { data: 'telefono', name: 'telefono' },
                { data: 'sueldo_planilla', name: 'sueldo_planilla' },
                { data: 'sueldo_real', name: 'sueldo_real' },
                { data: 'estado', name: 'estado' }
            ]
        });
    }

    showCreateModal() {
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nuevo Empleado';
    }

    showEditModal(id) {
        this.fetchData(`${this.baseUrl}/${id}/edit`).then(data => {
            document.getElementById('nombre').value = data.empleado.nombre;
            document.getElementById('dni').value = data.empleado.dni;
            document.getElementById('telefono').value = data.empleado.telefono || '';
            document.getElementById('correo').value = data.empleado.correo || '';
            document.getElementById('sueldo_planilla').value = data.empleado.sueldo_planilla;
            document.getElementById('sueldo_real').value = data.empleado.sueldo_real;
            document.getElementById('estado').value = data.empleado.estado;
            if (data.empleado.fecha_ingreso) {
                document.getElementById('fecha_ingreso').value = data.empleado.fecha_ingreso;
            }
        });
        this.isEditing = true;
        this.elements.methodField.value = 'PUT';
        this.elements.form.action = `${this.baseUrl}/${id}`;
        this.elements.modalTitle.textContent = 'Editar Empleado';
        this.modal.show();
    }

    focusFirstField() {
        document.getElementById('nombre').focus();
    }

    async verDetalle(id) {
        try {
            const response = await fetch(`${this.baseUrl}/${id}`);
            const data = await response.json();

            const viewHtml = `
            <div class="modal fade" id="modalVerEmpleado" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title fs-5" id="modalTitle">Detalle del Empleado: ${data.empleado.nombre}</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="border border-primary rounded p-3">
                                        <h6 class="text-primary mb-3"><i class="bi bi-person me-2"></i>Datos Personales</h6>
                                        <div class="row mb-2">
                                            <div class="col-md-6">
                                                <label class="form-label text-muted small mb-1">Nombre</label>
                                                <p class="fw-bold mb-0">${data.empleado.nombre}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted small mb-1">DNI</label>
                                                <p class="fw-bold mb-0">${data.empleado.dni}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted small mb-1">Teléfono</label>
                                                <p class="fw-bold mb-0">${data.empleado.telefono || '-'}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted small mb-1">Correo</label>
                                                <p class="fw-bold mb-0">${data.empleado.correo || '-'}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted small mb-1">Fecha de Ingreso</label>
                                                <p class="fw-bold mb-0">${data.empleado.fecha_ingreso ? new Date(data.empleado.fecha_ingreso).toLocaleDateString('es-PE') : '-'}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted small mb-1">Años de Servicio</label>
                                                <p class="fw-bold mb-0">${data.empleado.fecha_ingreso ? Math.floor((new Date() - new Date(data.empleado.fecha_ingreso)) / (365.25 * 24 * 60 * 60 * 1000)) + ' años' : '-'}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="border border-success rounded p-3">
                                        <h6 class="text-success mb-3"><i class="bi bi-currency-dollar me-2"></i>Información Salarial</h6>
                                        <div class="row mb-2">
                                            <div class="col-md-6">
                                                <label class="form-label text-muted small mb-1">Sueldo Planilla</label>
                                                <p class="fw-bold mb-0">S/ ${parseFloat(data.empleado.sueldo_planilla).toFixed(2)}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted small mb-1">Sueldo Real</label>
                                                <p class="fw-bold mb-0">S/ ${parseFloat(data.empleado.sueldo_real).toFixed(2)}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted small mb-1">Estado</label>
                                                <p class="mb-0">
                                                    <span class="badge ${data.empleado.estado === 'activo' ? 'bg-success' : 'bg-secondary'}">
                                                        ${data.empleado.estado}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                ${data.empleado.observaciones ? `
                                <div class="col-lg-12 mt-3">
                                    <div class="border border-secondary rounded p-3">
                                        <h6 class="text-secondary mb-3"><i class="bi bi-card-text me-2"></i>Observaciones</h6>
                                        <p class="mb-0">${data.empleado.observaciones}</p>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <small class="text-muted me-auto">
                                Creado: ${data.empleado.created_at ? new Date(data.empleado.created_at).toLocaleDateString() : 'N/A'}
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

            const modal = new bootstrap.Modal(document.getElementById('modalVerEmpleado'));
            modal.show();

            document.getElementById('modalVerEmpleado').addEventListener('hidden.bs.modal', () => {
                container.remove();
            });
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error(error);
        }
    }

    confirmDelete(id) {
        Swal.fire({
            title: '¿Eliminar Empleado?',
            text: '¿Está seguro de eliminar este empleado? Esta acción no se puede deshacer.',
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
                    const formData = new FormData();
                    formData.append('_method', 'DELETE');
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                    const response = await fetch(`${this.baseUrl}/${id}`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });

                    const result = await response.json();
                    if (result.success) {
                        this.showNotification('success', 'Empleado eliminado correctamente');
                        this.tabla.ajax.reload(null, false);
                    } else {
                        this.showNotification('error', result.message);
                    }
                } catch (error) {
                    this.showNotification('error', 'Error al eliminar el empleado');
                }
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.empleadoManager = new EmpleadoManager();
});
document.getElementById('mnuPlanilla').classList.add('menu-open');
document.getElementById('itemEmpleados').classList.add('active');
</script>
@endpush
