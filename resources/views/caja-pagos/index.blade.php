@extends('plantilla.app')


@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Caja</h3>
                    @can('caja_pagos_create')
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
                                    <th>Secuencia</th>
                                    <th>Fecha</th>
                                    <th>Interno</th>
                                    <th>Recibo</th>
                                    <th>Entrada</th>
                                    <th>Salida</th>
                                    <th>Persona</th>
                                    <th>Situación</th>
                                    <th>Movimiento</th>
                                    <th>Comentario</th>
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
@canany(['caja_pagos_create', 'caja_pagos_edit'])
    @include('caja-pagos.action')
@endcanany
@endsection
@push('scripts')
<script>
class CajaPagoManager extends CrudManager {
    constructor() {
        super("{{ url('caja-pagos') }}");
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
                { data: 'fecha_caja', name: 'fecha_caja' },
                { data: 'numero_interno', name: 'numero_interno' },
                { data: 'numero_recibo', name: 'numero_recibo' },
                { data: 'entrada', name: 'entrada' },
                { data: 'salida', name: 'salida' },
                { data: 'persona_nombre', name: 'persona_nombre' },
                { data: 'situacion', name: 'situacion' },
                { data: 'cobranza_tipo_nombre', name: 'cobranza_tipo_nombre' },
                { data: 'comentario', name: 'comentario' }
            ],
             columnDefs: [
                { targets: 0, width: '5%', className: 'text-center' },
                { targets: 1, width: '5%' },
                { targets: 2, width: '5%' },
                { targets: 3, width: '10%', className: 'text-center' },
                { targets: 4, width: '10%', className: 'text-center' },
                { targets: 5, width: '10%', className: 'text-end' },
                { targets: 6, width: '10%', className: 'text-end' },
                { targets: 7, width: '10%' },
                { targets: 8, width: '10%', className: 'text-center' },
                { targets: 9, width: '5%' },
                { targets: 10, width: '20%' }
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
            
            this.elements.modalTitle.textContent = 'Editar Línea: '+ response.nombre;
            this.elements.methodField.value = 'PUT';
            
            document.getElementById('id').value = response.id || '';
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
        const modalEl = this.modal._element;

        modalEl.addEventListener('shown.bs.modal', () => {
            const input = document.getElementById('nombre');
            if (input) input.focus();
        }, { once: true });
    }

    showCreateModal(){
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nueva Línea';
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new CajaPagoManager();
});
document.getElementById('mnuCaja').classList.add('menu-open');
document.getElementById('itemCajaPagos')?.classList.add('active');
</script>
@endpush
