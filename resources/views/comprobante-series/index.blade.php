@extends('plantilla.app')
<!-- datatables-custom.css removed project-wide -->
@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Correlativos de los comprobantes</h3>
                    @can('comprobante_tipos_create')
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
                                    <th>Comprobante</th>
                                    <th>Serie actual</th>
                                    <th>Correlativo actual</th>
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
@canany(['comprobante_tipos_create', 'comprobante_tipos_edit'])
    @include('comprobante-series.action')
@endcanany
@endsection
@push('scripts')
<script>
class ComprobanteSerieManager extends CrudManager {
    constructor() {
        super("{{ url('comprobante-series') }}");
        this.initializeDataTable();

        this.populateSelect('comprobante_tipo_codigo', '{{ route("comprobante-tipos.select") }}', item =>
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
                { data: 'tipo', name: 'tipo'},
                { data: 'serie', name: 'serie'},
                { data: 'correlativo', name: 'correlativo' }
            ],
            columnDefs: [
                { targets: 0, width: '15%', className: 'text-center' },
                { targets: 1, width: '30%' },
                { targets: 2, width: '20%' },
                { targets: 3, width: '35%' }
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
            
            this.elements.modalTitle.textContent = 'Editar Correlativo: '+ response.serie +'-'+response.correlativo;
            this.elements.methodField.value = 'PUT';
            
            // Llenar campos específicos
            document.getElementById('comprobante_tipo_codigo').value = response.comprobante_tipo_codigo || '';
            document.getElementById('serie').value = response.serie || '';
            document.getElementById('correlativo').value = response.correlativo || '';

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
            const input = document.getElementById('serie');
            if (input) input.focus();
        }, { once: true });
    }

    showCreateModal(){
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nuevo Correlativo';
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new ComprobanteSerieManager();
});
document.getElementById('mnuConfiguracion').classList.add('menu-open');
document.getElementById('itemComprobanteSerie').classList.add('active');
</script>
@endpush