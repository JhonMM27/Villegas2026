@extends('plantilla.app')


@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Certificados SUNAT</h3>
                    @can('sunat_create')
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
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Archivo</th>
                                    <th>Activo</th>
                                    <th>Expira</th>
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
@canany(['sunat_create', 'sunat_edit'])
    @include('sunat-certificados.action')
@endcanany
@endsection
@push('scripts')
<script>
class SunatManager extends CrudManager {
    constructor() {
        super("{{ url('sunat-certificados') }}");
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
                { data: 'id', name: 'id'},
                { data: 'nombre', name: 'nombre' },
                { data: 'certificado_path', name: 'certificado_path' },
                { data: 'activo', name: 'activo' },
                { data: 'expires_at', name: 'expires_at' }
            ],
             columnDefs: [
                { targets: 0, width: '15%', className: 'text-center' },
                { targets: 1, width: '8%' },
                { targets: 2, width: '40%' },
                { targets: 3, width: '20%' },
                { targets: 4, width: '7%', className: 'text-center' },
                { targets: 5, width: '10%' }
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
            
            this.elements.modalTitle.textContent = 'Editar registro: '+ response.nombre;
            this.elements.methodField.value = 'PUT';
            
            document.getElementById('id').value = response.id || '';
            document.getElementById('nombre').value = response.nombre || '';
            document.getElementById('certificado_path').value = response.certificado_path || '';
            document.getElementById('password').value = response.password || '';
            document.getElementById('activo').checked = response.activo ? true : false;
            this.setFieldValue('expires_at', response.expires_at || '');

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
}
document.addEventListener('DOMContentLoaded', () => {
    new SunatManager();
});
document.getElementById('mnuConfiguracion').classList.add('menu-open');
document.getElementById('itemSunat')?.classList.add('active');
</script>
@endpush
