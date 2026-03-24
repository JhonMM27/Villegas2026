@extends('plantilla.app')


@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Unidades</h3>
                    @can('unidades_create')
                    <button type="button" class="btn btn-primary" id="btnCreate">
                        <i class="bi bi-plus-circle"></i> Nuevo
                    </button>
                    @endcan
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="listadoTable" class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Opciones</th>
                                    <th>Código</th>
                                    <th>Descripción</th>
                                    <th>Activo</th>                               
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- /.card-body -->
                <div class="card-footer clearfix">
                    
                </div>
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
    </div>
    <!--end::Row-->
</div>
@canany(['unidades_create', 'unidades_edit'])
    @include('unidades.action')
@endcanany
@endsection
@push('scripts')
<script>
class UnidadManager extends CrudManager {
    constructor() {
        super("{{ url('unidades') }}");
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
                { data: 'codigo', name: 'codigo'},
                { data: 'descripcion', name: 'descripcion' },
                { data: 'activo', name: 'activo' }
            ],
             columnDefs: [
                { targets: 0, width: '15%', className: 'text-center' },
                { targets: 1, width: '10%' },
                { targets: 2, width: '65%' },
                { targets: 3, width: '10%', className: 'text-center' }
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
            
            this.elements.modalTitle.textContent = 'Editar Unidad: '+ response.descripcion;
            this.elements.methodField.value = 'PUT';
            
            // Llenar campos específicos
            document.getElementById('codigo').value = response.codigo || '';
            document.getElementById('descripcion').value = response.descripcion || '';
            document.getElementById('activo').checked = response.activo ? true : false;
            
            this.form.action = `${this.baseUrl}/${id}`;
            
            this.modal.show();
            
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error('Error al cargar datos:', error);
        }
    }

    focusFirstField() {
        document.getElementById('codigo').focus();
        const modalEl = this.modal._element;

        modalEl.addEventListener('shown.bs.modal', () => {
            const input = document.getElementById('codigo');
            if (input) input.focus();
        }, { once: true });
    }

    showCreateModal(){
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nueva Unidad';
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new UnidadManager();
    // Inicializar tooltips Bootstrap para iconos y botones
    try {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });
    } catch (e) {
        // Si bootstrap no está disponible en este contexto, ignorar
        console.warn('Tooltips no inicializados:', e);
    }
});
document.getElementById('mnuCatalogo').classList.add('menu-open');
document.getElementById('itemUnidades').classList.add('active');
</script>
@endpush