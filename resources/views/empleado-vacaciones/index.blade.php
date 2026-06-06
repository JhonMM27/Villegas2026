@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Control de Vacaciones Acumulables</h3>
                    @can('empleado_vacaciones_create')
                        <button type="button" class="btn btn-primary" id="btnCreate">
                            <i class="bi bi-plus-circle"></i> Nueva Vacación
                        </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="filtroAnio" class="form-label">Año Generado</label>
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
                    <div class="table-responsive">
                        <table id="listadoTable" class="table table-striped table-hover table-sm table-app">
                            <thead>
                                <tr>
                                    <th>Opciones</th>
                                    <th>ID</th>
                                    <th>Empleado</th>
                                    <th>Año</th>
                                    <th class="text-end">Días Gen.</th>
                                    <th class="text-end">Días Tomados</th>
                                    <th class="text-end">Días Pend.</th>
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
class VacacionManager extends CrudManager {
    constructor() {
        super("{{ url('empleado-vacaciones') }}");
        this.initializeDataTable();
        this.setupFilters();
        this.setupEventListeners();
        this.cargarEmpleadosFiltro();
    }

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
                { data: 'id', name: 'id' },
                { data: 'empleado_nombre', name: 'empleado_nombre' },
                { data: 'anio_generado', name: 'anio_generado' },
                { data: 'dias_generados', name: 'dias_generados' },
                { data: 'dias_tomados', name: 'dias_tomados' },
                { data: 'dias_pendientes', name: 'dias_pendientes' },
                { data: 'fechas', name: 'fechas' }
            ]
        });
    }

    setupFilters() {
        $('#btnFiltrar').on('click', () => {
            this.tabla.ajax.reload();
        });

        $('#filtroAnio, #filtroEmpleado').on('change', () => {
            this.tabla.ajax.reload();
        });
    }

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

    setupEventListeners() {
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

        document.getElementById('anio_generado')?.addEventListener('change', () => {
            const empleadoId = document.getElementById('empleado_id').value;
            if (empleadoId) {
                this.cargarResumenEmpleado(empleadoId, document.getElementById('anio_generado').value);
            }
        });
        document.getElementById('dias_tomados')?.addEventListener('input', () => this.actualizarDiasFaltantes());
        document.getElementById('dias_generados')?.addEventListener('input', () => this.actualizarDiasFaltantes());
    }

    handleSubmit(e) {
        const diasFaltantes = parseInt(document.getElementById('dias_faltantes').value) || 0;
        if (diasFaltantes <= 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'No puede crear vacación',
                text: 'El empleado ha agotado sus días de vacaciones para este año (15/15 días tomados)',
            });
            return false;
        }
        super.handleSubmit(e);
    }

    onEmpleadoSelected(empleado) {
        console.log('Empleado seleccionado:', empleado);
        const anio = document.getElementById('anio_generado').value;
        this.cargarResumenEmpleado(empleado.id, anio);
    }

    cargarResumenEmpleado(empleadoId, anio = null) {
        let url = `${this.baseUrl}/empleado/${empleadoId}/resumen`;
        if (anio) {
            url += `?anio=${anio}`;
        }
        fetch(url)
            .then(response => response.json())
            .then(data => {
                document.getElementById('dias_generados').value = data.total_dias_generados;
                document.getElementById('dias_tomados').value = data.total_dias_tomados;
                document.getElementById('dias_faltantes').value = Math.max(0, data.dias_pendientes);
            })
            .catch(() => {
                document.getElementById('dias_generados').value = 15;
                document.getElementById('dias_tomados').value = 0;
                document.getElementById('dias_faltantes').value = 15;
            });
    }

    actualizarDiasFaltantes() {
        const diasGen = parseInt(document.getElementById('dias_generados').value) || 0;
        const diasTom = parseInt(document.getElementById('dias_tomados').value) || 0;
        document.getElementById('dias_faltantes').value = Math.max(0, diasGen - diasTom);
    }

    showCreateModal() {
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nueva Vacación';
        this.elements.form.reset();
        this.isEditing = false;
        document.getElementById('empleado_id').value = '';
        document.getElementById('empleado_nombre').value = '';
        document.getElementById('anio_generado').value = new Date().getFullYear();
        document.getElementById('dias_generados').value = '15';
        document.getElementById('dias_tomados').value = '0';
        document.getElementById('fecha_inicio').value = '';
        document.getElementById('fecha_fin').value = '';
        document.getElementById('observaciones').value = '';
        document.getElementById('dias_faltantes').value = '15';
    }

    showEditModal(id) {
        this.fetchData(`${this.baseUrl}/${id}/edit`).then(data => {
            this.isEditing = true;
            document.getElementById('empleado_id').value = data.empleado_id;
            document.getElementById('empleado_nombre').value = data.empleado_nombre || `Empleado ID: ${data.empleado_id}`;
            document.getElementById('anio_generado').value = data.anio_generado;
            document.getElementById('dias_generados').value = data.dias_generados || 15;
            document.getElementById('dias_tomados').value = data.dias_tomados || 0;
            document.getElementById('fecha_inicio').value = data.fecha_inicio || '';
            document.getElementById('fecha_fin').value = data.fecha_fin || '';
            document.getElementById('observaciones').value = data.observaciones || '';
            const diasFalt = (data.dias_generados || 15) - (data.dias_tomados || 0);
            document.getElementById('dias_faltantes').value = Math.max(0, diasFalt);
            this.elements.methodField.value = 'PUT';
            this.elements.form.action = `${this.baseUrl}/${id}`;
            this.elements.modalTitle.textContent = 'Editar Vacación';
            this.modal.show();
        });
    }

    focusFirstField() {
        document.getElementById('empleado_nombre').focus();
    }

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