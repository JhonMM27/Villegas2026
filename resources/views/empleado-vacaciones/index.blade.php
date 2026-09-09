{{-- Vista principal del módulo de Vacaciones de Empleados --}}
{{-- Muestra la tabla de tramos de vacaciones con filtros por año y empleado --}}
@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Control de Vacaciones</h3>
                    @can('empleado_vacaciones_create')
                        <button type="button" class="btn btn-primary" id="btnCreate">
                            <i class="bi bi-plus-circle"></i> Nueva Vacación
                        </button>
                    @endcan
                </div>
                <div class="card-body">
                    {{-- Filtros de búsqueda --}}
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="filtroAnio" class="form-label">Año del Derecho</label>
                            <select id="filtroAnio" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                @for ($y = now()->year - 10; $y <= now()->year + 2; $y++)
                                    <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtroEmpleado" class="form-label">Empleado</label>
                            <select id="filtroEmpleado" class="form-select form-select-sm">
                                <option value="">Todos</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-secondary btn-sm" id="btnFiltrar">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                        </div>
                    </div>
                    {{-- Tabla de tramos de vacaciones --}}
                    <div class="table-responsive">
                        <table id="listadoTable" class="table table-striped table-hover table-sm table-app">
                            <thead>
                                <tr>
                                    <th>Opciones</th>
                                    <th>Empleado</th>
                                    <th>Año</th>
                                    <th class="text-end">Días del Tramo</th>
                                    <th class="text-end">Días Pend. Año</th>
                                    <th>Fechas</th>
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
@canany(['empleado_vacaciones_create', 'empleado_vacaciones_edit'])
    @include('empleado-vacaciones.action')
@endcanany
@endsection
@push('scripts')
<script>
/**
 * Gestor de Vacaciones — extiende CrudManager
 *
 * Funcionalidades clave:
 * - Auto-cálculo de días del tramo desde fecha_inicio y fecha_fin
 * - Resumen visual de disponibilidad (días usados/disponibles por año)
 * - Validación frontend que impide exceder los 15 días anuales
 */
class VacacionManager extends CrudManager {
    constructor() {
        super("{{ url('empleado-vacaciones') }}");
        this.initializeDataTable();
        this.setupFilters();
        this.setupEventListeners();
        this.cargarEmpleadosFiltro();
    }

    /**
     * Inicializa la DataTable con las columnas actualizadas.
     * Se eliminó la columna ID y Días Gen. (redundante, siempre 15).
     */
    initializeDataTable() {
        this.tabla = $('#listadoTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: this.baseUrl + '/dataTable',
                type: 'GET',
                data: (d) => {
                    d.anio = $('#filtroAnio').val();
                    d.empleado_id = $('#filtroEmpleado').val();
                }
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'empleado_nombre', name: 'empleado_nombre' },
                { data: 'anio_generado', name: 'anio_generado' },
                { data: 'dias_tramo', name: 'dias_tramo', className: 'text-end' },
                { data: 'dias_pendientes_anio', name: 'dias_pendientes_anio', className: 'text-end' },
                { data: 'fechas', name: 'fechas' }
            ]
        });
    }

    /** Configura los filtros de búsqueda de la tabla */
    setupFilters() {
        $('#btnFiltrar').on('click', () => {
            this.tabla.ajax.reload();
        });

        $('#filtroAnio, #filtroEmpleado').on('change', () => {
            this.tabla.ajax.reload();
        });
    }

    /** Carga la lista de empleados elegibles en el select de filtro */
    cargarEmpleadosFiltro() {
        fetch("{{ url('empleado-vacaciones/elegibles') }}")
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('filtroEmpleado');
                data.forEach(emp => {
                    const option = document.createElement('option');
                    option.value = emp.id;
                    option.textContent = emp.nombre;
                    select.appendChild(option);
                });
            });
    }

    /**
     * Configura todos los event listeners del modal:
     * - Búsqueda de empleado (live search)
     * - Cambio de año del derecho → recarga resumen
     * - Cambio de fechas → auto-cálculo de días del tramo
     */
    setupEventListeners() {
        // Búsqueda de empleado con autocompletado
        this.setupLiveSearchSelect({
            inputId: 'empleado_nombre',
            hiddenId: 'empleado_id',
            url: "{{ url('empleado-vacaciones/elegibles') }}",
            template: (item) => `${item.nombre} (DNI: ${item.dni} - ${item.anos_servicio} años)`,
            getId: item => item.id,
            minLength: 1,
            delay: 300,
            onSelect: (item) => this.onEmpleadoSelected(item)
        });

        // Al cambiar el año del derecho, recargar el resumen de disponibilidad
        document.getElementById('anio_generado')?.addEventListener('change', () => {
            const empleadoId = document.getElementById('empleado_id').value;
            if (empleadoId) {
                const excluirId = this.isEditing ? this.editingId : null;
                this.cargarResumenEmpleado(empleadoId, document.getElementById('anio_generado').value, excluirId);
            }
        });

        // Al cambiar fecha_inicio o fecha_fin, recalcular días del tramo
        const fechaInicio = document.getElementById('fecha_inicio');
        const fechaFin = document.getElementById('fecha_fin');

        if (fechaInicio) {
            // Soporta tanto Flatpickr (change event) como input nativo
            fechaInicio.addEventListener('change', () => this.calcularDiasTramo());
        }
        if (fechaFin) {
            fechaFin.addEventListener('change', () => this.calcularDiasTramo());
        }
    }

    /**
     * Calcula automáticamente los días del tramo desde las fechas seleccionadas.
     * Actualiza el display y valida contra los días disponibles.
     */
    calcularDiasTramo() {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;
        const display = document.getElementById('dias_tramo_display');
        const feedback = document.getElementById('dias_tramo_feedback');
        const disponibles = parseInt(document.getElementById('dias_disponibles').value) || 0;

        if (!fechaInicio || !fechaFin) {
            display.value = '0';
            display.classList.remove('is-invalid', 'text-danger', 'text-success');
            feedback.textContent = '';
            return;
        }

        const inicio = new Date(fechaInicio);
        const fin = new Date(fechaFin);

        if (fin < inicio) {
            display.value = '0';
            display.classList.add('is-invalid');
            feedback.textContent = 'La fecha fin debe ser posterior a la fecha inicio';
            return;
        }

        // Cálculo: diferencia en días + 1 (ambos extremos incluidos)
        const diffTime = fin.getTime() - inicio.getTime();
        const diasTramo = Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1;

        display.value = diasTramo;
        display.classList.remove('is-invalid');
        feedback.textContent = '';

        // Feedback visual según disponibilidad
        if (diasTramo > disponibles) {
            display.classList.add('is-invalid');
            display.classList.remove('text-success');
            feedback.textContent = `Excede los ${disponibles} días disponibles para este año`;
        } else {
            display.classList.remove('is-invalid');
            display.classList.add('text-success');
            feedback.textContent = '';
        }
    }

    /**
     * Valida antes de enviar el formulario.
     * Verifica que los días del tramo no excedan los disponibles del año.
     */
    handleSubmit(e) {
        const disponibles = parseInt(document.getElementById('dias_disponibles').value) || 0;
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;

        // Validar que las fechas estén completas
        if (!fechaInicio || !fechaFin) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Fechas requeridas',
                text: 'Debe ingresar fecha de inicio y fin del período de vacaciones.',
            });
            return false;
        }

        // Calcular días del tramo
        const inicio = new Date(fechaInicio);
        const fin = new Date(fechaFin);
        const diasTramo = Math.floor((fin - inicio) / (1000 * 60 * 60 * 24)) + 1;

        // Validar que no exceda los disponibles
        if (diasTramo > disponibles) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Días insuficientes',
                text: `El tramo abarca ${diasTramo} días, pero solo hay ${disponibles} días disponibles para este año.`,
            });
            return false;
        }

        // Validar que el empleado esté seleccionado
        if (!document.getElementById('empleado_id').value) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Empleado requerido',
                text: 'Debe seleccionar un empleado.',
            });
            return false;
        }

        super.handleSubmit(e);
    }

    /** Callback cuando se selecciona un empleado en el buscador */
    onEmpleadoSelected(empleado) {
        const anio = document.getElementById('anio_generado').value;
        const excluirId = this.isEditing ? this.editingId : null;
        this.cargarResumenEmpleado(empleado.id, anio, excluirId);
    }

    /**
     * Carga el resumen de disponibilidad del empleado desde el backend.
     * Actualiza el badge visual con días usados/disponibles.
     *
     * @param {number} empleadoId - ID del empleado
     * @param {string|null} anio - Año del derecho a consultar
     * @param {number|null} excluirId - ID del registro a excluir (al editar)
     */
    cargarResumenEmpleado(empleadoId, anio = null, excluirId = null) {
        let url = `${this.baseUrl}/empleado/${empleadoId}/resumen`;
        const params = new URLSearchParams();
        if (anio) params.append('anio', anio);
        if (excluirId) params.append('excluir_id', excluirId);
        if (params.toString()) url += `?${params.toString()}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                this.actualizarResumenVisual(data);
                // Guardar días disponibles para validación
                document.getElementById('dias_disponibles').value = Math.max(0, data.dias_pendientes);
                // Recalcular feedback visual del tramo si ya hay fechas
                this.calcularDiasTramo();
            })
            .catch(() => {
                this.resetResumenVisual();
                document.getElementById('dias_disponibles').value = 15;
            });
    }

    /**
     * Actualiza el badge de resumen visual con colores según disponibilidad.
     * Verde: tiene días disponibles | Amarillo: pocos días | Rojo: sin días
     */
    actualizarResumenVisual(data) {
        const resumen = document.getElementById('resumenDisponibilidad');
        const texto = document.getElementById('resumenTexto');
        const detalle = document.getElementById('resumenDetalle');
        const pendientes = data.dias_pendientes;
        const tomados = data.total_dias_tomados;

        texto.textContent = `Disponibles: ${pendientes} de 15 días`;
        detalle.textContent = tomados > 0
            ? `${tomados} días usados en otros tramos del ${data.anio}`
            : `Ningún tramo registrado para ${data.anio}`;

        // Cambiar color del badge según disponibilidad
        resumen.classList.remove('alert-info', 'alert-success', 'alert-warning', 'alert-danger');
        if (pendientes <= 0) {
            resumen.classList.add('alert-danger');
        } else if (pendientes <= 5) {
            resumen.classList.add('alert-warning');
        } else {
            resumen.classList.add('alert-success');
        }
    }

    /** Resetea el badge de resumen al estado inicial */
    resetResumenVisual() {
        const resumen = document.getElementById('resumenDisponibilidad');
        const texto = document.getElementById('resumenTexto');
        const detalle = document.getElementById('resumenDetalle');

        texto.textContent = 'Seleccione un empleado';
        detalle.textContent = '';
        resumen.classList.remove('alert-success', 'alert-warning', 'alert-danger');
        resumen.classList.add('alert-info');
    }

    /** Muestra el modal para crear una nueva vacación */
    showCreateModal() {
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nueva Vacación';
        this.elements.form.reset();
        this.isEditing = false;
        this.editingId = null;
        document.getElementById('empleado_id').value = '';
        document.getElementById('empleado_nombre').value = '';
        document.getElementById('anio_generado').value = new Date().getFullYear();
        document.getElementById('dias_generados').value = '15';
        document.getElementById('dias_disponibles').value = '15';
        document.getElementById('dias_tramo_display').value = '0';
        this.setFieldValue('fecha_inicio', '');
        this.setFieldValue('fecha_fin', '');
        document.getElementById('observaciones').value = '';
        this.resetResumenVisual();
    }

    /**
     * Abre el modal de edición para un tramo de vacación existente.
     * Carga el resumen excluyendo el registro actual (excluir_id)
     * para que los días disponibles reflejen la realidad.
     */
    showEditModal(id) {
        this.fetchData(`${this.baseUrl}/${id}/edit`).then(data => {
            this.isEditing = true;
            this.editingId = id;
            document.getElementById('empleado_id').value = data.empleado_id;
            document.getElementById('empleado_nombre').value = data.empleado_nombre || `Empleado ID: ${data.empleado_id}`;
            document.getElementById('anio_generado').value = data.anio_generado;
            document.getElementById('dias_generados').value = data.dias_generados || 15;
            this.setFieldValue('fecha_inicio', data.fecha_inicio || '');
            this.setFieldValue('fecha_fin', data.fecha_fin || '');
            document.getElementById('observaciones').value = data.observaciones || '';

            // Cargar resumen excluyendo este registro para obtener días disponibles reales
            this.cargarResumenEmpleado(data.empleado_id, data.anio_generado, id);

            this.elements.methodField.value = 'PUT';
            this.elements.form.action = `${this.baseUrl}/${id}`;
            this.elements.modalTitle.textContent = 'Editar Vacación';
            this.modal.show();
        });
    }

    focusFirstField() {
        document.getElementById('empleado_nombre').focus();
    }

    /** Confirmación de eliminación de un tramo de vacación */
    confirmDelete(id) {
        Swal.fire({
            title: '¿Eliminar Vacación?',
            text: '¿Está seguro de eliminar este registro de vacaciones?',
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
                        this.showNotification('success', 'Vacación eliminada correctamente');
                        this.tabla.ajax.reload(null, false);
                    } else {
                        this.showNotification('error', result.message);
                    }
                } catch (error) {
                    this.showNotification('error', 'Error al eliminar la vacación');
                }
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.vacacionManager = new VacacionManager();
});

document.getElementById('mnuPlanilla').classList.add('menu-open');
</script>
@endpush