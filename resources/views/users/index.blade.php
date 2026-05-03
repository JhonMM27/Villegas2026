@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1"><i class="bi bi-people me-2"></i>Usuarios</h3>
                    @can('users_create')
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
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Activo</th>
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
            .then(roles => {
                const container = document.getElementById('checkbox-roles');
                container.innerHTML = '';
                roles.forEach(r => {
                    const col = document.createElement('div');
                    col.className = 'col-md-4 mb-1';
                    const checked = marcados.includes(r.name) ? 'checked' : '';
                    col.innerHTML = `
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="roles[]" value="${r.name}" id="role_${r.id}" ${checked}>
                            <label class="form-check-label" for="role_${r.id}">${r.name}</label>
                        </div>
                    `;
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
            ajax: { url: this.baseUrl, type: 'GET' },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'email', name: 'email' },
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
            this.elements.modalTitle.textContent = 'Editar Usuario: ' + response.name;
            this.elements.methodField.value = 'PUT';
            document.getElementById('name').value = response.name || '';
            document.getElementById('email').value = response.email || '';
            document.getElementById('activo').value = response.activo ? '1' : '0';
            const rolesMarcados = (response.roles || []).map(r => r.name);
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

    showCreateModal() {
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