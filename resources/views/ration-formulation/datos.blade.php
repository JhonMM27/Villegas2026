@extends('plantilla.app')
@push('estilos')
<style>
    #listadoTable thead th {
        white-space: nowrap;
        vertical-align: middle;
    }
</style>
@endpush
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Datos Nutricionales de Ingredientes</h3>
                    <a href="{{ route('formulas-alimento.index') }}" class="btn btn-secondary btn-sm me-2">
                        <i class="bi bi-calculator"></i> Formulación Vacunos
                    </a>
                    <a href="{{ route('formulas-alimento-cerdo.index') }}" class="btn btn-secondary btn-sm me-2">
                        <i class="bi bi-piggy-bank"></i> Formulación Cerdos
                    </a>
                    @can('ration_datos_create')
                    <button type="button" class="btn btn-primary" id="btnCreate">
                        <i class="bi bi-plus-circle"></i> Nuevo Dato
                    </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="overflow-x: auto;">
                        <table id="listadoTable" class="table table-striped table-hover table-sm align-middle"
                               style="min-width: 2400px; width: 100%;">
                            <thead>
                                <tr>
                                    <th style="width: 90px; min-width: 90px;">Opciones</th>
                                    <th style="width: 200px; min-width: 200px;">Ingrediente</th>
                                    <th style="width: 110px; min-width: 110px;">Procedencia</th>
                                    <th style="width: 120px; min-width: 120px;">Clasificación</th>
                                    <th style="width: 120px; min-width: 120px;">Nutriente</th>
                                    <th class="text-end" style="min-width: 70px;">M.S %</th>
                                    <th class="text-end" style="min-width: 70px;">P.C %</th>
                                    <th class="text-end" style="min-width: 70px;">ENL</th>
                                    <th class="text-end" style="min-width: 70px;">EM</th>
                                    <th class="text-end" style="min-width: 70px;">FDN %</th>
                                    <th class="text-end" style="min-width: 70px;">Fibra %</th>
                                    <th class="text-end" style="min-width: 70px;">FDA %</th>
                                    <th class="text-end" style="min-width: 70px;">Grasa %</th>
                                    <th class="text-end" style="min-width: 70px;">Calcio %</th>
                                    <th class="text-end" style="min-width: 70px;">Fósforo %</th>
                                    <th class="text-end" style="min-width: 70px;">Magnesio %</th>
                                    <th class="text-end" style="min-width: 70px;">Almidón %</th>
                                    <th class="text-end" style="min-width: 70px;">Azúcar %</th>
                                    <th class="text-end" style="min-width: 70px;">Ceniza %</th>
                                    <th class="text-end" style="min-width: 70px;">Lactosa %</th>
                                    <th class="text-end" style="min-width: 70px;">Lisina %</th>
                                    <th class="text-end" style="min-width: 70px;">Metionina %</th>
                                    <th class="text-end" style="min-width: 70px;">Treonina %</th>
                                    <th style="width: 100px; min-width: 100px;">Estado</th>
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
@canany(['ration_datos_create', 'ration_datos_edit'])
    @include('ration-formulation.datos-action')
@endcanany
<div id="modalContainer"></div>
@endsection
@push('scripts')
<script>
class DatosNutricionalesManager extends CrudManager {
    constructor() {
        super("{{ route('ration-formulation.datos.datatable') }}");
        this.afterSuccess = () => {};
        this.initializeDataTable();
    }

    initializeDataTable() {
        this.tabla = $(this.elements.table).DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: this.baseUrl, type: 'GET' },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'ingrediente', name: 'ingrediente' },
                { data: 'procedencia', name: 'procedencia' },
                { data: 'clasificacion', name: 'clasificacion' },
                { data: 'nutriente', name: 'nutriente' },
                { data: 'materia_seca', name: 'materia_seca', className: 'text-end' },
                { data: 'proteina_cruda', name: 'proteina_cruda', className: 'text-end' },
                { data: 'enl', name: 'enl', className: 'text-end' },
                { data: 'em', name: 'em', className: 'text-end' },
                { data: 'fdn', name: 'fdn', className: 'text-end' },
                { data: 'fibra', name: 'fibra', className: 'text-end' },
                { data: 'fda', name: 'fda', className: 'text-end' },
                { data: 'grasa', name: 'grasa', className: 'text-end' },
                { data: 'calcio', name: 'calcio', className: 'text-end' },
                { data: 'fosforo', name: 'fosforo', className: 'text-end' },
                { data: 'magnesio', name: 'magnesio', className: 'text-end' },
                { data: 'almidon', name: 'almidon', className: 'text-end' },
                { data: 'azucar', name: 'azucar', className: 'text-end' },
                { data: 'ceniza', name: 'ceniza', className: 'text-end' },
                { data: 'lactosa', name: 'lactosa', className: 'text-end' },
                { data: 'lisina', name: 'lisina', className: 'text-end' },
                { data: 'metionina', name: 'metionina', className: 'text-end' },
                { data: 'treonina', name: 'treonina', className: 'text-end' },
                { data: 'activo', name: 'activo', orderable: true, searchable: false }
            ],
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[1, 'asc']],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
            responsive: false,
            scrollX: true
        });
    }

    showCreateModal() {
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nuevo Dato Nutricional';
        this.elements.methodField.value = '';
        this.form.action = "{{ route('ration-formulation.datos.store') }}";
        this.form.reset();
        document.getElementById('activo').checked = true;
    }

    async showEditModal(id) {
        try {
            const response = await this.fetchData(`{{ url('ration-formulation/datos') }}/${id}`);
            if (!response.success) return;

            this.isEditing = true;
            this.resetForm();
            this.elements.modalTitle.textContent = 'Editar Dato Nutricional';
            this.elements.methodField.value = 'PUT';
            this.form.action = `{{ url('ration-formulation/datos') }}/${id}`;

            const item = response.data;
            const campos = [
                'ingrediente','procedencia','clasificacion','nutriente',
                'materia_seca','proteina_cruda','enl','em','fdn','fibra','fda','grasa',
                'calcio','fosforo','magnesio',
                'almidon','azucar','ceniza','lactosa',
                'lisina','metionina','treonina'
            ];
            campos.forEach(campo => {
                const el = document.getElementById(campo);
                if (el) el.value = item[campo] ?? '';
            });
            document.getElementById('activo').checked = !!item.activo;

            this.modal.show();
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error(error);
        }
    }

    confirmDelete(id, texto = '') {
        Swal.fire({
            title: '¿Eliminar dato nutricional?',
            text: texto ? `Se eliminará "${texto}". Esta acción no se puede deshacer.` : 'Esta acción no se puede deshacer. Si está siendo usado por algún ingrediente, no se permitirá.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) this.deleteRecord(id);
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const manager = new DatosNutricionalesManager();

    document.body.addEventListener('click', function(e) {
        if (e.target.closest('.btn-action-edit')) {
            const id = e.target.closest('.btn-action-edit').dataset.id;
            manager.showEditModal(id);
        }
        if (e.target.closest('.btn-action-delete')) {
            const id = e.target.closest('.btn-action-delete').dataset.id;
            const texto = e.target.closest('.btn-action-delete').dataset.texto || '';
            manager.confirmDelete(id, texto);
        }
    });

    document.getElementById('mnuNutricion')?.classList.add('menu-open');
    document.getElementById('itemRationDatos')?.classList.add('active');
});
</script>
@endpush
