@extends('plantilla.app')
<!-- datatables-custom.css removed project-wide -->
@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-1">
                    <h3 class="card-title flex-grow-1">Clientes</h3>
                    @can('clientes_create')
                    <button type="button" class="btn btn-primary" id="btnCreate">
                        <i class="bi bi-plus-circle"></i> Nuevo
                    </button>
                    @endcan
                    <a href="{{ route('reportes.clientes.imprimir') }}" 
                    target="_blank" 
                    class="btn btn-danger">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="listadoTable" class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Opciones</th>
                                    <th>Tipo Documento</th>
                                    <th>Número Documento</th>
                                    <th>Razón Social</th>
                                    <th>Dirección</th>
                                    <th>Teléfono</th>
                                    <th>Email</th>
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
@canany(['clientes_create', 'clientes_edit'])
    @include('clientes.action')
@endcanany
@endsection
@push('scripts')
<script>
class ClienteManager extends CrudManager {
    constructor() {
        super("{{ url('clientes') }}");
        this.initializeDataTable();
        this.populateSelect('documento_tipo_codigo', '{{ route("documento-tipos.select") }}', item =>
            `<option value="${item.codigo}">${item.codigo} - ${item.descripcion}</option>`
        );
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
                { data: 'documento_tipo', name: 'documento_tipo.descripcion', orderable: false, searchable: false},
                { data: 'documento_numero', name: 'documento_numero' },
                { data: 'razon_social', name: 'razon_social' },
                { data: 'direccion', name: 'direccion', orderable: false, searchable: false },
                { data: 'telefono', name: 'telefono' },
                { data: 'email', name: 'email' }
            ],
            columnDefs: [
                { targets: 0, width: '15%', className: 'text-center' },
                { targets: 1, width: '15%' },
                { targets: 2, width: '15%' },
                { targets: 3, width: '15%' },
                { targets: 4, width: '20%' },
                { targets: 5, width: '10%' },
                { targets: 6, width: '10%' }
            ],
            responsive: true,
            order: [[3, 'asc']]
        });
    }

    async showEditModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);
            
            this.isEditing = true;
            this.resetForm();
            
            this.elements.modalTitle.textContent = 'Editar Cliente: '+ response.razon_social;
            this.elements.methodField.value = 'PUT';
            
            // Llenar campos específicos
            document.getElementById('documento_tipo_codigo').value = response.documento_tipo_codigo;
            document.getElementById('documento_numero').value = response.documento_numero || '';
            document.getElementById('razon_social').value = response.razon_social || '';
            document.getElementById('direccion').value = response.direccion || '';
            document.getElementById('telefono').value = response.telefono || '';
            document.getElementById('email').value = response.email || '';

            this.form.action = `${this.baseUrl}/${id}`;
            
            this.modal.show();
            
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error('Error al cargar datos:', error);
        }
    }
    focusFirstField() {
        document.getElementById('razon_social').focus();
        const modalEl = this.modal._element;

        modalEl.addEventListener('shown.bs.modal', () => {
            const input = document.getElementById('razon_social');
            if (input) input.focus();
        }, { once: true });
    }
    
    showCreateModal(){
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nuevo Cliente';
        document.getElementById('documento_tipo_codigo').value = '01';
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new ClienteManager();
});
document.getElementById('mnuSalida').classList.add('menu-open');
document.getElementById('itemClientes').classList.add('active');
</script>
@endpush