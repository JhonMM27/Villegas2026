@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1"><i class="bi bi-shield me-2"></i>Roles</h3>
                    @can('roles_create')
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
                                    <th>Permisos</th>
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
@canany(['roles_create', 'roles_edit'])
    @include('roles.action')
@endcanany
@endsection
@push('scripts')
<script>
class RoleManager extends CrudManager {
    constructor() {
        super("{{ url('roles') }}");
        this.initializeDataTable();
        this.loadPermissions();
    }

    loadPermissions(marcados = []) {
        fetch('{{ route("permisos.select") }}')
            .then(response => response.json())
            .then(permisos => {
                const container = document.getElementById('checkbox-permisos');
                container.innerHTML = '';

                const getGroupKey = (name) => {
                    if (name === 'super_admin') return 'zz_especial';
                    if (name.startsWith('dashboard_')) return 'yy_dashboard';
                    if (name.endsWith('_report')) return 'yx_reportes';
                    const i = name.indexOf('_');
                    return (i > 0) ? name.substring(0, i) : 'otros';
                };

                const getGroupTitle = (key) => {
                    if (key === 'zz_especial') return 'ESPECIAL';
                    if (key === 'yy_dashboard') return 'DASHBOARD';
                    if (key === 'yx_reportes') return 'REPORTES';
                    return key.replace(/_/g, ' ').toUpperCase();
                };

                const groups = {};
                permisos.forEach(p => {
                    const g = getGroupKey(p.name);
                    if (!groups[g]) groups[g] = [];
                    groups[g].push(p);
                });

                const keys = Object.keys(groups)
                    .filter(k => !['yy_dashboard','yx_reportes','zz_especial'].includes(k))
                    .sort((a,b) => a.localeCompare(b));

                ['yy_dashboard','yx_reportes','zz_especial'].forEach(k => {
                    if (groups[k]) keys.push(k);
                });

                keys.forEach(groupKey => {
                    const title = getGroupTitle(groupKey);
                    const head = document.createElement('div');
                    head.className = 'col-12 mt-2';
                    head.innerHTML = `
                        <div class="d-flex align-items-center justify-content-between">
                            <strong>${title}</strong>
                            <span class="text-muted small">${groups[groupKey].length} permisos</span>
                        </div>
                        <hr class="my-1">
                    `;
                    container.appendChild(head);

                    const sortedPerms = groups[groupKey].slice().sort((a,b) => a.name.localeCompare(b.name));
                    const cols = [[], [], [], []];
                    sortedPerms.forEach((p, i) => cols[Math.min(i % 4, 3)].push(p));

                    const rowDiv = document.createElement('div');
                    rowDiv.className = 'col-12';
                    rowDiv.innerHTML = '<div class="row"></div>';
                    const innerRow = rowDiv.firstElementChild;

                    cols.forEach(half => {
                        const colDiv = document.createElement('div');
                        colDiv.className = 'col-md-3 mb-2';
                        half.forEach(p => {
                            const checked = marcados.includes(p.name) ? 'checked' : '';
                            colDiv.innerHTML += `
                                <div class="form-check small mb-1">
                                    <input type="checkbox" class="form-check-input" name="permissions[]" value="${p.name}" id="perm_${p.id}" ${checked}>
                                    <label class="form-check-label text-wrap w-100" for="perm_${p.id}" style="word-break: break-word; line-height: 1.1;">${p.name}</label>
                                </div>
                            `;
                        });
                        innerRow.appendChild(colDiv);
                    });

                    container.appendChild(rowDiv);
                });
            })
            .catch(error => {
                console.error('Error al cargar permisos:', error);
                document.getElementById('permissions-error').textContent = 'No se pudieron cargar los permisos.';
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
                { data: 'permissions', name: 'permissions' }
            ],
            columnDefs: [
                { targets: 0, width: '15%', className: 'text-center' },
                { targets: 1, width: '15%' },
                { targets: 2, width: '70%' }
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
            this.elements.modalTitle.textContent = 'Editar Rol: ' + response.name;
            this.elements.methodField.value = 'PUT';
            document.getElementById('name').value = response.name || '';
            const permisosMarcados = (response.permissions || []).map(p => p.name);
            this.loadPermissions(permisosMarcados);
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
        this.elements.modalTitle.textContent = 'Nuevo Rol';
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new RoleManager();
});
document.getElementById('mnuSeguridad').classList.add('menu-open');
document.getElementById('itemRoles').classList.add('active');
</script>
@endpush