@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">
                        Ingresos a Caja
                    </h3>
                    @can('caja_ingresos_create')
                        <button type="button" class="btn btn-primary" id="btnCreate">
                            <i class="bi bi-plus-circle"></i> Nuevo Ingreso
                        </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="listadoTable" class="table table-striped table-hover table-sm align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 90px;">Opciones</th>
                                    <th style="width: 100px;">Fecha</th>
                                    <th style="width: 180px;">Caja</th>
                                    <th style="width: 130px;">Monto</th>
                                    <th style="width: 200px;">Usuario</th>
                                    <th>Comentario</th>
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

@canany(['caja_ingresos_create', 'caja_ingresos_edit'])
    @include('caja-ingresos.action')
@endcanany

<div id="modalContainer"></div>
@endsection

@push('scripts')
<script>
class CajaIngresoManager extends CrudManager {
    constructor() {
        super("{{ url('caja-ingresos') }}");
        this.afterSuccess = () => {};
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
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'fecha', name: 'fecha' },
                { data: 'caja_destino', name: 'caja_destino' },
                { data: 'monto', name: 'monto' },
                { data: 'user_nombre', name: 'user_nombre' },
                { data: 'comentario', name: 'comentario' }
            ],
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, className: 'text-center' },
                { targets: 3, className: 'text-end' }
            ],
            order: [[1, 'desc']],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            responsive: true
        });
    }

    showCreateModal() {
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nuevo Ingreso a Caja';
        this.elements.methodField.value = '';
        this.form.action = this.baseUrl;
        this.form.reset();
        document.getElementById('caja_destino').value = 'P';
        document.getElementById('fecha').value = new Date().toISOString().split('T')[0];

        if (typeof window.reinitFlatpickr === 'function') {
            window.reinitFlatpickr();
        }
    }

    async showEditModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}/edit`);
            if (!response.success) {
                this.showNotification('error', response.message || 'Error al cargar');
                return;
            }

            this.isEditing = true;
            this.resetForm();
            this.elements.modalTitle.textContent = 'Editar Ingreso a Caja';
            this.elements.methodField.value = 'PUT';
            this.form.action = `${this.baseUrl}/${id}`;

            const item = response.ingreso;
            document.getElementById('fecha').value = item.fecha ? item.fecha.split('T')[0] : '';
            document.getElementById('monto').value = item.monto;
            document.getElementById('caja_destino').value = item.caja_destino;
            document.getElementById('comentario').value = item.comentario || '';

            this.modal.show();

            if (typeof window.reinitFlatpickr === 'function') {
                window.reinitFlatpickr();
            }
        } catch (error) {
            this.showNotification('error', 'Error al cargar el ingreso');
            console.error(error);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.cajaIngresoManager = new CajaIngresoManager();
    document.getElementById('mnuCaja')?.classList.add('menu-open');
    document.getElementById('itemCajaIngresos')?.classList.add('active');
});
</script>
@endpush
