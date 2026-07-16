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
                    <h3 class="card-title flex-grow-1">Formulaciones</h3>
                    @can('formulaciones_create')
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
                                    <th>Usuario</th>
                                    <th>Fecha</th>
                                    <th>Item</th>
                                    <th>Total Kg</th>
                                    <th>Formulación</th>                         
                                    <th>Cliente</th>
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
@canany(['formulaciones_create', 'formulaciones_edit'])
    @include('formulaciones.action')
@endcanany
<div id="modalContainer"></div>
@endsection
@push('scripts')
<script>
class FormulacionManager extends CrudManager {
    constructor() {
        super("{{ url('formulaciones') }}");
        this.afterSuccess = this.handleFormulacionSuccess.bind(this);
        this.initializeDataTable();

        this.populateSelect('documento_tipo_codigo', '{{ route("documento-tipos.select") }}', item =>
            `<option value="${item.codigo}">${item.codigo} - ${item.descripcion}</option>`
        );

        this.setupLiveSearchSelect({
            inputId: 'producto_nombre_preparada',
            hiddenId: 'producto_id_preparada',
            url: "{{ route('productos.buscar-formulacion-preparada') }}",
            template: (item) => {
                return item.id
                    ? `${item.id} - ${item.nombre} (S/ ${item.costo_unitario})`
                    : `${item.nombre} (S/ ${item.costo_unitario})`;
            },
            getId: item => item.id,
            minLength : 1,
            delay : 300,
            onSelect: (item) => this.addPreparada(item)
        });

        this.setupLiveSearchSelect({
            inputId: 'producto_nombre',
            hiddenId: 'producto_id',
            url: "{{ route('productos.buscar-formulacion') }}",
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

        this.setupLiveSearchSelect({
            inputId: 'cliente_razon_social',
            hiddenId: 'cliente_id',
            url: "{{ route('clientes.buscar') }}",
            template: (item) => {
                return item.documento_numero
                    ? `${item.documento_numero} - ${item.razon_social}`
                    : item.razon_social;
            }
        });

        document.getElementById('btnRegistrarCliente').addEventListener('click', () => this.registerSupplier());
    }

    async handleFormulacionSuccess(response, isEditing) {
        // Solo mostrar opción de ticket para compras NUEVAS (no ediciones)
        if (!isEditing && response.formulacion_id) {
            setTimeout(() => {
                this.showTicketOption(response);
            }, 1000); // Esperar 1 segundo para que se vea la notificación de registro primero
        }
    }

    async showTicketOption(response){
        const result = await Swal.fire({
            title: '¡Formulación registrada!',
            text: '¿Deseas imprimir la formulación?',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Imprimir Ticket',
            cancelButtonText: 'Continuar',
            reverseButtons: true,
            timer: 8000,
            timerProgressBar: true
        });
        
        if (result.isConfirmed) {
            const imprimirRuta = "{{ route('formulaciones.imprimir', ['id' => ':id']) }}";
            window.open(imprimirRuta.replace(':id', response.formulacion_id), '_blank');
        }
    }

    addProductoToTable(item, override = {}) {
        //console.log('Agregando producto:', item);
        const productoPreparada = document.getElementById('producto_id_preparada')?.value;

        if (!productoPreparada) {
            this.showNotification(
                'warning',
                'Debe seleccionar el producto preparado antes de agregar detalles'
            );
            return;
        }

        const tbody = document.querySelector('#tablaDetalles tbody');
        document.getElementById('producto_id').value = '';
        document.getElementById('producto_nombre').value = '';

        const existingRow = [...tbody.querySelectorAll('tr')].find(row => row.dataset.productoId == item.id);
        if (existingRow) {
            this.showNotification('warning', 'El producto ya está en la lista');
            return;
        }

        const rowCount = tbody.rows.length + 1;
        const tr = document.createElement('tr');
        tr.dataset.productoId = item.id;
        tr.dataset.empaque = item.empaque || 1;

        const salida_saco = override.salida_saco ?? 0;
        const salida_kg = override.salida_kg ?? 0;

        tr.innerHTML = `
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm btnEliminarFila">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
            <td class="text-center">${rowCount}</td>
            <td>${item.id}</td>
            <td>${item.nombre}</td>
            <td>${item.empaque}</td>

            <td>
                <input type="number" 
                    name="detalles[${rowCount}][salida_saco]" 
                    value="${salida_saco}" 
                    step="any"
                    class="form-control form-control-sm inputSalidaSaco" readonly>
            </td>

            <td>
                <input type="number" 
                    name="detalles[${rowCount}][salida_kg]" 
                    value="${salida_kg}"
                    step="any"
                    class="form-control form-control-sm inputSalidaKg">
            </td>

            <td>${item.linea?.nombre || ''}</td>

            <input type="hidden" name="detalles[${rowCount}][producto_id]" value="${item.id}">
        `;

        tbody.appendChild(tr);

        // Vincular eventos de sincronización
        const inputSaco = tr.querySelector('.inputSalidaSaco');
        const inputKg = tr.querySelector('.inputSalidaKg');
        const empaque = parseFloat(tr.dataset.empaque || 1);

        /*inputSaco.addEventListener('input', (e) => {
            const v = parseFloat(e.target.value) || 0;
            inputKg.value = (v * empaque).toFixed(2);
            this.calculateTotals();
        });*/

        inputKg.addEventListener('input', (e) => {
            const v = parseFloat(e.target.value) || 0;
            inputSaco.value = empaque > 0 ? (v / empaque).toFixed(2) : 0;
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

        let total_salida_kg = 0;
        let total_salida_saco = 0;

        [...tbody.querySelectorAll('tr')].forEach(row => {
            const salidaKg = parseFloat(row.querySelector('.inputSalidaKg').value) || 0;
            const empaque = parseFloat(row.dataset.empaque || 1);

            // ✅ calcular SACOS desde KG
            const salidaSaco = empaque > 0 ? salidaKg / empaque : 0;
            row.querySelector('.inputSalidaSaco').value = salidaSaco.toFixed(2);

            //total_salida_saco += salidaSaco;
            total_salida_kg += salidaKg;
        });
        const productoEmpaque = parseFloat(document.getElementById('producto_empaque')?.value) || 0;

        total_salida_saco = Number((productoEmpaque > 0
            ? total_salida_kg / productoEmpaque
            : 0).toFixed(2));

        document.getElementById('total_salida_saco').value = total_salida_saco.toFixed(2);
        document.getElementById('total_salida_kg').value = total_salida_kg.toFixed(2);
    }


    //Agregamos producto a lo input de preparada
    addPreparada(item) {
        //console.log('Agregando producto:', item);
        
        document.getElementById('producto_id_preparada').value = item.id;
        document.getElementById('producto_nombre_preparada').value = `(${item.id}) ${item.nombre}`;
        document.getElementById('producto_empaque').value = item.empaque;
        document.getElementById('producto_empaque_text').textContent = item.empaque;
        document.getElementById('linea').textContent = item.linea.nombre;
        this.initIngresoEventos();
    }

    initIngresoEventos() {
        const sacoEl = document.getElementById('ingreso_saco');
        const kgEl = document.getElementById('ingreso_kg');
    }

    initializeDataTable() {
        this.tabla = $(this.elements.table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: this.baseUrl,
                type: 'GET',
                error: function(xhr, status, error) {
                    // Imprime el error en consola
                    console.log('Error DataTable AJAX:', xhr);
                    if(xhr.responseJSON && xhr.responseJSON.message){
                        console.log('Mensaje Laravel:', xhr.responseJSON.message);
                    } else {
                        console.log('Respuesta cruda:', xhr.responseText);
                    }
                }
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false},
                { data: 'id', name: 'id', orderable: false, searchable: false },
                { data: 'usuario', name: 'usuario', orderable: false, searchable: false },
                { data: 'fecha', name: 'fecha' },
                { data: 'item', name: 'item', orderable: false, searchable: false },
                { data: 'salida_kg', name: 'salida_kg', orderable: false, searchable: false },
                { data: 'producto_nombre', name: 'producto_nombre', orderable: true },
                { data: 'cliente_nombre', name: 'cliente_nombre', orderable: true },
                { data: 'activo', name: 'activo', orderable: true  }
            ],
            columnDefs: [
                { targets: 0, width: '10%', className: 'text-center' },
                { targets: 1, width: '10%' },
                { targets: 2, width: '10%' },
                { targets: 3, width: '10%' },
                { targets: 4, width: '10%' },
                { targets: 5, width: '10%' },
                { targets: 6, width: '10%' },
                { targets: 7, width: '15%' },
                { targets: 8, width: '15%' },
            ],
            order: [[1, 'asc']], // Ordenar por ID por defecto
            responsive: true
        });
    }

    showCreateModal(){
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nueva Formulación';

        document.querySelector('#tablaDetalles tbody').innerHTML = '';
        document.getElementById('documento_tipo_codigo').value = '01';
        document.getElementById('producto_empaque_text').textContent = '';
        document.getElementById('linea').textContent = '';

        this.setFieldValue('fecha', this.obtenerFechaHoraActual());
        const usuarioNombre = @json(auth()->user()->name);
        document.getElementById('usuario_nombre').textContent = usuarioNombre;
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

    async showEditModal(id) {
        this.resetForm();
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);
            
            this.isEditing = true;
            
            
            this.elements.modalTitle.textContent = 'Editar Formulación: '+ response.id+ ' '+response.producto_nombre;
            this.elements.methodField.value = 'PUT';
            
            // Llenar campos específicos
            // Llenar campos principales del modal
            //console.log(response);
            this.setFieldValue('fecha', this.formatDateTimeLocal(response.fecha));
            const item = {
                id: response.producto_id,
                nombre: response.producto_nombre,
                empaque: response.producto_empaque,
                linea: {
                    nombre: response.producto_linea
                }
            };
            this.addPreparada(item)

            document.getElementById('cliente_id').value = response.cliente_id || '';
            document.getElementById('cliente_razon_social').value = response.cliente_nombre || '';

            // Llenar tabla de detalles (productos)
            
            this.updateDetailsTable(response.detalles);

            // Llenar totales
            document.getElementById('total_salida_saco').value = parseFloat(response.salida_kg/response.producto_empaque).toFixed(2);
            document.getElementById('total_salida_kg').value = parseFloat(response.salida_kg).toFixed(2);

            this.form.action = `${this.baseUrl}/${id}`;
            
            this.modal.show();
            const usuarioNombre = @json(auth()->user()->name);
            document.getElementById('usuario_nombre').textContent = usuarioNombre;
            
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
                costo_unitario: detalle.precio_unitario,
                linea: { nombre: detalle.producto_linea }
            };

            this.addProductoToTable(item, {
                salida_saco: Number((detalle.salida_kg / item.empaque).toFixed(2)),
                salida_kg: detalle.salida_kg
            });
        });
    }

    focusFirstField() {
        document.getElementById('producto_nombre_preparada').focus();
        const modalEl = this.modal._element;

        modalEl.addEventListener('shown.bs.modal', () => {
            const input = document.getElementById('producto_nombre_preparada');
            if (input) input.focus();
        }, { once: true });
    }
    async registerSupplier() {
        // Recoge los datos del formulario
        const documento_tipo_codigo = document.getElementById('documento_tipo_codigo').value;
        const documento_numero = document.getElementById('documento_numero').value;
        const razon_social = document.getElementById('razon_social').value;

        if (!documento_tipo_codigo || !documento_numero || !razon_social) {
            this.showNotification('warning', 'Completa todos los campos obligatorios de cliente');
            return;
        }
        try {
            const url = "{{ route('clientes.store') }}";
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    documento_tipo_codigo,
                    documento_numero,
                    razon_social
                })
            });

            const data = await response.json();

            if (response.ok && data.success && data.cliente) {
                // Asigna los datos al formulario principal
                document.getElementById('cliente_id').value = data.cliente.id;
                document.getElementById('cliente_razon_social').value = data.cliente.razon_social;

                // Cambia a la tab de "Buscar Cliente"
                new bootstrap.Tab(document.getElementById('nav-buscar-tab')).show();
                this.showNotification('success', 'cliente registrado correctamente');
                document.getElementById('documento_numero').value = '';
                document.getElementById('razon_social').value = '';
            } 
            else if (response.status === 422) {
                this.handleFormErrors({ status: 422, data }); 
            } else {
                this.showNotification('error', data.message || 'Error al registrar cliente');
            }
        } catch (error) {
            this.showNotification('error', 'Error de red al registrar cliente');
            console.error(error);
        }
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
    new FormulacionManager();
    document.body.addEventListener('click', function(e) {
        if (e.target && (e.target.matches('.btn-view-formulacion') || e.target.closest('.btn-view-formulacion'))) {
            const button = e.target.closest('.btn-view-formulacion');
            const formulacionId = button.getAttribute('data-id');
            if (!formulacionId) return;
            
            const url = "{{ route('formulaciones.ver', ':id') }}".replace(':id', formulacionId);

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

document.getElementById('mnuProduccion').classList.add('menu-open');
document.getElementById('itemFormulaciones').classList.add('active');
</script>
<script src="{{asset('js/detallesFlecha.js')}}"></script>
@endpush