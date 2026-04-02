@extends('plantilla.app')

@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Costo Servicio Preparada</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="listadoTable" class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Clave</th>
                                    <th>Valor</th>
                                    <th>Descripción</th>
                                    <th>Última Actualización</th>
                                    <th>Opciones</th>
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
@can('configuraciones_edit')
    @include('configuraciones.action')
@endcan
@endsection

@push('scripts')
<script>
class ConfiguracionManager {
    constructor() {
        this.baseUrl = "{{ url('configuraciones') }}";
        this.initializeDataTable();
        this.setupEventListeners();
    }

    initializeDataTable() {
        this.tabla = $('#listadoTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: this.baseUrl,
                type: 'GET'
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'clave', name: 'clave' },
                { data: 'valor', name: 'valor', className: 'text-end' },
                { data: 'descripcion', name: 'descripcion' },
                { data: 'updated_at', name: 'updated_at', className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            columnDefs: [
                { targets: 0, width: '8%' },
                { targets: 1, width: '30%' },
                { targets: 2, width: '15%' },
                { targets: 3, width: '30%' },
                { targets: 4, width: '12%' },
                { targets: 5, width: '5%' }
            ],
            responsive: true,
            order: [[0, 'asc']]
        });
    }

    setupEventListeners() {
        $(document).on('click', '.btn-edit', (e) => this.showEditModal($(e.currentTarget).data('id')));
        $('#formUpdate').on('submit', (e) => this.handleSubmit(e));
    }

    async showEditModal(id) {
        try {
            const response = await fetch(`${this.baseUrl}/${id}`);
            const data = await response.json();

            document.getElementById('config_id').value = data.id;
            document.getElementById('config_clave').value = data.clave;
            document.getElementById('config_valor').value = data.valor;
            document.getElementById('config_descripcion').value = data.descripcion || '';

            this.modal = new bootstrap.Modal(document.getElementById('modalUpdate'));
            this.modal.show();
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error(error);
        }
    }

    async handleSubmit(e) {
        e.preventDefault();
        const id = document.getElementById('config_id').value;
        const valor = document.getElementById('config_valor').value;

        try {
            const response = await fetch(`${this.baseUrl}/${id}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ valor })
            });

            const result = await response.json();

            if (result.success) {
                this.modal?.hide();
                this.tabla.ajax.reload();
                this.showNotification('success', result.message);
            } else {
                this.showNotification('error', result.message);
            }
        } catch (error) {
            this.showNotification('error', 'Error al guardar los cambios');
            console.error(error);
        }
    }

    showNotification(type, message) {
        Swal.fire({
            icon: type,
            title: type === 'success' ? 'Éxito' : 'Error',
            text: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new ConfiguracionManager();
});
document.getElementById('mnuConfiguracion')?.classList.add('menu-open');
document.getElementById('itemCostoServicioPreparada')?.classList.add('active');
</script>
@endpush
