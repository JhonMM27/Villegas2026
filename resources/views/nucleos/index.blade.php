@extends('plantilla.app')
@push('estilos')

@endpush
@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Núcleos</h3>
                    @can('nucleos_create')
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
                                    <th>Descripción</th>
                                    <th>Unidad</th>
                                    <th>Empaque</th>
                                    <th>Cant. Porcentaje</th>
                                    <th>Items</th>
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
@canany(['nucleos_create', 'nucleos_edit'])
    @include('nucleos.action')
@endcanany
<div id="modalContainer"></div>
@endsection
@push('scripts')
<script>
class NucleoManager extends CrudManager {
    constructor() {
        super("{{ url('nucleos') }}");
        this.afterSuccess = this.handleFormulacionSuccess.bind(this);
        this.initializeDataTable();

        this.setupLiveSearchSelect({
            inputId: 'producto_nombre_nucleo',
            hiddenId: 'producto_id_nucleo',
            url: "{{ route('productos.buscar-nucleo') }}",
            template: (item) => {
                return item.id
                    ? `${item.id} - ${item.nombre} (S/ ${item.costo_unitario})`
                    : `${item.nombre} (S/ ${item.costo_unitario})`;
            },
            getId: item => item.id,
            minLength : 1,
            delay : 300,
            onSelect: (item) => this.addNucleo(item)
        });

        this.setupLiveSearchSelect({
            inputId: 'producto_nombre',
            hiddenId: 'producto_id',
            url: "{{ route('productos.buscar-aditivo') }}",
            template: (item) => {
                return item.id
                    ? `${item.id} - ${item.nombre} (S/ ${item.costo_unitario})`
                    : `${item.nombre} (S/ ${item.costo_unitario})`;
            },
            getId: item => item.id,
            minLength : 1,
            delay : 300,
            onSelect: (item) => this.addProductoToTable(item)
        });
    }

    async handleFormulacionSuccess(response, isEditing) {
        // Solo mostrar opción de ticket para compras NUEVAS (no ediciones)
        if (!isEditing && response.nucleo_id) {
            setTimeout(() => {
                this.showTicketOption(response);
            }, 1000); // Esperar 1 segundo para que se vea la notificación de registro primero
        }
    }

    async showTicketOption(response){
        const result = await Swal.fire({
            title: '¡Núcleo registrado!',
            text: '¿Deseas imprimir el núcleo?',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Imprimir Ticket',
            cancelButtonText: 'Continuar',
            reverseButtons: true,
            timer: 8000,
            timerProgressBar: true
        });
        
        if (result.isConfirmed) {
            const imprimirRuta = "{{ route('nucleos.imprimir', ['id' => ':id']) }}";
            window.open(imprimirRuta.replace(':id', response.nucleo_id), '_blank');
        }
    }

    addProductoToTable(item, override = {}) {
        //console.log('Agregando producto:', item);
        const productoNucleo = document.getElementById('producto_id_nucleo')?.value;

        if (!productoNucleo) {
            this.showNotification(
                'warning',
                'Debe seleccionar el producto núcleo antes de agregar detalles'
            );
            return;
        }

        const tbody = document.querySelector('#tablaDetalles tbody');
        document.getElementById('producto_id').value = '';
        document.getElementById('producto_nombre').value = '';

        /*
        // Valores originales (modo nuevo)
        let precioUnit = parseFloat(item.costo_unitario) || 0;

        // Si viene override (modo editar)
        if (override.precio_unitario !== undefined) {
            precioUnit = parseFloat(override.precio_unitario);
        }
        */

        const existingRow = [...tbody.querySelectorAll('tr')].find(row => row.dataset.productoId == item.id);
        if (existingRow) {
            this.showNotification('warning', 'El producto ya está en la lista');
            return;
        }

        const rowCount = tbody.rows.length + 1;
        const tr = document.createElement('tr');
        tr.dataset.productoId = item.id;

        const cantidad = item.cantidad ?? 0;
        const productoActivo = item.activo === undefined
            || item.activo === null
            || Number(item.activo) === 1;
        const estadoProducto = productoActivo
            ? ''
            : ' <span class="badge bg-warning text-dark">Inactivo</span>';

        tr.innerHTML = `
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm btnEliminarFila">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
            <td class="text-center">${rowCount}</td>
            <td>${item.id}</td>
            <td>${item.nombre}${estadoProducto}</td>
            <td class="text-end">
                <select 
                    name="detalles[${rowCount}][unidad_codigo]" 
                    class="form-select form-select-sm inputUnidadCodigo">
                    <option value="KGM" selected>Kg</option>
                </select>
            </td>

            <td>
                <input type="number" 
                    name="detalles[${rowCount}][cantidad]" 
                    value="${cantidad}"
                    step="any" 
                    class="form-control form-control-sm inputCantidad">
            </td>
            <input type="hidden" name="detalles[${rowCount}][producto_id]" value="${item.id}">
        `;

        tbody.appendChild(tr);

        const inputCantidad = tr.querySelector('.inputCantidad');

        inputCantidad.addEventListener('input', () => {
            this.calculateTotals();
        });

        tr.querySelector('.btnEliminarFila').addEventListener('click', () => {
            tr.remove();
            this.calculateTotals();
        });

        tr.querySelectorAll('input[type="number"]').forEach(input => this.setupInputErrorClear(input));
    }

    calculateTotals() {
        const tbody = document.querySelector('#tablaDetalles tbody');
        if (!tbody) return;

        let cantidad_porcentaje = 0;

        [...tbody.querySelectorAll('tr')].forEach(row => {
            const cantidad = parseFloat(row.querySelector('.inputCantidad').value) || 0;

            //total_salida_saco += salidaSaco;
            cantidad_porcentaje += cantidad;
        });

        document.getElementById('cantidad_porcentaje').value = cantidad_porcentaje.toFixed(4);
    }


    //Agregamos producto a lo input de preparada
    addNucleo(item) {
        //console.log('Agregando producto:', item);

        const productoActivo = item.activo === undefined
            || item.activo === null
            || Number(item.activo) === 1;
        const estadoProducto = productoActivo ? '' : ' [INACTIVO]';

        document.getElementById('producto_id_nucleo').value = item.id;
        document.getElementById('producto_nombre_nucleo').value = `(${item.id}) ${item.nombre}${estadoProducto}`;
        document.getElementById('producto_empaque').value = item.empaque;
        document.getElementById('producto_empaque_text').textContent = item.empaque;
        document.getElementById('linea').textContent = item.linea.nombre;
        //this.initIngresoEventos();
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
                { data: 'id', name: 'id', orderable: false, searchable: false },
                { data: 'nombre', name: 'nombre', orderable: false, searchable: false },
                { data: 'unidad_nombre', name: 'unidad_nombre', orderable: false, searchable: false },
                { data: 'empaque', name: 'empaque', orderable: false, searchable: false },
                { data: 'cantidad_porcentaje', name: 'cantidad_porcentaje', orderable: false, searchable: false },
                { data: 'items', name: 'items', orderable: false, searchable: false },
                { data: 'activo', name: 'activo', orderable: true  }
            ],
            columnDefs: [
                { targets: 0, width: '10%', className: 'text-center' },
                { targets: 1, width: '10%' },
                { targets: 2, width: '30%' },
                { targets: 3, width: '10%' },
                { targets: 4, width: '10%' },
                { targets: 5, width: '10%' },
                { targets: 6, width: '10%' },
                { targets: 7, width: '10%' },
            ],
            responsive: true,
            order: [[1, 'asc']]
        });
    }

    showCreateModal(){
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nuevo Núcleo';

        document.querySelector('#tablaDetalles tbody').innerHTML = '';
        document.getElementById('activo').checked = true;
        document.getElementById('producto_empaque_text').textContent = '';
        document.getElementById('linea').textContent = '';
    }

    async showEditModal(id) {
        this.resetForm();
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);
            
            this.isEditing = true;
            
            
            this.elements.modalTitle.textContent = 'Editar Núcleo: '+ response.id+ ' '+response.nombre;
            this.elements.methodField.value = 'PUT';
            
            // Llenar campos específicos

            const item = {
                id: response.id,
                nombre: response.nombre,
                empaque: response.empaque,
                activo: response.producto?.activo,
                linea: {
                    nombre: 'NUCLEO'
                }
            };

            this.addNucleo(item)

            // Llenar tabla de detalles (productos)
            
            this.updateDetailsTable(response.detalles);

            // Llenar totales
            document.getElementById('cantidad_porcentaje').value = parseFloat(response.cantidad_porcentaje).toFixed(4);
            document.getElementById('activo').checked = Number(response.activo) === 1;

            this.form.action = `${this.baseUrl}/${id}`;
            
            this.modal.show();
            
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error('Error al cargar datos:', error);
        }
    }
    
    updateDetailsTable(detalles=[]){
        const tbody = document.querySelector('#tablaDetalles tbody');
        tbody.innerHTML = '';

        detalles.forEach(detalle => {
            const item = {
                id: detalle.producto_id,
                nombre: detalle.producto_nombre,
                empaque: detalle.producto_empaque,
                cantidad: detalle.cantidad,
                activo: detalle.producto?.activo
            };

            this.addProductoToTable(item);
        });
    }

    focusFirstField() {
        document.getElementById('producto_nombre_nucleo').focus();
        const modalEl = this.modal._element;

        modalEl.addEventListener('shown.bs.modal', () => {
            const input = document.getElementById('producto_nombre_nucleo');
            if (input) input.focus();
        }, { once: true });
    }

    async handleFormErrors(error) {
        if (error.status === 422 && error.data && error.data.errors) {
            const errors = error.data.errors;
            
            // Limpiar errores anteriores
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            
            Object.keys(errors).forEach(campo => {
                // Detectar si es un error de detalle de tabla
                // Ejemplo: "detalles.1.precio_unitario_servicio"
                const matchDetalle = campo.match(/^detalles\.(\d+)\.(.+)$/);
                
                if (matchDetalle) {
                    const indice = matchDetalle[1];
                    const nombreCampo = matchDetalle[2];
                    
                    // Buscar el input específico en la tabla
                    const input = document.querySelector(`input[name="detalles[${indice}][${nombreCampo}]"]`);
                    
                    if (input) {
                        input.classList.add('is-invalid');
                        
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'invalid-feedback d-block';
                        errorDiv.style.cssText = 'font-size: 0.875rem; margin-top: 0.25rem;';
                        errorDiv.textContent = errors[campo][0];
                        
                        input.parentElement.appendChild(errorDiv);
                        
                        // Scroll al primer error
                        if (Object.keys(errors)[0] === campo) {
                            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                } else {
                    // Errores de campos normales
                    const input = document.querySelector(`[name="${campo}"]`);
                    
                    if (input) {
                        input.classList.add('is-invalid');
                        
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'invalid-feedback d-block';
                        errorDiv.textContent = errors[campo][0];
                        
                        input.parentElement.appendChild(errorDiv);
                    }
                }
            });
            
            // Mostrar resumen en SweetAlert
            let mensajeHTML = '<ul class="text-start mb-0">';
            Object.keys(errors).forEach(campo => {
                errors[campo].forEach(mensaje => {
                    mensajeHTML += `<li>${mensaje}</li>`;
                });
            });
            mensajeHTML += '</ul>';
            
            Swal.fire({
                icon: 'error',
                title: 'Errores de validación',
                html: mensajeHTML,
                confirmButtonText: 'Revisar'
            });
            
        } else {
            // Si existe el método padre, llamarlo para otros errores
            if (super.handleFormErrors) {
                super.handleFormErrors(error);
            } else {
                this.showNotification('error', error.data?.message || 'Error al procesar la solicitud');
            }
        }
    }

    // Agregar función para limpiar errores al escribir
    setupInputErrorClear(input) {
        input.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            const errorDiv = this.parentElement.querySelector('.invalid-feedback');
            if (errorDiv) {
                errorDiv.remove();
            }
        });
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new NucleoManager();
    document.body.addEventListener('click', function(e) {
        if (e.target && (e.target.matches('.btn-view-nucleo') || e.target.closest('.btn-view-nucleo'))) {
            const button = e.target.closest('.btn-view-nucleo');
            const nucleoId = button.getAttribute('data-id');
            if (!nucleoId) return;
            
            const url = "{{ route('nucleos.ver', ':id') }}".replace(':id', nucleoId);

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

document.getElementById('mnuNucleo').classList.add('menu-open');
document.getElementById('itemNucleos').classList.add('active');
</script>
<script src="{{asset('js/detallesFlecha.js')}}"></script>
@endpush
