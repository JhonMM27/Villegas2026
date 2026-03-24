@extends('plantilla.app')
<!-- datatables-custom.css removed project-wide -->
@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-1">
                    <h3 class="card-title flex-grow-1">Proveedores</h3>
                    @can('proveedores_create')
                    <button type="button" class="btn btn-primary" id="btnCreate">
                        <i class="bi bi-plus-circle"></i> Nuevo
                    </button>
                    @endcan
                    <a href="{{ route('reportes.proveedores.imprimir') }}" 
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
                                    <th>Representante</th>
                                    <th>Teléfono Rep.</th>
                                    <th>Cuenta bancaria</th>
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
@canany(['proveedores_create', 'proveedores_edit'])
    @include('proveedores.action')
@endcanany
@endsection
@push('scripts')
<script>
class ProveedorManager extends CrudManager {
    constructor() {
        super("{{ url('proveedores') }}");
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
                { data: 'direccion', name: 'direccion' },
                { data: 'telefono', name: 'telefono' },
                { data: 'email', name: 'email' },
                { data: 'representante', name: 'representante' },
                { data: 'representante_telefono', name: 'representante_telefono' },
                { data: 'cuenta_bancaria', name: 'cuenta_bancaria' }
            ],
            columnDefs: [
                { targets: 0, width: '12%', className: 'text-center' },
                { targets: 1, width: '8%' },
                { targets: 2, width: '12%' },
                { targets: 3, width: '18%' },
                { targets: 4, width: '15%' },
                { targets: 5, width: '8%' },
                { targets: 6, width: '12%' },
                { targets: 7, width: '12%' },
                { targets: 8, width: '10%' },
                { targets: 9, width: '12%' }
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
            
            this.elements.modalTitle.textContent = 'Editar Proveedor: '+ response.razon_social;
            this.elements.methodField.value = 'PUT';
            
            // Llenar campos específicos
            document.getElementById('documento_tipo_codigo').value = response.documento_tipo_codigo;
            document.getElementById('documento_numero').value = response.documento_numero || '';
            document.getElementById('razon_social').value = response.razon_social || '';
            document.getElementById('direccion').value = response.direccion || '';
            document.getElementById('telefono').value = response.telefono || '';
            document.getElementById('email').value = response.email || '';
            document.getElementById('representante').value = response.representante || '';
            document.getElementById('representante_telefono').value = response.representante_telefono || '';
            document.getElementById('cuenta_bancaria').value = response.cuenta_bancaria || '';

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
        this.elements.modalTitle.textContent = 'Nuevo Proveedor';
        document.getElementById('documento_tipo_codigo').value = '01';
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new ProveedorManager();
});
document.getElementById('mnuIngreso').classList.add('menu-open');
document.getElementById('itemProveedores')?.classList.add('active');
</script>
@endpush
