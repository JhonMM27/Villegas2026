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
                                    <th>No Planilla</th>
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
@push('estilos')
<style>
    .payroll-correction-modal {
        border: 0;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 1.5rem 4rem rgba(15, 23, 42, .2);
    }

    .payroll-correction-header {
        padding: 1.1rem 1.5rem;
        border-bottom: 1px solid var(--bs-border-color);
        background: linear-gradient(135deg, rgba(255, 193, 7, .14), rgba(255, 255, 255, 0));
    }

    .payroll-correction-icon {
        width: 2.75rem;
        height: 2.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        border-radius: .8rem;
        color: #8a5a00;
        background: rgba(255, 193, 7, .24);
        font-size: 1.3rem;
    }

    .employee-summary {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: .9rem 1rem;
        border: 1px solid var(--bs-border-color);
        border-radius: .85rem;
        background: var(--bs-tertiary-bg);
    }

    .employee-avatar {
        width: 2.6rem;
        height: 2.6rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        border-radius: 50%;
        color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), .12);
        font-size: 1.15rem;
    }

    .correction-notice {
        display: flex;
        align-items: center;
        gap: .7rem;
        padding: .8rem 1rem;
        border: 1px solid rgba(var(--bs-info-rgb), .22);
        border-radius: .75rem;
        color: var(--bs-info-text-emphasis);
        background: rgba(var(--bs-info-rgb), .08);
        font-size: .87rem;
        line-height: 1.35;
    }

    .correction-notice i {
        flex: 0 0 auto;
        font-size: 1.25rem;
    }

    .formula-pill {
        padding: .35rem .65rem;
        border-radius: 999px;
        color: var(--bs-success-text-emphasis);
        background: rgba(var(--bs-success-rgb), .1);
        font-size: .78rem;
        font-weight: 600;
    }

    .salary-input .form-control,
    .salary-result .form-control {
        min-width: 0;
        font-variant-numeric: tabular-nums;
    }

    .salary-result .input-group-text,
    .salary-result .form-control {
        color: var(--bs-success-text-emphasis);
        background: rgba(var(--bs-success-rgb), .09);
        border-color: rgba(var(--bs-success-rgb), .28);
    }

    @media (max-width: 575.98px) {
        .payroll-correction-header,
        .payroll-correction-modal .modal-body,
        .payroll-correction-modal .modal-footer {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .employee-summary {
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .employee-summary .badge {
            margin-left: 3.45rem;
        }
    }
</style>
@endpush
@push('scripts')
<script>
class EmpleadoManager extends CrudManager {
    constructor() {
        super("{{ url('empleados') }}");
        this.initializeDataTable();
        ['sueldo_real', 'sueldo_planilla'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', () => this.calcularNoPlanilla());
        });
        document.getElementById('vigente_mes')?.addEventListener('change', event => {
            document.getElementById('vigente_desde').value = `${event.target.value}-01`;
        });
        this.rectificacionElement = document.getElementById('modalRectificarSueldo');
        this.rectificacionModal = this.rectificacionElement
            ? new bootstrap.Modal(this.rectificacionElement)
            : null;
        this.rectificacionForm = document.getElementById('formRectificarSueldo');
        this.rectificacionForm?.addEventListener('submit', event => this.guardarRectificacion(event));
        ['rect_real', 'rect_planilla'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', () => this.calcularRectificacion());
        });
        document.getElementById('rect_motivo')?.addEventListener('input', event => {
            document.getElementById('rect_motivo_contador').textContent = event.target.value.length;
            event.target.classList.remove('is-invalid');
        });
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
                { data: 'sueldo_base', name: 'sueldo_base', orderable: false, searchable: false },
                { data: 'sueldo_planilla', name: 'sueldo_planilla', orderable: false, searchable: false },
                { data: 'sueldo_real', name: 'sueldo_real', orderable: false, searchable: false },
                { data: 'estado', name: 'estado' }
            ]
        });
    }

    showCreateModal() {
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nuevo Empleado';
        document.getElementById('vigente_mes').value = '{{ now()->format('Y-m') }}';
        document.getElementById('vigente_desde').value = '{{ now()->startOfMonth()->toDateString() }}';
        this.calcularNoPlanilla();
    }

    showEditModal(id) {
        this.fetchData(`${this.baseUrl}/${id}/edit`).then(data => {
            document.getElementById('nombre').value = data.empleado.nombre;
            document.getElementById('dni').value = data.empleado.dni;
            document.getElementById('telefono').value = data.empleado.telefono || '';
            document.getElementById('correo').value = data.empleado.correo || '';
            document.getElementById('sueldo_base').value = data.empleado.sueldo_base;
            document.getElementById('sueldo_planilla').value = data.empleado.sueldo_planilla;
            document.getElementById('sueldo_real').value = data.empleado.sueldo_real;
            document.getElementById('vigente_mes').value = '{{ now()->format('Y-m') }}';
            document.getElementById('vigente_desde').value = '{{ now()->startOfMonth()->toDateString() }}';
            this.calcularNoPlanilla();
            document.getElementById('estado').value = data.empleado.estado;
            if (data.empleado.fecha_ingreso) {
                this.setFieldValue('fecha_ingreso', data.empleado.fecha_ingreso);
            }
            if (data.empleado.fecha_salida) {
                this.setFieldValue('fecha_salida', data.empleado.fecha_salida);
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

    calcularNoPlanilla() {
        const real = parseFloat(document.getElementById('sueldo_real')?.value) || 0;
        const planilla = parseFloat(document.getElementById('sueldo_planilla')?.value) || 0;
        document.getElementById('sueldo_base').value = Math.max(real - planilla, 0).toFixed(2);
    }

    async rectificarSueldo(id) {
        if (!this.rectificacionModal) return;

        try {
            const { empleado } = await this.fetchData(`${this.baseUrl}/${id}/edit`);
            this.limpiarRectificacion();
            document.getElementById('rect_empleado_id').value = empleado.id;
            document.getElementById('rect_empleado_nombre').textContent = empleado.nombre;
            document.getElementById('rect_empleado_dni').textContent = `DNI: ${empleado.dni}`;
            document.getElementById('rect_periodo').value = '{{ now()->format('Y-m') }}';
            document.getElementById('rect_real').value = parseFloat(empleado.sueldo_real || 0).toFixed(2);
            document.getElementById('rect_planilla').value = parseFloat(empleado.sueldo_planilla || 0).toFixed(2);
            this.calcularRectificacion();
            this.rectificacionModal.show();
        } catch (error) {
            this.showNotification('error', 'No se pudo cargar la información salarial del empleado');
            console.error(error);
        }
    }

    calcularRectificacion() {
        const realInput = document.getElementById('rect_real');
        const planillaInput = document.getElementById('rect_planilla');
        if (!realInput || !planillaInput) return;

        const real = parseFloat(realInput.value) || 0;
        const planilla = parseFloat(planillaInput.value) || 0;
        const esValido = planilla <= real;
        planillaInput.classList.toggle('is-invalid', !esValido);
        realInput.classList.remove('is-invalid');
        document.getElementById('rect_no_planilla').value = Math.max(real - planilla, 0).toFixed(2);
    }

    limpiarRectificacion() {
        this.rectificacionForm?.reset();
        this.rectificacionForm?.querySelectorAll('.is-invalid').forEach(element => element.classList.remove('is-invalid'));
        const alerta = document.getElementById('rect_alerta');
        alerta.classList.add('d-none');
        alerta.textContent = '';
        document.getElementById('rect_motivo_contador').textContent = '0';
        document.getElementById('rect_no_planilla').value = '0.00';
    }

    validarRectificacion() {
        const periodo = document.getElementById('rect_periodo');
        const real = document.getElementById('rect_real');
        const planilla = document.getElementById('rect_planilla');
        const motivo = document.getElementById('rect_motivo');
        const sueldoReal = parseFloat(real.value);
        const sueldoPlanilla = parseFloat(planilla.value);

        periodo.classList.toggle('is-invalid', !periodo.value);
        real.classList.toggle('is-invalid', !Number.isFinite(sueldoReal) || sueldoReal < 0);
        planilla.classList.toggle('is-invalid', !Number.isFinite(sueldoPlanilla) || sueldoPlanilla < 0 || sueldoPlanilla > sueldoReal);
        motivo.classList.toggle('is-invalid', motivo.value.trim().length === 0);

        return Boolean(periodo.value)
            && Number.isFinite(sueldoReal)
            && sueldoReal >= 0
            && Number.isFinite(sueldoPlanilla)
            && sueldoPlanilla >= 0
            && sueldoPlanilla <= sueldoReal
            && motivo.value.trim().length > 0;
    }

    async guardarRectificacion(event) {
        event.preventDefault();
        if (!this.validarRectificacion()) return;

        const empleadoId = document.getElementById('rect_empleado_id').value;
        const [anio, mes] = document.getElementById('rect_periodo').value.split('-').map(Number);
        const boton = document.getElementById('btnRectificarSueldo');
        const contenidoOriginal = boton.innerHTML;
        const alerta = document.getElementById('rect_alerta');
        boton.disabled = true;
        boton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Procesando...';
        alerta.classList.add('d-none');

        try {
            const response = await fetch(`${this.baseUrl}/${empleadoId}/rectificar-sueldo`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    anio,
                    mes,
                    sueldo_real: parseFloat(document.getElementById('rect_real').value),
                    sueldo_planilla: parseFloat(document.getElementById('rect_planilla').value),
                    motivo: document.getElementById('rect_motivo').value.trim()
                })
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'No se pudo rectificar el sueldo');
            }

            this.rectificacionModal.hide();
            this.showNotification('success', data.message);
            this.tabla.ajax.reload(null, false);
        } catch (error) {
            alerta.textContent = error.message || 'Ocurrió un error al procesar la rectificación.';
            alerta.classList.remove('d-none');
        } finally {
            boton.disabled = false;
            boton.innerHTML = contenidoOriginal;
        }
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
                                                <label class="form-label text-muted small mb-1">Fecha de Salida</label>
                                                <p class="fw-bold mb-0">${data.empleado.fecha_salida ? new Date(data.empleado.fecha_salida).toLocaleDateString('es-PE') : '-'}</p>
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
                                                 <label class="form-label text-muted small mb-1">Sueldo no planilla</label>
                                                <p class="fw-bold mb-0">S/ ${parseFloat(data.empleado.sueldo_base).toFixed(2)}</p>
                                            </div>
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
            title: '¿Inactivar empleado?',
            text: 'El empleado quedará inactivo y se conservarán sus pagos e historial salarial.',
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
                        this.showNotification('success', 'Empleado inactivado correctamente');
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
