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
                    <h3 class="card-title flex-grow-1">Núcleo Preparadas</h3>
                    @can('nucleo_preparadas_create')
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
                                    <th>Número Interno</th>
                                    <th>Código</th>
                                    <th>Núcleo</th>                                    
                                    <th>Unidad</th>
                                    <th>Empaque</th>
                                    <th>Cant. Porc.</th> 
                                    <th>Total Kg.</th>
                                    <th>Total Soles</th>
                                    <th>Items</th>
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
@canany(['nucleo_preparadas_create', 'nucleo_preparadas_edit'])
    @include('nucleo-preparadas.action')
@endcanany
<div id="modalContainer"></div>
@endsection
@push('scripts')
<script>
class NucleoPreparadaManager extends CrudManager {
    constructor() {
        super("{{ url('nucleo-preparadas') }}");
        this.afterSuccess = this.handlePreparadaSuccess.bind(this);
        this.initializeDataTable();

        document.addEventListener('click', (e) => {
            if (e.target && e.target.id === 'btnProcesar') {
                this.procesarProporcion();
            }
        });

        this.setupLiveSearchSelect({
            inputId: 'nucleo_nombre',
            hiddenId: 'nucleo_id',
            url: "{{ route('nucleos.buscar') }}",
            template: (item) => {
                return item.id
                    ? `${item.id} - ${item.nombre} (${item.empaque})`
                    : `${item.nombre} (${item.empaque})`;
            },
            getId: item => item.id,
            minLength : 1,
            delay : 300,
            onSelect: (item) => this.addNucleo(item)
        });
    }

    async handlePreparadaSuccess(response, isEditing) {
        // Solo mostrar opción de ticket para compras NUEVAS (no ediciones)
        if (!isEditing && response.nucleo_preparada_id) {
            setTimeout(() => {
                this.showTicketOption(response);
            }, 1000); // Esperar 1 segundo para que se vea la notificación de registro primero
        }
    }

    async showTicketOption(response){
        const result = await Swal.fire({
            title: '¡Preparación de Núcleo registrada!',
            text: '¿Deseas imprimir la preparación del núcleo?',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Imprimir Ticket',
            cancelButtonText: 'Continuar',
            reverseButtons: true,
            timer: 8000,
            timerProgressBar: true
        });
        
        if (result.isConfirmed) {
            const imprimirRuta = "{{ route('nucleo-preparadas.imprimir', ['id' => ':id']) }}";
            window.open(imprimirRuta.replace(':id', response.nucleo_preparada_id), '_blank');
        }
    }

   addNucleo(item) {
        const info = document.getElementById('info_nucleo');
        info.classList.remove('d-none');

        const tbody = document.querySelector('#tablaNucleos tbody');
        tbody.innerHTML = '';

        let totalKg = 0;
        let totalValorizado = 0;

        // 1) primer pase: sumar totales
        const detalles = (item.detalles || []).map(det => {
            const cantidadKg = parseFloat(det.cantidad || 0);
            const empaque = parseFloat(det.producto?.empaque || 0);
            const costoEmpaque = parseFloat(det.producto?.costo_unitario || 0);

            const costoKg = empaque > 0 ? (costoEmpaque / empaque) : 0;
            const valorizado = cantidadKg * costoKg;

            totalKg += cantidadKg;
            totalValorizado += valorizado;

            return { det, cantidadKg, empaque, costoEmpaque, costoKg, valorizado };
        });

        if (totalKg <= 0) {
            this.showNotification?.('error', 'No hay kilos para calcular proporción');
            document.getElementById('total_salida').textContent = '0.00';
            return;
        }

        // 2) segundo pase: pintar filas con porcentaje
        let sumaPct = 0;

        detalles.forEach(({ det, cantidadKg, empaque, costoEmpaque, costoKg, valorizado }, idx) => {
            let pct = (cantidadKg / totalKg) * 100;

            // Para evitar que por redondeo no llegue a 100.00,
            // ajustamos el último registro:
            if (idx === detalles.length - 1) {
                pct = 100 - sumaPct;
            }
            sumaPct += pct;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center">${det.producto_id}</td>
                <td>${(det.producto_nombre || '').trim()}</td>
                <td class="text-end">${empaque.toFixed(2)}</td>
                <td class="text-end">${det.unidad_codigo || ''}</td>
                <td class="text-end">${costoEmpaque.toFixed(4)}</td>
                <td class="text-end">${cantidadKg.toFixed(4)}</td>
                <td class="text-end">${costoKg.toFixed(4)}</td>
                <td class="text-end">${pct.toFixed(2)}%</td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('total_salida').textContent = totalKg.toFixed(4);

        //const el = document.getElementById('total_costo_unitario');
        //if (el) ('value' in el) ? el.value = totalValorizado.toFixed(2) : el.textContent = totalValorizado.toFixed(2);

        document.getElementById('nucleo_empaque_text').textContent = item.empaque || '0';
        document.getElementById('nucleo_unidad_text').textContent = item.unidad_nombre || '0';
    }

    //Recalculamos la tabla.
    procesarProporcion() {
        const tbodyOrigen  = document.querySelector('#tablaNucleos tbody');
        const tbodyDestino = document.querySelector('#tablaDetalles tbody');

        if (!tbodyOrigen || tbodyOrigen.rows.length === 0) {
            this.showNotification('error', 'Primero seleccione un núcleo');
            return;
        }

        const proporcion = parseFloat(document.getElementById('proporcion')?.value || 0);
        if (proporcion <= 0) {
            this.showNotification('error', 'Ingrese una proporción válida');
            return;
        }

        const costoServicio = parseFloat(
            document.getElementById('costo_servicio')?.value || 0
        );

        tbodyDestino.innerHTML = '';

        const totalBaseKg = parseFloat(
            document.querySelector('#tablaNucleos #total_salida')?.textContent || 0
        );

        if (totalBaseKg <= 0) {
            this.showNotification('error', 'No hay kilos base para procesar');
            return;
        }

        const factor = proporcion / totalBaseKg;

        //let totalSalidaKg    = 0;
        //let totalSalidaSaco  = 0;
        //let totalSalidaSoles = 0;

        // =========================================
        // DETALLES PROPORCIONALES
        // =========================================
        let preparadaTotal=0.0;

        [...tbodyOrigen.rows].forEach((row, index) => {
            const productoId     = row.cells[0].textContent.trim();
            const productoNombre = row.cells[1].textContent.trim();
            const empaque         = row.cells[2].textContent.trim();
            const unidad         = row.cells[3].textContent.trim();

            const costoBase   = parseFloat(row.cells[4].textContent) || 0;
            let cantidad = parseFloat(row.cells[5].textContent) || 0;
            const costoUnitario = parseFloat(row.cells[6].textContent) || 0;

            let salida_kg=cantidad*factor;
            const subtotal = costoUnitario * cantidad * factor;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${productoId}
                    <input type="hidden" name="detalles[${index}][producto_id]" value="${productoId}">
                </td>

                <td>${productoNombre}</td>
                <td>1</td>

                <td>${unidad}<input type="hidden" name="detalles[${index}][unidad_codigo]" value="${unidad}"></td>
                <td>${(cantidad).toFixed(4)}<input type="hidden" name="detalles[${index}][cantidad_porcentaje]" value="${cantidad}"></td>
                <td>${(salida_kg).toFixed(4)}<input type="hidden" name="detalles[${index}][salida_kg]" value="${salida_kg}"></td>
                <input type="hidden" name="detalles[${index}][costo_unitario]" value="${costoUnitario.toFixed(4)}">
                    
                </td>
                <td>${subtotal.toFixed(4)} <input type="hidden" name="detalles[${index}][salida_soles]" value="${subtotal.toFixed(4)}"></td>
            `;

            preparadaTotal += subtotal;
            tbodyDestino.appendChild(tr);
        });

        // =========================================
        // SERVICIO MEZCLADO
        // =========================================
        if (costoServicio > 0) {
            const idx = tbodyDestino.rows.length;

            tbodyDestino.insertAdjacentHTML('beforeend', `
                <tr>
                    <td>77</td>
                    <td>SERVICIO MEZCLADO</td>
                    <td>1</td>
                    <td>Servicio</td>
                    <td>0.00</td>
                    <td>0.00</td>
                    <td>${costoServicio.toFixed(2)}</td>

                    <input type="hidden" name="detalles[${idx}][producto_id]" value="77">
                    <input type="hidden" name="detalles[${idx}][unidad_codigo]" value="ZZ">
                    <input type="hidden" name="detalles[${idx}][cantidad_porcentaje]" value="0">
                    <input type="hidden" name="detalles[${idx}][salida_kg]" value="0">
                    <input type="hidden" name="detalles[${idx}][costo_unitario]" value="${costoServicio.toFixed(2)}">
                    <input type="hidden" name="detalles[${idx}][salida_soles]" value="${costoServicio.toFixed(2)}">
                </tr>
            `);

            preparadaTotal += costoServicio;
        }

        // =========================================
        // TOTALES
        // =========================================
        
        const kilosPorSaco = parseFloat(document.getElementById('nucleo_empaque_text')?.textContent || 1);
        const totalKg = proporcion;

        const sacos = (kilosPorSaco > 0) ? (totalKg / kilosPorSaco) : 0;
        const costoPorSaco = sacos > 0 ? preparadaTotal / sacos : 0;

        document.querySelector('#tablaDetalles #kilos_sacof').value = kilosPorSaco.toFixed(4);
        document.querySelector('#tablaDetalles #total_sacof').value = sacos.toFixed(4);
        document.querySelector('#tablaDetalles #costo_sacof').value = costoPorSaco.toFixed(4);

        document.querySelector('#tablaDetalles #preparada_total').value  = preparadaTotal.toFixed(4);
    }

    addDetalles(detalles) {
        const tbody = document.querySelector('#tablaDetalles tbody');
        tbody.innerHTML = '';
        const tbodyF = document.querySelector('#tablaNucleos tbody');
        tbodyF.innerHTML = '';

        detalles.forEach((det, index) => {

            const tr = document.createElement('tr');

             tr.innerHTML = `
                <td>${det.producto_id}
                    <input type="hidden" name="detalles[${index}][producto_id]" value="${det.producto_id}">
                </td>

                <td>${det.producto_nombre}</td>
                <td>${det.producto_empaque}</td>
                <td>${det.unidad_codigo}<input type="hidden" name="detalles[${index}][unidad_codigo]" value="${det.unidad_codigo}"></td>
                <td>${det.cantidad_porcentaje}<input type="hidden" name="detalles[${index}][cantidad_porcentaje]" value="${det.cantidad_porcentaje}"></td>
                <td>${det.salida_kg}<input type="hidden" name="detalles[${index}][salida_kg]" value="${det.salida_kg}"></td>

                
                    <input type="hidden" name="detalles[${index}][costo_unitario]" value="${det.costo_unitario}">
                

                <td>${det.salida_soles} <input type="hidden" name="detalles[${index}][salida_soles]" value="${det.salida_soles}"></td>
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
                { data: 'nucleo_id', name: 'nucleo_id',  orderable: false, searchable: false  },
                { data: 'nucleo_nombre', name: 'nucleo_nombre'},                
                { data: 'unidad_nombre', name: 'unidad_nombre', orderable: false, searchable: false },
                { data: 'producto_empaque', name: 'producto_empaque', orderable: false, searchable: false }, 
                { data: 'cantidad_porcentaje', name: 'cantidad_porcentaje', orderable: false, searchable: false },               
                { data: 'ingreso_kg', name: 'ingreso_kg', orderable: false, searchable: false,
                    render: function (data) {
                        return parseFloat(data).toFixed(2);
                    } 
                 },
                { data: 'ingreso_soles', name: 'ingreso_soles', orderable: false, searchable: false },
                { data: 'items', name: 'formulacion_id', orderable: false, searchable: false },
                { data: 'estado', name: 'estado', orderable: true, searchable: false }
            ],
            columnDefs: [
                { targets: 0,  width: '7%',  className: 'text-center' }, // Acción
                { targets: 1,  width: '4%',  className: 'text-center' }, // ID
                { targets: 2,  width: '8%'  }, // Usuario
                { targets: 3,  width: '8%',  className: 'text-center' }, // Fecha
                { targets: 4,  width: '7%'  }, // Número interno
                { targets: 5,  width: '4%',  className: 'text-end' }, // Ítems
                { targets: 6,  width: '13%',  className: 'text-end' }, // Salida Kg
                { targets: 7,  width: '7%',  className: 'text-end' }, // Salida S/
                { targets: 8,  width: '6%',  className: 'text-end' }, // Salida Saco
                { targets: 9,  width: '10%' }, // Producto
                { targets: 10, width: '5%',  className: 'text-center' }, // Formulación
                { targets: 11, width: '7%' }, 
                { targets: 12, width: '7%' }
            ],
            order: [[1, 'asc']], // Ordenar por ID por defecto
            responsive: true
        });
    }

    showCreateModal(){
        this.isEditing = false;
        this.isRectifying = false;
        this.resetForm();

        // Forzar limpieza de los hidden de rectificación (defensa contra residuos de sesión)
        const esRect = document.getElementById('es_rectificacion');
        if (esRect) esRect.value = '0';
        const preparadaAnuladaId = document.getElementById('preparada_anulada_id');
        if (preparadaAnuladaId) preparadaAnuladaId.value = '';

        this.elements.modalTitle.textContent = 'Nuevo Núcleo Preparada';
        this.elements.methodField.value = '';
        this.form.action = this.baseUrl;

        document.getElementById('info_nucleo').classList.add('d-none');

        document.getElementById('total_salida').textContent = '0.00';
        document.getElementById('total_salida').textContent = '0.00';
        //document.getElementById('total_costo_unitario').value = '0.00';

        document.querySelector('#tablaDetalles tbody').innerHTML = '';
        document.querySelector('#tablaNucleos tbody').innerHTML = '';

        this.setFieldValue('fecha', this.obtenerFechaHoraActual());
        const usuarioNombre = @json(auth()->user()->name);
        document.getElementById('usuario_nombre').textContent = usuarioNombre;

        this.modal.show();
        setTimeout(() => this.focusFirstField(), 150);
    }

    onModalHidden() {
        const esRect = document.getElementById('es_rectificacion');
        if (esRect) esRect.value = '0';
        const preparadaAnuladaId = document.getElementById('preparada_anulada_id');
        if (preparadaAnuladaId) preparadaAnuladaId.value = '';
        this.isEditing = false;
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

    async handleSubmit(e) {
        if (this.isRectifying) {
            e.preventDefault();
            const motivo = await solicitarMotivoAuditoria('Motivo de la rectificación', 'Este motivo quedará registrado permanentemente en Auditoría.');
            if (motivo === null) return;
            asignarMotivoAuditoria(this.form, motivo);
        }

        return super.handleSubmit(e);
    }

    async showEditModal(id) {
        this.resetForm();
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);

            this.isEditing = true;

            // Detectar si es rectificación (registro anulado)
            const esRectificacion = response.estado === 'anulada';

            if (esRectificacion) {
                // Modo Rectificación
                this.isRectifying = true;
                this.elements.modalTitle.textContent = 'Rectificar Núcleo Preparada: ' + response.id + ' ' + response.nucleo_nombre;
                this.elements.methodField.value = 'POST';

                // Agregar campos de rectificación
                let esRectificacionInput = document.getElementById('es_rectificacion');
                if (!esRectificacionInput) {
                    esRectificacionInput = document.createElement('input');
                    esRectificacionInput.type = 'hidden';
                    esRectificacionInput.id = 'es_rectificacion';
                    esRectificacionInput.name = 'es_rectificacion';
                    this.form.appendChild(esRectificacionInput);
                }
                esRectificacionInput.value = '1';

                let preparadaAnuladaIdInput = document.getElementById('preparada_anulada_id');
                if (!preparadaAnuladaIdInput) {
                    preparadaAnuladaIdInput = document.createElement('input');
                    preparadaAnuladaIdInput.type = 'hidden';
                    preparadaAnuladaIdInput.id = 'preparada_anulada_id';
                    preparadaAnuladaIdInput.name = 'preparada_anulada_id';
                    this.form.appendChild(preparadaAnuladaIdInput);
                }
                preparadaAnuladaIdInput.value = response.id;

                // Enviar a store (sin ID en URL)
                this.form.action = this.baseUrl;
            } else {
                // Modo Edición normal
                this.isRectifying = false;
                this.elements.modalTitle.textContent = 'Editar Núcleo Preparada: '+ response.id+ ' '+response.nucleo_nombre;
                this.elements.methodField.value = 'PUT';
                this.form.action = `${this.baseUrl}/${id}`;
            }
            
            this.setFieldValue('fecha', this.formatDateTimeLocal(response.fecha));
            const info = document.getElementById('info_nucleo');
            info.classList.remove('d-none');

            document.getElementById('nucleo_nombre').value = response.nucleo_nombre || '';
            document.getElementById('nucleo_empaque_text').textContent = response.empaque || '';
            document.getElementById('nucleo_unidad_text').textContent = response.unidad_nombre|| '';
            
            document.getElementById('nucleo_id').value = response.nucleo_id || '';
            document.getElementById('proporcion').value = response.ingreso_kg || '';
            document.getElementById('numero_interno').value = response.numero_interno || '';

            document.getElementById('total_salida').textContent = response.empaque|| '';

            document.querySelector('#tablaDetalles #kilos_sacof').value = response.producto_empaque;
            document.querySelector('#tablaDetalles #total_sacof').value = response.ingreso_saco;
            document.querySelector('#tablaDetalles #costo_sacof').value = response.costo_unitario;
            document.querySelector('#tablaDetalles #preparada_total').value  = response.ingreso_soles;

            // Llenar tabla de detalles (productos)
            
            this.addDetalles(response.detalles);
            
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
        document.getElementById('nucleo_nombre').focus();
        const modalEl = this.modal._element;

        modalEl.addEventListener('shown.bs.modal', () => {
            const input = document.getElementById('nucleo_nombre');
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
    window.nucleoPreparadaManager = new NucleoPreparadaManager();
    document.body.addEventListener('click', function(e) {
        // ─── Botón Ver Nucleo Preparada ─────────────────────────────
        if (e.target && (e.target.matches('.btn-view-nucleo-preparada') || e.target.closest('.btn-view-nucleo-preparada'))) {
            const button = e.target.closest('.btn-view-nucleo-preparada');
            const preparadaId = button.getAttribute('data-id');
            if (!preparadaId) return;
            
            const url = "{{ route('nucleo-preparadas.ver', ':id') }}".replace(':id', preparadaId);

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

        // ─── Botón Anular Nucleo Preparada ─────────────────────────────
        if (e.target && (e.target.matches('.btn-anular-nucleo-preparada') || e.target.closest('.btn-anular-nucleo-preparada'))) {
            const button = e.target.closest('.btn-anular-nucleo-preparada');
            const preparadaId = button.getAttribute('data-id');
            if (!preparadaId) return;

            Swal.fire({
                title: '¿Anular preparación de núcleo?',
                text: 'Esta acción revertirá los insumos consumidos y el producto final producido. El kardex se recalculará en cascada.',
                input: 'textarea',
                inputLabel: 'Motivo (opcional)',
                inputPlaceholder: 'Puede describir el motivo o dejarlo vacío',
                inputAttributes: { maxlength: 500 },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (!result.isConfirmed) return;

                const url = '{{ url("nucleo-preparadas") }}/' + preparadaId + '/anular';
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ motivo: String(result.value).trim() })
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
                        window.nucleoPreparadaManager.tabla.ajax.reload();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(function(err) {
                    console.error(err);
                    Swal.fire('Error', 'Ocurrió un error al procesar la solicitud', 'error');
                });
            });
        }

        // ─── Botón Rectificar Nucleo Preparada ─────────────────────────────
        if (e.target && (e.target.matches('.btn-rectificar-nucleo-preparada') || e.target.closest('.btn-rectificar-nucleo-preparada'))) {
            const button = e.target.closest('.btn-rectificar-nucleo-preparada');
            const preparadaId = button.getAttribute('data-id');
            if (!preparadaId) return;

            window.nucleoPreparadaManager.showEditModal(preparadaId);
        }
    });
});

document.getElementById('mnuNucleo').classList.add('menu-open');
document.getElementById('itemPreparacionNucleos').classList.add('active');
</script>
@endpush
