@extends('plantilla.app')
<!-- datatables-custom.css removed project-wide -->
@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Productos</h3>
                    @can('productos_create')
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
                                    <th>Unidad</th>
                                    <th>Linea</th>
                                    <th>Nombre</th>
                                    <th>Empaque</th>
                                    <th>Stock Almacén</th>
                                    <th>Costo Unitario</th>
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
@canany(['productos_create', 'productos_edit'])
    @include('productos.action')
@endcan
<div id="modalContainer"></div>
@endsection
@push('scripts')
<script>
class ProductoManager extends CrudManager {
    constructor() {
        super("{{ url('productos') }}");
        this.initializeDataTable();        

        this.populateSelect('unidad_codigo', '{{ route("unidades.select") }}', item =>
            `<option value="${item.codigo}">${item.codigo} - ${item.descripcion}</option>`
        );

        this.populateSelect('afectacion_tipo_codigo', '{{ route("afectacion-tipos.select") }}', item =>
            `<option value="${item.codigo}">${item.codigo} - ${item.descripcion}</option>`
        );
        this.populateSelect('linea_id', '{{ route("lineas.select") }}', item =>
            `<option value="${item.id}">${item.nombre}</option>`
        );
        this.setupLiveSearchSelect({
            inputId: 'unidad_descripcion',
            hiddenId: 'unidad_codigo_d',
            url: "{{ route('unidades.buscar') }}",
            template: (item) => {
                return  `${item.codigo} - ${item.descripcion}`;
            },
            getId: item => item.codigo,
            onSelect: (item) => this.addToTable(item)
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
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'unidad', name: 'unidad', orderable: false },
                { data: 'linea', name: 'linea', orderable: false },
                { data: 'nombre', name: 'nombre' },
                { data: 'empaque', name: 'empaque' },
                { data: 'stock_almacen', name: 'stock_almacen', className: 'text-end' },
                { data: 'costo_unitario', name: 'costo_unitario', className: 'text-end' },
                { data: 'activo', name: 'activo', orderable: false, searchable: false, className: 'text-center' }

            ],
            columnDefs: [
                { targets: 0, width: '10%', className: 'text-center' },
                { targets: 1, width: '10%' },
                { targets: 2, width: '10%' },
                { targets: 3, width: '20%' },
                { targets: 4, width: '10%' },
                { targets: 5, width: '10%' },
                { targets: 6, width: '10%' },
                { targets: 7, width: '10%' }
            ],
            responsive: true,
            order: [[3, 'asc']]
        });
    }

    async showEditModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);
            
            this.isEditing = true;
            this.resetForm();
            
            this.elements.modalTitle.textContent = 'Editar Producto: '+ response.nombre;
            this.elements.methodField.value = 'PUT';
            
            // Llenar campos específicos
            document.getElementById('unidad_codigo').value = response.unidad_codigo || 'NIU';
            document.getElementById('afectacion_tipo_codigo').value = response.afectacion_tipo_codigo || '10';
            document.getElementById('linea_id').value = response.linea_id || '';
            document.getElementById('codigo').value = response.codigo || '';
            document.getElementById('nombre').value = response.nombre || '';
            document.getElementById('empaque').value = response.empaque || '';
            document.getElementById('descripcion').value = response.descripcion || '';
            document.getElementById('costo_unitario').value = response.costo_unitario || 0;
            document.getElementById('stock_almacen').value = response.stock_almacen || 0;
            document.getElementById('stock_minimo').value = response.stock_minimo || 0;
            document.getElementById('activo').checked = response.activo ? true : false;

            if (response.imagen && response.imagen !== "") {
                document.getElementById('imagen_producto').src = "{{ asset('uploads/productos') }}/" + response.imagen;
                document.getElementById('imagen_producto').style.display = 'block';
            } else {
                document.getElementById('imagen_producto').style.display = 'none';
            }

            // Llenar tabla de detalles (productos)
            this.updateDetailsTable(response.fracciones);

            this.form.action = `${this.baseUrl}/${id}`;
            
            this.modal.show();
            
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error('Error al cargar datos:', error);
        }
    }
    updateDetailsTable(fracciones=[]){
        const tbody = document.querySelector('#tablaDetalles tbody');
        tbody.innerHTML = ''; 
        fracciones.forEach(detalle => {
            this.addToTable(detalle);
        });
    }

    addToTable(item) {
        //console.log('Agregando producto:', item);
        
        const tbody = document.querySelector('#tablaDetalles tbody');
        document.getElementById('unidad_codigo_d').value = '';
        document.getElementById('unidad_descripcion').value = '';

        const empaque = parseFloat(item.empaque) || 0;
        const precioLista = parseFloat(item.precio_lista) || 0;

        // Verificar si el producto ya existe en la tabla
        const existingRow = [...tbody.querySelectorAll('tr')].find(row => row.dataset.unidadId == item.codigo);

        if (existingRow) {
            this.showNotification('warning', 'La unidad ya está en la lista');

        } else {
            const rowCount = tbody.rows.length + 1;

            const tr = document.createElement('tr');
            tr.dataset.unidadId = item.codigo;

            tr.innerHTML = `
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm btnEliminarFila">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
                <td class="text-center">${rowCount}</td>
                <td>${item.unidad_codigo || item.codigo} (${item.unidad?.descripcion || item.descripcion})</td>
                <td>
                    <input type="number" name="detalles[${rowCount}][empaque]" value="${empaque}" min="1" class="form-control form-control-sm inputEmpaque">
                </td>
                <td class="text-end">
                    <input type="number" name="detalles[${rowCount}][precio_lista]" value="${precioLista}" min="0" step="0.01" class="form-control form-control-sm inputPrecioLista">
                </td>              

                <!-- Solo estos inputs viajan al backend -->
                <input type="hidden" name="detalles[${rowCount}][unidad_codigo_det]" value="${item.unidad_codigo || item.codigo}">
            `;

            // Eventos
            tr.querySelector('.btnEliminarFila').addEventListener('click', () => {
                tr.remove();
            });

            tbody.appendChild(tr);
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
        this.elements.modalTitle.textContent = 'Nuevo Producto';

        document.querySelector('#tablaDetalles tbody').innerHTML = '';
        document.getElementById('unidad_codigo').value = 'NIU';
        document.getElementById('afectacion_tipo_codigo').value = '10';
        document.getElementById('imagen_producto').style.display = 'none';
        document.getElementById('linea_id').value = '1';
        document.getElementById('empaque').value = '1';
        document.getElementById('stock_almacen').value = '0';
        document.getElementById('costo_unitario').value = '0';
        document.getElementById('stock_minimo').value = '10';
        document.getElementById('activo').checked = true;
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new ProductoManager();
    document.body.addEventListener('click', function(e) {
        if (e.target && (e.target.matches('.btn-view-producto') || e.target.closest('.btn-view-producto'))) {
            const button = e.target.closest('.btn-view-producto');
            const productoId = button.getAttribute('data-id');
            if (!productoId) return;

            //const url = `{{ url('productos') }}/${productoId}/ver`;
            const url = "{{ route('productos.ver', ':id') }}".replace(':id', productoId);
            
            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('No se pudo cargar el registro');
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
document.getElementById('mnuCatalogo').classList.add('menu-open');
document.getElementById('itemProductos').classList.add('active');
</script>
@endpush