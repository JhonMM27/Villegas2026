@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Pagos de Planilla</h3>
                    @can('planilla_pagos_create')
                        <button type="button" class="btn btn-primary" id="btnCreate">
                            <i class="bi bi-plus-circle"></i> Nuevo Pago
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
                                    <th>Periodo</th>
                                    <th>Disponible</th>
                                    <th>H. Extras</th>
                                    <th>Total Pagar</th>
                                    <th>Estado</th>
                                    <th>Fecha Pago</th>
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
@canany(['planilla_pagos_create', 'planilla_pagos_edit'])
    @include('planilla.pagos.action')
@endcanany
<div id="modalVerContainer"></div>
@endsection
@push('scripts')
<script>
class PagoPlanillaManager extends CrudManager {
    constructor() {
        super("{{ url('planilla-pagos') }}");
        this.initializeDataTable();
        this.setupEventListeners();
        this.setupCajaListeners();
    }

    initializeDataTable() {
        this.tabla = $(this.elements.table).DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: this.baseUrl, type: 'GET' },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'id', name: 'id' },
                { data: 'empleado_id', name: 'empleado_id' },
                { data: 'mes', name: 'mes' },
                { data: 'sueldo_base', name: 'sueldo_base' },
                { data: 'horas_extras', name: 'horas_extras' },
                { data: 'total_pagar', name: 'total_pagar' },
                { data: 'estado', name: 'estado' },
                { data: 'fecha_pago', name: 'fecha_pago' }
            ]
        });
    }

    setupEventListeners() {
        this.setupLiveSearchSelect({
            inputId: 'empleado_nombre',
            hiddenId: 'empleado_id',
            url: "{{ route('empleados.buscar') }}",
            template: (item) => `${item.nombre} (DNI: ${item.dni})`,
            getId: item => item.id,
            minLength: 1,
            delay: 300,
            onSelect: (item) => this.onEmpleadoSelected(item)
        });

        document.getElementById('horas_extras')?.addEventListener('input', () => this.calcularTotal());
        
        document.getElementById('mes')?.addEventListener('change', () => this.cargarDisponible());
        document.getElementById('anio')?.addEventListener('change', () => this.cargarDisponible());
    }

    validateCajaDistribution() {
        const totalPagar = parseFloat(document.getElementById('total_pagar')?.value) || 0;
        const principal = parseFloat(document.getElementById('principal')?.value) || 0;
        const deposito = parseFloat(document.getElementById('deposito')?.value) || 0;
        const consortium = parseFloat(document.getElementById('consorcio')?.value) || 0;
        const sum = principal + deposito + consortium;

        if (sum > totalPagar && totalPagar > 0) {
            document.getElementById('total_caja').classList.add('is-invalid');
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'La distribución de caja no puede superar el total a pagar (S/ ' + totalPagar.toFixed(2) + ')',
                toast: true,
                position: 'top-end',
                showConfirmButton: true,
                timer: false
            });
            return false;
        }
        return true;
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        if (!this.validateCajaDistribution()) {
            this.setSubmitButtonState(false);
            return;
        }

        const formData = new FormData(this.form);
        this.setSubmitButtonState(true);
        this.clearFormErrors();
        
        try {
            const response = await this.submitForm(formData);
            
            const focusedElement = this.elements.modal.querySelector(':focus');
            if (focusedElement) focusedElement.blur();
            
            this.modal.hide();
            this.tabla.ajax.reload(null, false);
            
            const isSuccess = response.success === true || response.status === true;
            if (isSuccess) {
                const message = this.isEditing 
                    ? (response.message || 'Pago actualizado correctamente')
                    : (response.message || 'Pago registrado correctamente');
                this.showNotification('success', message);

                if (this.afterSuccess) {
                    await this.afterSuccess(response, this.isEditing);
                }

            } else {
                this.showNotification('error', response.message || 'Error en la operación');
            }
            
        } catch (error) {
            console.error('Error en submit:', error);
            this.handleFormErrors(error);
        } finally {
            this.setSubmitButtonState(false);
        }
    }

    async cargarDisponible() {
        const empleadoId = document.getElementById('empleado_id').value;
        const mes = document.getElementById('mes').value;
        const anio = document.getElementById('anio').value;

        if (!empleadoId || !mes || !anio) return;

        try {
            const response = await fetch(`{{ route('planilla-pagos.disponible') }}?empleado_id=${empleadoId}&mes=${mes}&anio=${anio}`);
            const data = await response.json();

            document.getElementById('sueldo_planilla').value = parseFloat(data.sueldo_planilla).toFixed(2);
            document.getElementById('sueldo_real').value = parseFloat(data.sueldo_real).toFixed(2);
            document.getElementById('disponible_label').textContent = parseFloat(data.disponible).toFixed(2);
            
            this.calcularTotal();
        } catch (error) {
            console.error('Error al cargar disponible:', error);
        }
    }

    onEmpleadoSelected(empleado) {
        document.getElementById('sueldo_planilla').value = parseFloat(empleado.sueldo_planilla).toFixed(2);
        document.getElementById('sueldo_real').value = parseFloat(empleado.sueldo_real).toFixed(2);
        
        this.cargarDisponible();
    }

    calcularTotal() {
        const disponible = parseFloat(document.getElementById('disponible_label').textContent) || 0;
        const horasExtras = parseFloat(document.getElementById('horas_extras').value) || 0;
        const total = disponible + horasExtras;
        document.getElementById('total_pagar_label').textContent = total.toFixed(2);
        document.getElementById('total_pagar').value = total.toFixed(2);
        document.getElementById('principal').value = total.toFixed(2);
        document.getElementById('deposito').value = '0.00';
        document.getElementById('consorcio').value = '0.00';
        document.getElementById('total_caja').value = total.toFixed(2);
    }

    recalcularTotalCaja() {
        const totalPagar = parseFloat(document.getElementById('total_pagar')?.value) || 0;
        const principal = parseFloat(document.getElementById('principal').value) || 0;
        const deposito = parseFloat(document.getElementById('deposito').value) || 0;
        const consortium = parseFloat(document.getElementById('consorcio').value) || 0;
        const total = principal + deposito + consortium;

        document.getElementById('total_caja').value = total.toFixed(2);

        if (total > totalPagar && totalPagar > 0) {
            document.getElementById('total_caja').classList.add('is-invalid');
        } else {
            document.getElementById('total_caja').classList.remove('is-invalid');
        }
    }

    setupCajaListeners() {
        ['principal', 'deposito', 'consorcio'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', () => this.recalcularTotalCaja());
        });
    }

    showCreateModal() {
        super.showCreateModal();
        this.isEditing = false;
        this.elements.modalTitle.textContent = 'Procesar Pago de Planilla';
        this.elements.methodField.value = 'POST';
        document.getElementById('empleado_id').value = '';
        document.getElementById('empleado_nombre').value = '';
        document.getElementById('sueldo_planilla').value = '0.00';
        document.getElementById('sueldo_real').value = '0.00';
        document.getElementById('disponible_label').textContent = '0.00';
        document.getElementById('horas_extras').value = '0';
        document.getElementById('total_pagar_label').textContent = '0.00';
        document.getElementById('total_pagar').value = '0.00';
        document.getElementById('principal').value = '0.00';
        document.getElementById('deposito').value = '0.00';
        document.getElementById('consorcio').value = '0.00';
        document.getElementById('total_caja').value = '0.00';
        document.getElementById('total_caja').classList.remove('is-invalid');
        document.getElementById('horas_extras').disabled = false;
        document.getElementById('empleado_nombre').disabled = false;
        
        const now = new Date();
        document.getElementById('mes').value = now.getMonth() + 1;
        document.getElementById('anio').value = now.getFullYear();
        
        document.getElementById('mes').disabled = false;
        document.getElementById('anio').disabled = false;
    }

    async showEditModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);
            
            this.isEditing = true;
            this.resetForm();
            this.elements.modalTitle.textContent = 'Editar Pago: ' + response.empleado.nombre + ' - ' + response.mes + '/' + response.anio;
            this.elements.methodField.value = 'PUT';
            this.form.action = `${this.baseUrl}/${id}`;

            document.getElementById('empleado_id').value = response.empleado_id;
            document.getElementById('empleado_nombre').value = response.empleado.nombre + ' (DNI: ' + response.empleado.dni + ')';
            document.getElementById('sueldo_planilla').value = parseFloat(response.empleado.sueldo_planilla || 0).toFixed(2);
            document.getElementById('sueldo_real').value = parseFloat(response.empleado.sueldo_real || 0).toFixed(2);
            document.getElementById('disponible_label').textContent = parseFloat(response.sueldo_base || 0).toFixed(2);
            document.getElementById('horas_extras').value = parseFloat(response.horas_extras || 0);
            document.getElementById('total_pagar_label').textContent = parseFloat(response.total_pagar || 0).toFixed(2);
            document.getElementById('total_pagar').value = parseFloat(response.total_pagar || 0).toFixed(2);
            
            document.getElementById('principal').value = parseFloat(response.importe_p || 0).toFixed(2);
            document.getElementById('deposito').value = parseFloat(response.importe_d || 0).toFixed(2);
            document.getElementById('consorcio').value = parseFloat(response.importe_c || 0).toFixed(2);
            document.getElementById('total_caja').value = (
                parseFloat(response.importe_p || 0) +
                parseFloat(response.importe_d || 0) +
                parseFloat(response.importe_c || 0)
            ).toFixed(2);
            document.getElementById('total_caja').classList.remove('is-invalid');
            
            document.getElementById('horas_extras').disabled = false;
            document.getElementById('empleado_nombre').disabled = true;
            
            const mesSelect = document.getElementById('mes');
            mesSelect.value = response.mes;
            mesSelect.disabled = true;
            document.getElementById('anio').value = response.anio;
            document.getElementById('anio').disabled = true;

            this.modal.show();
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error(error);
        }
    }

    focusFirstField() {
        document.getElementById('empleado_nombre').focus();
    }

    async verDetalle(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);
            const p = response;

            const viewHtml = `
            <div class="modal fade" id="modalVerPago" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title fs-5" id="modalTitle">Detalle del Pago: ${p.empleado?.nombre || '-'}</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="border border-primary rounded p-3 h-100">
                                        <h6 class="text-primary mb-3"><i class="bi bi-person me-2"></i>Datos del Pago</h6>
                                        <div class="row mb-2">
                                            <div class="col-12">
                                                <label class="form-label text-muted small mb-1">Empleado</label>
                                                <p class="fw-bold mb-2">${p.empleado?.nombre || '-'}</p>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-muted small mb-1">Periodo</label>
                                                <p class="fw-bold mb-0">${this.getNombreMes(p.mes)} ${p.anio}</p>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-muted small mb-1">Estado</label>
                                                <p class="mb-0">
                                                    <span class="badge ${p.estado === 'pagado' ? 'bg-primary' : 'bg-warning'}">
                                                        ${p.estado}
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-muted small mb-1">Fecha de Pago</label>
                                                <p class="fw-bold mb-0">${p.fecha_pago ? new Date(p.fecha_pago).toLocaleDateString() : '-'}</p>
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
                                    <div class="border border-success rounded p-3 h-100">
                                        <h6 class="text-success mb-3"><i class="bi bi-currency-dollar me-2"></i>Cálculo del Pago</h6>
                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <label class="form-label text-muted small mb-1">Sueldo Planilla</label>
                                                <p class="fw-bold mb-1">S/ ${parseFloat(p.empleado?.sueldo_planilla || 0).toFixed(2)}</p>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-muted small mb-1">Sueldo Real</label>
                                                <p class="fw-bold mb-1">S/ ${parseFloat(p.empleado?.sueldo_real || 0).toFixed(2)}</p>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-muted small mb-1">Disponible</label>
                                                <p class="fw-bold mb-1">S/ ${parseFloat(p.sueldo_base || 0).toFixed(2)}</p>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-muted small mb-1">H. Extras</label>
                                                <p class="fw-bold mb-1">S/ ${parseFloat(p.horas_extras || 0).toFixed(2)}</p>
                                            </div>
                                        </div>
                                        <hr class="my-2">
                                        <div class="row">
                                            <div class="col-12 text-end">
                                                <label class="form-label text-muted small mb-1">Total a Pagar</label>
                                                <p class="fw-bold mb-0 text-success fs-5">S/ ${parseFloat(p.total_pagar || 0).toFixed(2)}</p>
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

            document.getElementById('modalVerContainer')?.remove();
            const container = document.createElement('div');
            container.id = 'modalVerContainer';
            container.innerHTML = viewHtml;
            document.body.appendChild(container);

            const modal = new bootstrap.Modal(document.getElementById('modalVerPago'));
            modal.show();

            document.getElementById('modalVerPago').addEventListener('hidden.bs.modal', () => {
                container.remove();
            });
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error(error);
        }
    }

    getNombreMes(mes) {
        const meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        return meses[mes] || mes;
    }

    marcarPagado(id) {
        Swal.fire({
            title: '¿Confirmar pago?',
            text: '¿Desea marcar este pago como completado?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, marcar como pagado',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch(`${this.baseUrl}/${id}/marcar-pagado`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.showNotification('success', 'Pago marcado como completado');
                        this.tabla.ajax.reload(null, false);
                    } else {
                        this.showNotification('error', data.message);
                    }
                } catch (error) {
                    this.showNotification('error', 'Error al procesar');
                }
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.pagoManager = new PagoPlanillaManager();
});
document.getElementById('mnuPlanilla').classList.add('menu-open');
document.getElementById('itemPagos').classList.add('active');
</script>
@endpush
