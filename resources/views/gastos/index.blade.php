@extends('plantilla.app')
<!-- datatables-custom.css removed project-wide -->
@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Gastos</h3>
                    @can('gastos_create')
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
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Descripción</th>
                                    <th>Responsable</th>
                                    <th>Recibo</th>
                                    <th>Monto</th>
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
@canany(['gastos_create', 'gastos_edit'])
    @include('gastos.action')
@endcanany
<div id="modalContainer"></div>
@endsection
@push('scripts')
<script>
class GastoManager extends CrudManager {
    constructor() {
        super("{{ url('gastos') }}");
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
                 { 
                     data: 'fecha_gasto', 
                     name: 'fecha_gasto',
                     render: function(data) {
                         if (!data) return '';
                         const fecha = new Date(data);
                         const day = String(fecha.getDate()).padStart(2, '0');
                         const month = String(fecha.getMonth() + 1).padStart(2, '0');
                         const year = fecha.getFullYear();
                         const hours = String(fecha.getHours()).padStart(2, '0');
                         const minutes = String(fecha.getMinutes()).padStart(2, '0');
                         return `${day}/${month}/${year} ${hours}:${minutes}`;
                     }
                 },
                { data: 'user_nombre', name: 'user_nombre'},                
                { data: 'descripcion', name: 'descripcion' },
                { data: 'responsable', name: 'responsable' },
                { data: 'numero_recibo', name: 'numero_recibo' },
                { data: 'monto', name: 'monto' }
            ],
            columnDefs: [
                { targets: 0, width: '15%', className: 'text-center' },
                { targets: 1, width: '15%' },
                { targets: 2, width: '15%' },
                { targets: 3, width: '20%', className: 'text-center' },
                { targets: 4, width: '15%' },
                { targets: 5, width: '10%' },
                { targets: 6, width: '10%' },
            ],
            responsive: true,
            order: [[1, 'desc']]
        });
    }

    async showEditModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);
            
            this.isEditing = true;
            this.resetForm();
            
            this.elements.modalTitle.textContent = 'Editar Gasto: '+ response.numero_recibo;
            this.elements.methodField.value = 'PUT';
            
            // Llenar campos específicos
            document.getElementById('fecha_gasto').value = (response.fecha_gasto || '').replace(' ', 'T').slice(0,16);
            //document.getElementById('numero_recibo').value = response.numero_recibo || '';
            document.getElementById('numero_interno').value = response.numero_interno || '';
            document.getElementById('total_cobranza').value = response.monto || 0;
            document.getElementById('principal').value = response.importe_p || 0;
            document.getElementById('deposito').value = response.importe_d || 0;
            document.getElementById('consorcio').value = response.importe_c || 0;
            document.getElementById('usuario_nombre').textContent = response.user_nombre|| '';
            document.getElementById('responsable').value = response.responsable || '';
            document.getElementById('descripcion').value = response.descripcion || '';

            this.form.action = `${this.baseUrl}/${id}`;
            
            this.modal.show();
            
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error('Error al cargar datos:', error);
        }
    }
    
    focusFirstField() {
        document.getElementById('principal').focus();
        const modalEl = this.modal._element;

        modalEl.addEventListener('shown.bs.modal', () => {
            const input = document.getElementById('principal');
            if (input) input.focus();
        }, { once: true });
    }

    showCreateModal(){
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nuevo Gasto';
        document.getElementById('fecha_gasto').value = this.obtenerFechaHoraActual();
    }

    obtenerFechaHoraActual() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');

        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    toNumber(val) {
        if (val === null || val === undefined) return 0;
        // Por si entra con comas
        const n = parseFloat(String(val).replace(/,/g, '').trim());
        return isNaN(n) ? 0 : n;
    }

    calcCobranza() {
        const principal = this.toNumber(document.getElementById('principal')?.value);
        const deposito  = this.toNumber(document.getElementById('deposito')?.value);
        const consorcio = this.toNumber(document.getElementById('consorcio')?.value);

        const total = principal + deposito + consorcio;

        const totalInput = document.getElementById('total_cobranza');
        if (totalInput) totalInput.value = total.toFixed(2);
    }

    bindCobranzaAutoSum() {
        const ids = ['principal', 'deposito', 'consorcio'];

        ids.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;

            // Evitar duplicar listeners cada vez que abras el modal
            if (el.dataset.boundCobranza === '1') return;
            el.dataset.boundCobranza = '1';

            el.addEventListener('input', () => this.calcCobranza());
            el.addEventListener('change', () => this.calcCobranza());
        });

        // calcula una vez al inicio (por si ya viene con valores cargados)
        this.calcCobranza();
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new GastoManager();
    document.body.addEventListener('click', function(e) {
        if (e.target && (e.target.matches('.btn-view-gasto') || e.target.closest('.btn-view-gasto'))) {
            const button = e.target.closest('.btn-view-gasto');
            const gastoId = button.getAttribute('data-id');
            if (!gastoId) return;

            const url = "{{ route('gastos.ver', ':id') }}".replace(':id', gastoId);

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('No se pudo cargar la gasto');
                    return response.text();
                })
                .then(html => {
                    let modalContainer = document.getElementById('modalContainer');
                    if (!modalContainer) {
                        modalContainer = document.createElement('div');
                        modalContainer.id = 'modalContainer';
                        document.body.appendChild(modalContainer);
                    }
                    modalContainer.innerHTML = html;

                    const modalEl = modalContainer.querySelector('.modal');
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                })
                .catch(err => {
                    console.error(err);
                    //this.showNotification('error', 'Error al cargar el detalle');
                });
        }
    });
});
document.getElementById('mnuCaja').classList.add('menu-open');
document.getElementById('itemGastos').classList.add('active');
</script>
@endpush