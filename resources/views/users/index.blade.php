@extends('plantilla.app')
<!-- datatables-custom.css removed project-wide -->
@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Usuarios</h3>
                    @can('users_create')
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
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
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
@canany(['users_create', 'users_edit'])
    @include('users.action')
@endcanany
@endsection
@push('scripts')
<script>
class UserManager extends CrudManager {
    constructor() {
        super("{{ url('usuarios') }}");
        this.initializeDataTable();
        this.loadRoles();
    }

    loadRoles(marcados = []) {
        fetch('{{ route("roles.select") }}')
            .then(response => response.json())
            .then(permisos => {
                const container = document.getElementById('checkbox-roles');
                container.innerHTML = '';
                permisos.forEach(p => {
                    const col = document.createElement('div');
                    col.className = 'col-md-3 mb-1';

                    const div = document.createElement('div');
                    div.className = 'form-check';

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.className = 'form-check-input';
                    checkbox.name = 'roles[]';
                    checkbox.value = p.name;
                    checkbox.id = `perm_${p.id}`;
                    if (marcados.includes(p.name)) checkbox.checked = true;

                    const label = document.createElement('label');
                    label.className = 'form-check-label';
                    label.htmlFor = `perm_${p.id}`;
                    label.textContent = p.name;

                    div.appendChild(checkbox);
                    div.appendChild(label);
                    col.appendChild(div);
                    container.appendChild(col);
                });
            })
            .catch(error => {
                console.error('Error al cargar roles:', error);
                document.getElementById('roles-error').textContent = 'No se pudieron cargar los roles.';
            });
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
                { data: 'name', name: 'name'},
                { data: 'email', name: 'email'},
                { data: 'roles', name: 'roles' },
                { data: 'activo', name: 'activo' }
            ],
            columnDefs: [
                { targets: 0, width: '15%', className: 'text-center' },
                { targets: 1, width: '30%' },
                { targets: 2, width: '25%' },
                { targets: 3, width: '15%' },
                { targets: 4, width: '15%' }
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
            
            this.elements.modalTitle.textContent = 'Editar Usuario: '+ response.name;
            this.elements.methodField.value = 'PUT';
            
            // Llenar campos específicos
            document.getElementById('name').value = response.name || '';
            document.getElementById('email').value = response.email || '';
            document.getElementById('activo').value = response.activo ? '1' : '0';
            // Llamar a loadRoles con roles marcados
            const rolesMarcados = (response.roles || []).map(p => p.name);
            this.loadRoles(rolesMarcados);

            this.form.action = `${this.baseUrl}/${id}`;
            
            this.modal.show();
            
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error('Error al cargar datos:', error);
        }
    }
    focusFirstField() {
        document.getElementById('name').focus();
        const modalEl = this.modal._element;

        modalEl.addEventListener('shown.bs.modal', () => {
            const input = document.getElementById('name');
            if (input) input.focus();
        }, { once: true });
    }

    showCreateModal(){
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nuevo Usuario';
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new UserManager();
});
document.getElementById('mnuSeguridad').classList.add('menu-open');
document.getElementById('itemUsuarios').classList.add('active');
</script>
@endpush