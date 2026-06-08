@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Categorías de Costos</h3>
                    @can('costo_categorias_create')
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
                                    <th>Nombre</th>
                                    <th>Activo</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer clearfix">
                </div>
            </div>
        </div>
    </div>
</div>
@canany(['costo_categorias_create', 'costo_categorias_edit'])
    @include('costo-categorias.action')
@endcanany
@endsection
@push('scripts')
<script>
class CostoCategoriaManager extends CrudManager {
    constructor() {
        super("{{ url('costo-categorias') }}");
        this.initializeDataTable();
    }

    initializeDataTable() {
        this.tabla = $(this.elements.table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: this.baseUrl,
                type: 'GET'
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false},
                { data: 'nombre', name: 'nombre' },
                { data: 'activo', name: 'activo' }
            ],
            columnDefs: [
                { targets: 0, width: '15%', className: 'text-center' },
                { targets: 1, width: '70%' },
                { targets: 2, width: '15%', className: 'text-center' }
            ],
            responsive: true,
            order: [[1, 'asc']]
        });
    }

    async showEditModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);

            this.isEditing = true;
            this.resetForm();

            this.elements.modalTitle.textContent = 'Editar Categoría: ' + response.nombre;
            this.elements.methodField.value = 'PUT';

            document.getElementById('nombre').value = response.nombre || '';
            document.getElementById('activo').checked = response.activo ? true : false;

            this.form.action = `${this.baseUrl}/${id}`;

            this.modal.show();

        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error('Error al cargar datos:', error);
        }
    }

    focusFirstField() {
        document.getElementById('nombre').focus();
    }

    showCreateModal() {
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nueva Categoría de Costo';
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new CostoCategoriaManager();
});
document.getElementById('mnuConfiguracion').classList.add('menu-open');
document.getElementById('itemCostoCategoria').classList.add('active');
</script>
@endpush
