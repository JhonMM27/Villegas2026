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
                    <h3 class="card-title flex-grow-1">Preparadas</h3>
                    @can('preparadas_create')
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
                                    <th>N° Interno</th>
                                    <th>Items</th>                                    
                                    <th>Total Kg</th>
                                    <th>Total Sacos</th> 
                                    <th>Total Soles</th>                                                                      
                                    <th>Preparada</th>
                                    <th>Formulación</th>                         
                                    <th>Cliente</th>
                                    <th>Estado</th>
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
@canany(['preparadas_create', 'preparadas_edit'])
    @include('preparadas.action')
@endcanany
<div id="modalContainer"></div>
@endsection
@push('scripts')
<script>
class PreparadaManager extends CrudManager {
    constructor() {
        super("{{ url('preparadas') }}");
        this.afterSuccess = this.handlePreparadaSuccess.bind(this);
        this.config = {};
        this.initializeDataTable();
        this.fetchConfiguraciones();

        document.addEventListener('click', (e) => {
            if (e.target && e.target.id === 'btnProcesar') {
                this.procesarProporcion();
            }
        });

        this.setupLiveSearchSelect({
            inputId: 'formulacion_nombre',
            hiddenId: 'formulacion_id',
            url: "{{ route('formulaciones.buscar') }}",
            template: (item) => {
                return item.id
                    ? `${item.id} - ${item.producto_nombre} (${item.producto_empaque})`
                    : `${item.producto_nombre} (${item.producto_empaque})`;
            },
            getId: item => item.id,
            minLength : 1,
            delay : 300,
            onSelect: (item) => this.addFormulacion(item)
        });
    }

    async fetchConfiguraciones() {
        try {
            const res = await fetch('/configuraciones/json');
            this.config = await res.json();
        } catch (e) {
            console.warn('No se pudo cargar configuraciones, usando valores por defecto');
            this.config = {};
        }
    }

    async handlePreparadaSuccess(response, isEditing) {
        // Solo mostrar opción de ticket para compras NUEVAS (no ediciones)
        if (!isEditing && response.preparada_id) {
            setTimeout(() => {
                this.showTicketOption(response);
            }, 1000); // Esperar 1 segundo para que se vea la notificación de registro primero
        }
    }

    async showTicketOption(response){
        const result = await Swal.fire({
            title: '¡Preparación registrada!',
            text: '¿Deseas imprimir la preparación?',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Imprimir Ticket',
            cancelButtonText: 'Continuar',
            reverseButtons: true,
            timer: 8000,
            timerProgressBar: true
        });
        
        if (result.isConfirmed) {
            const imprimirRuta = "{{ route('preparadas.imprimir', ['id' => ':id']) }}";
            window.open(imprimirRuta.replace(':id', response.preparada_id), '_blank');
        }
    }

    //Agregamos producto a lo input de preparada
    addFormulacion(item) {
        const info = document.getElementById('info_formulacion');
        info.classList.remove('d-none');
        
        const tbody = document.querySelector('#tablaFormulaciones tbody');
        tbody.innerHTML = '';

        let totalSalidaKg = 0;
        //let totalSalidaSoles = 0;

        const ingresoKgTotal = parseFloat(item.ingreso_kg || 0);
        const empaque = parseFloat(item.producto_empaque || 1);

        // 1️⃣ Totales
        item.detalles.forEach(det => {
            totalSalidaKg += parseFloat(det.salida_kg || 0);
            //totalSalidaSoles += parseFloat(det.salida_soles || 0);
        });

        // 2️⃣ Render
        item.detalles.forEach(det => {
            const salidaKg = parseFloat(det.salida_kg || 0);
            //const salidaSoles = parseFloat(det.salida_soles || 0);
            //const precioUnitario = parseFloat(det.precio_unitario || 0);

            const porcentajePreparada = ingresoKgTotal > 0
                ? ((salidaKg / ingresoKgTotal) * 100).toFixed(4)
                : '0.00';

            const porcentajeProporcion = totalSalidaKg > 0
                ? ((salidaKg / totalSalidaKg) * 100).toFixed(4)
                : '0.00';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center">${det.producto_id}</td>
                <td>${det.producto_nombre.trim()}</td>
                <td class="text-end">${det.producto_empaque}</td>
                <td class="text-end">${det.producto.costo_unitario}</td>
                <td class="text-end">${salidaKg.toFixed(4)}</td>
                <td class="text-center">${det.producto_linea.trim()}</td>
                <td class="text-end">${porcentajeProporcion} %</td>
            `;
            tbody.appendChild(tr);
        });
        /*
        document.getElementById('total_salida_saco').textContent  =
            parseFloat(item.salida_saco || 0).toFixed(2);
        */
        document.getElementById('total_salida_kg').textContent  =
            totalSalidaKg.toFixed(4);
        /*
        document.getElementById('total_salida_soles').textContent  =
            totalSalidaSoles.toFixed(2);
        */
        document.getElementById('producto_empaque_text').textContent  =item.producto_empaque || '0';
        document.getElementById('cliente_nombre').textContent  =item.cliente_nombre || '';
        //document.getElementById('costo_unitario_text').textContent  =item.costo_unitario || '';
        document.getElementById('unidad_text').textContent  ='SACO' || '';
    }
    

    //Recalculamos la tabla.
    procesarProporcion() {
        const tbodyOrigen  = document.querySelector('#tablaFormulaciones tbody');
        const tbodyDestino = document.querySelector('#tablaDetalles tbody');

        if (!tbodyOrigen || tbodyOrigen.rows.length === 0) {
            this.showNotification('error', 'Primero seleccione una formulación');
            return;
        }

        const proporcion = parseFloat(document.getElementById('proporcion')?.value || 0);
        if (proporcion <= 0) {
            this.showNotification('error', 'Ingrese una proporción válida');
            return;
        }

        // const costoServicio = parseFloat(
        //     document.getElementById('costo_servicio')?.value || 0
        // );

        
        tbodyDestino.innerHTML = '';

        const totalBaseKg = parseFloat(
            document.querySelector('#tablaFormulaciones #total_salida_kg')?.textContent || 0
        );

        if (totalBaseKg <= 0) {
            this.showNotification('error', 'No hay kilos base para procesar');
            return;
        }

        const factor = proporcion / totalBaseKg;

        let totalSalidaKg    = 0;
        let totalSalidaSaco  = 0;
        let totalSalidaSoles = 0;

        // =========================================
        // DETALLES PROPORCIONALES
        // =========================================
        [...tbodyOrigen.rows].forEach((row, index) => {

            const productoId     = row.cells[0].textContent.trim();
            const productoNombre = row.cells[1].textContent.trim();
            const empaque        = parseFloat(row.cells[2].textContent || 0);
            const precio         = parseFloat(row.cells[3].textContent || 0);
            const salidaKgBase   = parseFloat(row.cells[4].textContent || 0);
            const linea          = row.cells[5].textContent.trim();

            const salidaKg    = salidaKgBase    * factor;            
            const salidaSaco  = Number((empaque > 0 ? salidaKg / empaque : 0).toFixed(4));

            const salidaSoles = Number((salidaSaco * precio).toFixed(4));

            totalSalidaKg    += salidaKg;
            totalSalidaSaco  += salidaSaco;
            totalSalidaSoles += salidaSoles;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${productoId}
                    <input type="hidden" name="detalles[${index}][producto_id]" value="${productoId}">
                </td>

                <td>${productoNombre}</td>

                <td>${empaque}</td>

                <td>${precio.toFixed(4)}
                    <input type="hidden" name="detalles[${index}][precio_unitario]" value="${precio.toFixed(4)}">
                </td>

                <td>${salidaSaco.toFixed(4)}
                    <input type="hidden" name="detalles[${index}][salida_saco]" value="${salidaSaco.toFixed(4)}">
                </td>

                <td>${salidaKg.toFixed(4)}
                    <input type="hidden" name="detalles[${index}][salida_kg]" value="${salidaKg.toFixed(4)}">
                </td>

                <td>${salidaSoles.toFixed(4)}
                    <input type="hidden" name="detalles[${index}][salida_soles]" value="${salidaSoles.toFixed(4)}">
                </td>

                <td>${linea}</td>
            `;

            tbodyDestino.appendChild(tr);
        });

        // =========================================
        // SERVICIO MEZCLADO
        // =========================================
        const costoServicioCalculado = Number((totalSalidaKg * (parseFloat(this.config.costo_servicio_mezclado_preparadas) || 0.07)).toFixed(2));
        const inputServicio = document.getElementById('costo_servicio');
        if (inputServicio) {
            inputServicio.value = costoServicioCalculado;
        }

        const costoServicio = costoServicioCalculado;

        if (costoServicio > 0) {
            const idx = tbodyDestino.rows.length;

            tbodyDestino.insertAdjacentHTML('beforeend', `
                <tr>
                    <td>77</td>
                    <td>SERVICIO MEZCLADO</td>
                    <td>1</td>
                    <td>${costoServicio.toFixed(2)}</td>
                    <td>0.00</td>
                    <td>0.00</td>
                    <td>${costoServicio.toFixed(2)}</td>
                    <td>SERVICIO</td>

                    <input type="hidden" name="detalles[${idx}][producto_id]" value="77">
                    <input type="hidden" name="detalles[${idx}][precio_unitario]" value="${costoServicio.toFixed(2)}">
                    <input type="hidden" name="detalles[${idx}][salida_soles]" value="${costoServicio.toFixed(2)}">
                    <input type="hidden" name="detalles[${idx}][salida_saco]" value="0.00">
                    <input type="hidden" name="detalles[${idx}][salida_kg]" value="0.00">
                </tr>
            `);

            totalSalidaSoles += costoServicio;
        }

        // =========================================
        // TOTALES
        // =========================================
        const empaque = parseFloat(
            document.getElementById('producto_empaque_text')?.textContent || 1
        );
        
        document.querySelector('#tablaDetalles #total_salida_saco').value  = (totalSalidaKg /empaque).toFixed(4);
        document.querySelector('#tablaDetalles #kilos_saco').value  = (empaque).toFixed(4);
        document.querySelector('#tablaDetalles #costo_saco').value  = (totalSalidaSoles/(totalSalidaKg /empaque)).toFixed(4);

        //document.querySelector('#tablaDetalles #total_salida_kg').value    = totalSalidaKg.toFixed(2);
        document.querySelector('#tablaDetalles #costo_total').value = totalSalidaSoles.toFixed(4);

        // INGRESO = PRODUCTO FINAL
        
        let totalSacos = empaque > 0
            ? totalSalidaKg / empaque
            : 0;

        document.querySelector('#tablaDetalles #total_ingreso_saco').value =
            totalSacos.toFixed(4);

        document.querySelector('#tablaDetalles #total_ingreso_kg').value =
            totalSalidaKg.toFixed(4);

        document.querySelector('#tablaDetalles #total_ingreso_soles').value =
            totalSalidaSoles.toFixed(4);

        document.querySelector('#tablaDetalles #costo_unitario_footer').value =
            totalSacos > 0
                ? (totalSalidaSoles / totalSacos).toFixed(4)
                : '0.00';
    }

    addDetalles(detalles) {
        const tbody = document.querySelector('#tablaDetalles tbody');
        tbody.innerHTML = '';
        const tbodyF = document.querySelector('#tablaFormulaciones tbody');
        tbodyF.innerHTML = '';

        detalles.forEach((det, index) => {

            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${det.producto_id}
                    <input type="hidden" name="detalles[${index}][producto_id]" value="${det.producto_id}">
                </td>

                <td>${det.producto_nombre}</td>

                <td>${parseFloat(det.producto_empaque || 1)}</td>

                <td>${parseFloat(det.precio_unitario || 0).toFixed(4)}
                    <input type="hidden" name="detalles[${index}][precio_unitario]" value="${parseFloat(det.precio_unitario || 0).toFixed(4)}">
                </td>

                <td>${parseFloat(det.salida_saco || 0).toFixed(4)}
                    <input type="hidden" name="detalles[${index}][salida_saco]" value="${parseFloat(det.salida_saco || 0).toFixed(4)}">
                </td>

                <td>${parseFloat(det.salida_kg || 0).toFixed(4)}
                    <input type="hidden" name="detalles[${index}][salida_kg]" value="${parseFloat(det.salida_kg || 0).toFixed(4)}">
                </td>

                <td>${parseFloat(det.salida_soles || 0).toFixed(4)}
                    <input type="hidden" name="detalles[${index}][salida_soles]" value="${parseFloat(det.salida_soles || 0).toFixed(4)}">
                </td>

                <td>${det.producto_linea_nombre || ''}</td>
            `;

            tbody.appendChild(tr);
        });
    }


    initIngresoEventos() {
        const empaqueEl = document.getElementById('producto_empaque');
        const costoEl = document.getElementById('costo_unitario');
        const sacoEl = document.getElementById('ingreso_saco');
        const kgEl = document.getElementById('ingreso_kg');
        const solesEl = document.getElementById('ingreso_soles');

        // Evitar duplicar eventos
        sacoEl.oninput = function() {
            const empaque = parseFloat(empaqueEl.value) || 0;
            const costo = parseFloat(costoEl.value) || 0;
            const sacos = parseFloat(this.value) || 0;

            kgEl.value = (sacos * empaque).toFixed(4);
            solesEl.value = (sacos * costo).toFixed(4);
        };

        kgEl.oninput = function() {
            const empaque = parseFloat(empaqueEl.value) || 0;
            const costo = parseFloat(costoEl.value) || 0;
            const kg = parseFloat(this.value) || 0;

            if (empaque > 0) {
                const sacos = kg / empaque;
                sacoEl.value = sacos.toFixed(4);
                solesEl.value = (sacos * costo).toFixed(4);
            }
        };
        // Nuevo: actualizar ingreso_soles cuando cambia costo_unitario
        costoEl.oninput = function() {
            const costo = parseFloat(this.value) || 0;
            const sacos = parseFloat(sacoEl.value) || 0;

            // Solo recalcular ingreso_soles
            solesEl.value = (sacos * costo).toFixed(4);
        };
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
                { data: 'numero_interno', name: 'numero_interno' },
                { data: 'items', name: 'items', orderable: false, searchable: false },                
                { data: 'ingreso_kg', name: 'ingreso_kg', orderable: false, searchable: false, 
                    render: function (data) {
                        return parseFloat(data).toFixed(2);
                    } 
                },
                { data: 'ingreso_saco', name: 'ingreso_saco', orderable: false, searchable: false,
                    render: function (data) {
                        return parseFloat(data).toFixed(2);
                    } 
                 }, 
                { data: 'ingreso_soles', name: 'ingreso_soles', orderable: false, searchable: false,
                    render: function (data) {
                        return parseFloat(data).toFixed(2);
                    } 
                 },                              
                { data: 'producto_nombre', name: 'producto_nombre', orderable: true },
                { data: 'formulacion_id', name: 'formulacion_id', orderable: true },
                { data: 'cliente_nombre', name: 'cliente_nombre', orderable: true },
                { data: 'estado', name: 'estado', orderable: false }
            ],
            columnDefs: [
                { targets: 0,  width: '7%',  className: 'text-center' }, // Acción
                { targets: 1,  width: '4%',  className: 'text-center' }, // ID
                { targets: 2,  width: '8%'  }, // Usuario
                { targets: 3,  width: '8%',  className: 'text-center' }, // Fecha
                { targets: 4,  width: '9%'  }, // Número interno
                { targets: 5,  width: '4%',  className: 'text-end' }, // Ítems
                { targets: 6,  width: '7%',  className: 'text-end' }, // Salida Kg
                { targets: 7,  width: '8%',  className: 'text-end' }, // Salida S/
                { targets: 8,  width: '6%',  className: 'text-end' }, // Salida Saco
                { targets: 9,  width: '17%' }, // Producto
                { targets: 10, width: '5%',  className: 'text-center' }, // Formulación
                { targets: 11, width: '10%' }  // Cliente
            ],
            order: [[1, 'asc']], // Ordenar por ID por defecto
            responsive: true
        });
    }

    showCreateModal(){
        super.showCreateModal();
        document.getElementById('es_rectificacion').value = '0';
        document.getElementById('preparada_anulada_id').value = '';

        document.getElementById('info_formulacion').classList.add('d-none');
        this.elements.modalTitle.textContent = 'Nueva Preparada';

        document.getElementById('total_salida_kg').textContent = '0.00';

        document.querySelector('#tablaDetalles tbody').innerHTML = '';
        document.querySelector('#tablaFormulaciones tbody').innerHTML = '';
        
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
            document.getElementById('es_rectificacion').value = '0';
            document.getElementById('preparada_anulada_id').value = '';

            this.elements.modalTitle.textContent = 'Editar Preparada: '+ response.id+ ' '+response.producto_nombre;
            this.elements.methodField.value = 'PUT';

            this.setFieldValue('fecha', this.formatDateTimeLocal(response.fecha));
            const info = document.getElementById('info_formulacion');
            info.classList.remove('d-none');

            document.getElementById('formulacion_nombre').value = response.producto_nombre || '';
            const inputServicio = document.getElementById('costo_servicio');
            if (inputServicio) {
                inputServicio.value = Number((parseFloat(response.ingreso_kg || 0) * (parseFloat(this.config.costo_servicio_mezclado_preparadas) || 0.07)).toFixed(2));
            }
            document.getElementById('producto_empaque_text').textContent = response.producto_empaque || '';
            //document.getElementById('unidad_text').textContent = response.costo_unitario;
            document.getElementById('cliente_nombre').textContent = response.cliente_nombre|| '';
            document.getElementById('numero_interno').value = response.numero_interno || '';
            document.getElementById('formulacion_id').value = response.formulacion_id || '';
            
            // Llenar tabla de detalles (productos)
            this.addDetalles(response.detalles);

            // Llenar totales
            let salidaSacoNew = response.salida_saco;

            if (response.salida_saco != 0) {
                salidaSacoNew = (
                    response.salida_kg / response.producto_empaque
                ).toFixed(4);
            }

            document.getElementById('total_ingreso_saco').value = parseFloat(response.ingreso_saco);
            document.getElementById('total_ingreso_kg').value = parseFloat(response.ingreso_kg);
            document.getElementById('total_ingreso_soles').value = parseFloat(response.ingreso_soles);
            document.getElementById('kilos_saco').value = response.producto_empaque || '';
            document.getElementById('total_salida_saco').value = response.ingreso_saco || '';
            document.getElementById('costo_saco').value = response.costo_unitario || '';
            document.getElementById('costo_total').value = response.ingreso_soles || '';

            this.form.action = `${this.baseUrl}/${id}`;
            
            this.modal.show();
            
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error('Error al cargar datos:', error);
        }
    }

    async showRectifyModal(id) {
        this.resetForm();
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);

            this.isEditing = false;
            this.isRectifying = true;

            this.elements.modalTitle.textContent = 'Rectificar Preparada: ' + response.id + ' ' + response.producto_nombre;

            this.elements.methodField.value = 'POST';
            this.form.action = this.baseUrl;
            document.getElementById('es_rectificacion').value = '1';
            document.getElementById('preparada_anulada_id').value = id;

            this.setFieldValue('fecha', this.formatDateTimeLocal(response.fecha));
            const info = document.getElementById('info_formulacion');
            info.classList.remove('d-none');

            document.getElementById('formulacion_nombre').value = response.producto_nombre || '';
            const inputServicio = document.getElementById('costo_servicio');
            if (inputServicio) {
                inputServicio.value = Number((parseFloat(response.ingreso_kg || 0) * (parseFloat(this.config.costo_servicio_mezclado_preparadas) || 0.07)).toFixed(2));
            }
            document.getElementById('producto_empaque_text').textContent = response.producto_empaque || '';
            document.getElementById('cliente_nombre').textContent = response.cliente_nombre|| '';
            document.getElementById('numero_interno').value = response.numero_interno || '';
            document.getElementById('formulacion_id').value = response.formulacion_id || '';
            
            // Llenar tabla de detalles (productos)
            this.addDetalles(response.detalles);

            document.getElementById('total_ingreso_saco').value = parseFloat(response.ingreso_saco);
            document.getElementById('total_ingreso_kg').value = parseFloat(response.ingreso_kg);
            document.getElementById('total_ingreso_soles').value = parseFloat(response.ingreso_soles);
            document.getElementById('kilos_saco').value = response.producto_empaque || '';
            document.getElementById('total_salida_saco').value = response.ingreso_saco || '';
            document.getElementById('costo_saco').value = response.costo_unitario || '';
            document.getElementById('costo_total').value = response.ingreso_soles || '';

            this.modal.show();
            
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos para rectificar');
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
                precio_unitario: detalle.precio_unitario,
                salida_saco: detalle.salida_saco,
                salida_kg: detalle.salida_kg,
                ingreso_saco: detalle.ingreso_saco,
                ingreso_kg: detalle.ingreso_kg,
                ingreso_soles: detalle.ingreso_soles,
                subtotal: detalle.salida_soles
            });
        });
    }

    focusFirstField() {
        document.getElementById('formulacion_nombre').focus();
        const modalEl = this.modal._element;

        modalEl.addEventListener('shown.bs.modal', () => {
            const input = document.getElementById('formulacion_nombre');
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
    const preparadaManager = new PreparadaManager();
    document.body.addEventListener('click', function(e) {
        if (e.target && (e.target.matches('.btn-view-preparada') || e.target.closest('.btn-view-preparada'))) {
            const button = e.target.closest('.btn-view-preparada');
            const preparadaId = button.getAttribute('data-id');
            if (!preparadaId) return;
            
            const url = "{{ route('preparadas.ver', ':id') }}".replace(':id', preparadaId);

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
                });
        }

        // ─── Botón Rectificar Preparada ─────────────────────────────
        if (e.target && (e.target.matches('.btn-rectificar-preparada') || e.target.closest('.btn-rectificar-preparada'))) {
            const btnRectificar = e.target.closest('.btn-rectificar-preparada');
            const preparadaId = btnRectificar.getAttribute('data-id');
            if (preparadaId) {
                preparadaManager.showRectifyModal(preparadaId);
            }
        }

        // ─── Botón Anular Preparada ─────────────────────────────
        if (e.target && (e.target.matches('.btn-anular-preparada') || e.target.closest('.btn-anular-preparada'))) {
            const button = e.target.closest('.btn-anular-preparada');
            const preparadaId = button.getAttribute('data-id');
            if (!preparadaId) return;

            Swal.fire({
                title: '¿Anular preparada?',
                text: 'Esta acción revertirá los insumos consumidos y el producto final producido. El kardex se recalculará en cascada.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (!result.isConfirmed) return;

                const url = '{{ url("preparadas") }}/' + preparadaId + '/anular';
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Anulada',
                            text: data.message,
                            timer: 2500,
                            showConfirmButton: false
                        });
                        preparadaManager.tabla.ajax.reload();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(function(err) {
                    Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                    console.error(err);
                });
            });
        }
    });
});

document.getElementById('mnuProduccion').classList.add('menu-open');
document.getElementById('itemPreparadas').classList.add('active');
</script>
@endpush