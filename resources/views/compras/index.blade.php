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
                    <h3 class="card-title flex-grow-1">Compras</h3>
                    @can('compras_create')
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
                                    <th>Usuario</th>
                                    <th>Fecha</th>
                                    <th>Forma de Pago</th>
                                    <th>Proveedor</th>
                                    <th>TC</th>
                                    <th>Serie</th>
                                    <th>Correlativo</th>                           
                                    <th>Total</th>
                                    <th>Abonos</th>
                                    <th>Saldo</th>
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
@canany(['compras_create', 'compras_edit'])
    @include('compras.action')
@endcanany
<div id="modalContainer"></div>
@endsection
@push('scripts')
<script>
class CompraManager extends CrudManager {
    constructor() {
        super("{{ url('compras') }}");
        this.afterSuccess = this.handleCompraSuccess.bind(this);
        this.initializeDataTable();

        this.populateSelect('documento_tipo_codigo', '{{ route("documento-tipos.select") }}', item =>
            `<option value="${item.codigo}">${item.codigo} - ${item.descripcion}</option>`
        );

        this.populateSelect(
            'comprobante_tipo_codigo',
            '{{ route("comprobante-tipos.select") }}?tipo=compras',
            item => `<option value="${item.codigo}">${item.codigo} - ${item.descripcion}</option>`
        );

        this.populateSelect('pago_forma_codigo', '{{ route("pago-formas.select") }}', item =>
            `<option value="${item.codigo}" data-dias="${item.dias}">${item.codigo} - ${item.descripcion}</option>`
        );

        this.populateSelect('cobranza_tipo_id', '{{ route("cobranza-tipos.select") }}', item =>
            `<option value="${item.id}">${item.id} - ${item.nombre}</option>`
        );

        this.setupLiveSearchSelect({
            inputId: 'producto_nombre',
            hiddenId: 'producto_id',
            url: "{{ route('productos.buscar') }}",
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
            inputId: 'proveedor_razon_social',
            hiddenId: 'proveedor_id',
            url: "{{ route('proveedores.buscar') }}",
            template: (item) => {
                const doc = item.documento_numero;
                return doc
                    ? `${item.id} - ${item.razon_social} (${doc})`
                    : `${item.id} - ${item.razon_social}`;
            },
            minLength : 1,
            delay : 300,
        });

        document.getElementById('btnRegistrarProveedor').addEventListener('click', () => this.registerSupplier());

        // Evento para el select de comprobante_tipo_codigo
        const selectComprobante = document.getElementById('comprobante_tipo_codigo');
        if (selectComprobante) {
            selectComprobante.addEventListener('change', (e) => {
                this.getSerie(e.target.value); // Ahora sí llama al método de la clase
            });
        }
        // Evento para el select de cobranza_tipo_id
        const selectCobranza = document.getElementById('cobranza_tipo_id');
        if (selectCobranza) {
            selectCobranza.addEventListener('change', (e) => {
                this.updateCobranza(e.target.value); // Ahora sí llama al método de la clase
            });
        }
        // Recalcular fecha de vencimiento al cambiar forma de pago
        const pagoFormaEl = document.getElementById('pago_forma_codigo');
        if (pagoFormaEl) {
            pagoFormaEl.addEventListener('change', () => this.calcularFechaVencimiento());
        }
    }

    async handleCompraSuccess(response, isEditing) {
        // Solo mostrar opción de ticket para compras NUEVAS (no ediciones)
        if (!isEditing && response.compra_id) {
            setTimeout(() => {
                this.showTicketOption(response);
            }, 1000); // Esperar 1 segundo para que se vea la notificación de registro primero
        }
    }

    async showTicketOption(response){
        const result = await Swal.fire({
            title: '¡Compra registrada!',
            text: '¿Deseas imprimir el ticket?',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Imprimir Ticket',
            cancelButtonText: 'Continuar',
            reverseButtons: true,
            timer: 8000,
            timerProgressBar: true
        });
        
        if (result.isConfirmed) {
            const imprimirRuta = "{{ route('compras.imprimir', ['id' => ':id']) }}";
            window.open(imprimirRuta.replace(':id', response.compra_id), '_blank');
        }
    }

    async handleSubmit(e) {
        if (!this.validarDistribucionCobranza()) {
            e.preventDefault();
            return;
        }

        if (this.isRectifying) {
            e.preventDefault();
            const motivo = await solicitarMotivoAuditoria('Motivo de la rectificación', 'Puede registrar un motivo o continuar dejando el campo vacío.');
            if (motivo === null) return;
            asignarMotivoAuditoria(this.form, motivo);
        }

        return super.handleSubmit(e);
    }

     buildUnidadSelect(fracciones, unidadActual) {
        return `
            <select class="form-select form-select-sm selectUnidad">
                ${fracciones.map(f => `
                    <option 
                        value="${f.unidad_codigo}"
                        data-empaque="${f.empaque}"
                        data-precio="${f.precio_lista}"
                        ${f.unidad_codigo === unidadActual ? 'selected' : ''}
                    >
                        ${f.unidad_codigo}
                    </option>
                `).join('')}
            </select>
        `;
    }

    addProductoToTable(item, cantidad = 1, precio_unitario = null,precio_unitario_servicio= null, subtotal = null) {
        //console.log('Agregando producto:', item);
        
        const tbody = document.querySelector('#tablaDetalles tbody');
        document.getElementById('producto_id').value = '';
        document.getElementById('producto_nombre').value = '';
        

        // Determinar el precio unitario: si no se pasa, usar costo_unitario del item
        const fracciones = item.fracciones || [];

        // buscar fracción por unidad actual o usar la primera disponible
        const fraccionActual = fracciones.find(f => f.unidad_codigo === item.unidad_codigo) || fracciones[0];

        // En compras siempre se usa el costo_unitario registrado del producto
        const precioInicial = parseFloat(item.costo_unitario || 0);

        // empaque inicial
        const empaqueInicial = fraccionActual
            ? parseFloat(fraccionActual.empaque || 1)
            : parseFloat(item.empaque || 1) || 1;
            
        const precioConImpuesto = precio_unitario != null
            ? parseFloat(precio_unitario || 0)
            : precioInicial;

        // Determinar el precio unitario: si no se pasa, usar costo_unitario del item
        const costoUnitario = parseFloat(item.costo_unitario) || 0;
        const precioServicio = parseFloat(precio_unitario_servicio || 0);

        //console.log('precioConImpuesto:', precioConImpuesto);

        // Verificar si el producto ya existe en la tabla
        const existingRow = [...tbody.querySelectorAll('tr')].find(row => row.dataset.productoId == item.id);

        if (existingRow) {
            const inputCantidad = existingRow.querySelector('.inputCantidad');
            const nuevaCantidad = parseInt(inputCantidad.value) + cantidad;
            inputCantidad.value = nuevaCantidad;

            const precio = parseFloat(existingRow.querySelector('.inputPrecioUnitario').value) || precioConImpuesto;
            existingRow.querySelector('.inputTotal').value = (precio * nuevaCantidad).toFixed(2);
            this.calculateTotals();

        } else {
            const rowCount = tbody.rows.length + 1;
            const porcentaje = parseFloat((item.afectacion_tipo && item.afectacion_tipo.porcentaje) || 0);
            const sub = subtotal != null ? subtotal : (precioConImpuesto * cantidad);
            const valorUnitario = precioConImpuesto / (1 + porcentaje);
            const impuesto = precioConImpuesto - valorUnitario;
            
            const unidadDet = (item.unidad && item.unidad.descripcion != null) ? item.unidad.descripcion : item.unidad_nombre;

            const tr = document.createElement('tr');
            tr.dataset.productoId = item.id;
            tr.dataset.afectacionPorcentaje = porcentaje;
            tr.dataset.afectacionCodigo = item.afectacion_tipo_codigo || '10';
            tr.dataset.empaque = empaqueInicial;
            tr.dataset.totalManual = (item.total_manual === "1") ? "1" : "0";

            tr.innerHTML = `
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm btnEliminarFila">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
                <td class="text-center">${rowCount}</td>
                <td>(${item.id}) ${item.nombre || ''}</td>
                <td>${this.buildUnidadSelect(fracciones, item.unidad_codigo)}</td>
                <td class="tdEmpaque">${empaqueInicial}</td>
                <td>
                    <input type="number" name="detalles[${rowCount}][cantidad]" value="${cantidad}" step="any" class="form-control form-control-sm inputCantidad">
                </td>
                <td>
                    <input type="number" name="detalles[${rowCount}][cantidad_kgm]" value="${cantidad*empaqueInicial}" step="any" class="form-control form-control-sm inputCantidadKgm">
                </td>
                <td class="text-end">
                    <input type="number" name="detalles[${rowCount}][precio_unitario]" value="${precioConImpuesto.toFixed(4)}" step="any" class="form-control form-control-sm inputPrecioUnitario">
                </td>
                <td class="text-end">
                    <input type="number" name="detalles[${rowCount}][precio_unitario_servicio]" value="${precioServicio.toFixed(2)}" step="any" min="0" max="9" class="form-control form-control-sm inputPrecioServicio">
                </td>
                <td class="text-end">
                    <input type="number" name="detalles[${rowCount}][total]" value="${sub.toFixed(2)}" step="any" class="form-control form-control-sm inputTotal">
                </td>

                <!-- Solo estos inputs viajan al backend -->
                <input type="hidden" name="detalles[${rowCount}][producto_id]" value="${item.id}">
                <input type="hidden" name="detalles[${rowCount}][producto_id]" value="${item.id}">
                <input type="hidden" name="detalles[${rowCount}][unidad_codigo]" value="${item.unidad_codigo || ''}">
                <input type="hidden" name="detalles[${rowCount}][empaque]" value="${empaqueInicial}">
            `;

            const selectUnidad = tr.querySelector('.selectUnidad');

            if (selectUnidad) {
                selectUnidad.addEventListener('change', (e) => {
                    const option = e.target.selectedOptions[0];

                    const nuevoEmpaque = parseFloat(option.dataset.empaque) || 1;
                    const nuevoPrecio  = parseFloat(option.dataset.precio) || 0;

                    const inputUnidad  = tr.querySelector('input[name*="[unidad_codigo]"]');
                    const inputEmpaque = tr.querySelector('input[name*="[empaque]"]');

                    if (inputUnidad)  inputUnidad.value  = option.value;
                    if (inputEmpaque) inputEmpaque.value = nuevoEmpaque;

                    // dataset
                    tr.dataset.empaque = nuevoEmpaque;

                    // actualizar columna empaque (ajusta índice si cambian columnas)
                    const tdEmpaque = tr.querySelector('.tdEmpaque') || tr.children[4];
                    if (tdEmpaque) tdEmpaque.textContent = nuevoEmpaque;

                    const inputCantidad = tr.querySelector('.inputCantidad');
                    const inputCantidadKgm = tr.querySelector('.inputCantidadKgm');
                    const inputPrecio = tr.querySelector('.inputPrecioUnitario');
                    const inputTotal = tr.querySelector('.inputTotal');

                    const cant = parseFloat(inputCantidad.value) || 0;

                    inputPrecio.value = nuevoPrecio.toFixed(2);
                    inputCantidadKgm.value = (cant * nuevoEmpaque).toFixed(2);

                    // subtotal
                    if (tr.dataset.totalManual !== "1") {
                        inputTotal.value = (cant * nuevoPrecio).toFixed(2);
                    }

                    this.calculateTotals();
                });
            }

            // Eventos
            const inputCantidadObj = tr.querySelector('.inputCantidad');
            const inputPrecioObj = tr.querySelector('.inputPrecioUnitario');
            const inputPrecioServicioObj = tr.querySelector('.inputPrecioServicio');
            const inputTotalObj = tr.querySelector('.inputTotal');

            const recalcularFilaAuto = () => {
                const cant = parseFloat(inputCantidadObj.value) || 0;
                const prec = parseFloat(inputPrecioObj.value) || 0;
                const precServ = parseFloat(inputPrecioServicioObj?.value) || 0;
                inputTotalObj.value = (cant * prec).toFixed(2);
                this.calculateTotals();
            };

            inputCantidadObj.addEventListener('input', (e) => {
                tr.dataset.totalManual = "0";
                const cantidad = parseFloat(e.target.value) || 0;
                const empaque = parseFloat(tr.dataset.empaque || 1);
                const inputCantidadKgm = tr.querySelector('.inputCantidadKgm');
                
                if (inputCantidadKgm) {
                    inputCantidadKgm.value = (cantidad * empaque).toFixed(2);
                }
                recalcularFilaAuto();
            });
            inputPrecioObj.addEventListener('input', () => {
                tr.dataset.totalManual = "0";
                recalcularFilaAuto();
            });
            if (inputPrecioServicioObj) {
                inputPrecioServicioObj.addEventListener('input', () => {
                    const val = parseFloat(inputPrecioServicioObj.value) || 0;
                    if (val > 9) {
                        inputPrecioServicioObj.value = '9';
                        inputPrecioServicioObj.classList.add('is-invalid');
                        this.showNotification('warning', 'El precio de servicio no puede exceder 9');
                    } else if (val < 0) {
                        inputPrecioServicioObj.value = '0';
                        inputPrecioServicioObj.classList.add('is-invalid');
                    } else {
                        inputPrecioServicioObj.classList.remove('is-invalid');
                        inputPrecioServicioObj.parentElement.querySelector('.invalid-feedback')?.remove();
                    }
                    this.calculateTotals();
                });
            }

            inputTotalObj.addEventListener('input', (e) => {
                tr.dataset.totalManual = "1";
                const cant = parseFloat(inputCantidadObj.value) || 0;
                const tot = parseFloat(e.target.value) || 0;
                if (cant > 0) {
                    inputPrecioObj.value = (tot / cant).toFixed(4);
                }
                this.calculateTotals();
            });

            tr.querySelector('.btnEliminarFila').addEventListener('click', () => {
                tr.remove();
                this.reindexDetalles(); 
                this.calculateTotals();
            });
            // Actualizar cantidad
            const inputCantidadKgm = tr.querySelector('.inputCantidadKgm');
            inputCantidadKgm.addEventListener('input', (e) => {
                const empaque = parseFloat(tr.dataset.empaque || 1);
                const cantidadKgm = parseFloat(e.target.value) || 0;
                
                // Calcular cantidad dividiendo kilos entre empaque
                const nuevaCantidad = empaque > 0 ? (cantidadKgm / empaque) : 0;
                inputCantidadObj.value = nuevaCantidad.toFixed(2);
                
                tr.dataset.totalManual = "0";
                recalcularFilaAuto();
            });

            // Configurar limpiador de errores y prevención de wheel scroll en inputs numéricos
            const allInputs = tr.querySelectorAll('input[type="number"]');
            allInputs.forEach(input => {
                this.setupInputErrorClear(input);
                // Evitar que la rueda del mouse altere la cantidad, precios o totales al enfocar el input
                input.addEventListener('wheel', (e) => {
                    if (document.activeElement === e.target) {
                        e.target.blur();
                    }
                }, { passive: true });
            });

            tbody.appendChild(tr);
        }

        this.calculateTotals();
    }


    calculateTotals() {
        const tbody = document.querySelector('#tablaDetalles tbody');
        if (!tbody) return;

        let op_gravada = 0, op_exonerada = 0, op_inafecta = 0, totalImpuesto = 0;

        [...tbody.querySelectorAll('tr')].forEach(row => {
            const cantidad = parseFloat(row.querySelector('.inputCantidad').value) || 0;
            const precioUnitario = parseFloat(row.querySelector('.inputPrecioUnitario').value) || 0;
            const porcentaje = parseFloat(row.dataset.afectacionPorcentaje || 0);
            const afectacionCodigo = row.dataset.afectacionCodigo;
            const empaque = parseFloat(row.dataset.empaque || 1);

            const itemSubtotal = parseFloat(row.querySelector('.inputTotal').value) || 0;
            let baseSinImpuesto = itemSubtotal, impuesto = 0;

            if (afectacionCodigo === '10') { 
                baseSinImpuesto = itemSubtotal / (1 + porcentaje);
                impuesto = itemSubtotal - baseSinImpuesto;
                op_gravada += baseSinImpuesto;
                totalImpuesto += impuesto;
            } else if (afectacionCodigo === '20') op_exonerada += itemSubtotal;
            else if (afectacionCodigo === '30') op_inafecta += itemSubtotal;

        });

        const total = op_gravada + op_exonerada + op_inafecta + totalImpuesto;

        // Solo mostramos en el formulario (no viajan al backend)
        document.getElementById('op_gravada').value = op_gravada.toFixed(2);
        document.getElementById('op_exonerada').value = op_exonerada.toFixed(2);
        document.getElementById('op_inafecta').value = op_inafecta.toFixed(2);
        document.getElementById('impuesto').value = totalImpuesto.toFixed(2);
        document.getElementById('total').value = total.toFixed(2);
        if (this.isRectifying) {
            // En rectificación, solo recalcular automaticamente si es Principal
            const tipoActual = document.getElementById('cobranza_tipo_id')?.value || '1';
            if (tipoActual === '1') {
                this.updateCobranza(tipoActual);
            }
            // Para Deposito (2) y Consortium (3) no hacer nada - mantienen sus valores manuales
        } else {
            this.updateCobranza();
        }
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
                { data: 'usuario', name: 'usuario' },
                { data: 'fecha_compra', name: 'fecha_compra' },
                { data: 'pago_forma', name: 'pago_forma' },
                { data: 'proveedor', name: 'proveedor' },
                { data: 'tipo_comprobante', name: 'tipo_comprobante' },
                { data: 'serie', name: 'serie' },
                { data: 'correlativo', name: 'correlativo' },
                { data: 'total', name: 'total' },
                { data: 'abonos', name: 'abonos' },
                { data: 'saldo', name: 'saldo' },
                { data: 'estado', name: 'estado' }
            ],
            columnDefs: [
                { targets: 0, width: '10%', className: 'text-center' },
                { targets: 1, width: '10%' },
                { targets: 2, width: '10%' },
                { targets: 3, width: '10%' },
                { targets: 4, width: '10%' },
                { targets: 5, width: '10%' },
                { targets: 6, width: '10%' },
                { targets: 7, width: '5%' },
                { targets: 8, width: '5%' },
                { targets: 9, width: '10%' },
                { targets: 10, width: '5%' },
                { targets: 11, width: '5%' }
            ],
            responsive: true
        });
    }

    showCreateModal(){
        super.showCreateModal();
        this.isRectifying = false;
        this.bindPagoFormaChange();
        this.bindCobranzaInputs();
        this.elements.modalTitle.textContent = 'Nueva Compra';
        document.getElementById('es_rectificacion').value = '0';
        document.getElementById('compra_anulada_id').value = '';
        
        document.querySelector('#tablaDetalles tbody').innerHTML = '';
        document.getElementById('documento_tipo_codigo').value = '01';
        document.getElementById('proveedor_id').value = '';
        document.getElementById('proveedor_razon_social').value = '';
        document.getElementById('comprobante_tipo_codigo').value = 'NC';
        document.getElementById('pago_forma_codigo').value = '1';
        document.getElementById('cobranza_tipo_id').value = '1';
        this.setFieldValue('fecha_compra', this.obtenerFechaHoraActual());
        this.setFieldValue('fecha_vencimiento', this.obtenerFechaActual());
        const usuarioNombre = @json(auth()->user()->name);
        document.getElementById('usuario_nombre').textContent = usuarioNombre;
        this.getSerie('NC');
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

    obtenerFechaActual() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    }

    calcularFechaVencimiento() {
        const select = document.getElementById('pago_forma_codigo');
        const fechaCompraInput = document.getElementById('fecha_compra');
        const fechaVencInput = document.getElementById('fecha_vencimiento');

        if (!select || !fechaCompraInput || !fechaCompraInput.value) return;

        const option = select.options[select.selectedIndex];
        const dias = parseInt(option.dataset.dias || 0);

        const fechaCompra = new Date(fechaCompraInput.value);
        fechaCompra.setDate(fechaCompra.getDate() + dias);

        const yyyy = fechaCompra.getFullYear();
        const mm = String(fechaCompra.getMonth() + 1).padStart(2, '0');
        const dd = String(fechaCompra.getDate()).padStart(2, '0');

        fechaVencInput.value = `${yyyy}-${mm}-${dd}`;
    }
    
    bindPagoFormaChange() {
        const select = document.getElementById('pago_forma_codigo');
        if (!select) return;

        // ✅ para que no se duplique el listener cada vez que abres el modal
        if (select.dataset.boundCobranza === '1') return;
        select.dataset.boundCobranza = '1';

        select.addEventListener('change', () => {
            if (select.value === '1') {
                this.updateCobranza();
            } else {
                this.limpiarCobranza();
            }
        });
    }

    updateCobranza(tipo = null) {
        const pagoForma = document.getElementById('pago_forma_codigo')?.value;

        const depositoEl = document.getElementById('deposito');
        const principalEl = document.getElementById('principal');
        const consorcioEl = document.getElementById('consorcio');
        const totalCobranzaEl = document.getElementById('total_cobranza');

        // En crédito se conserva la distribución manual ingresada por el usuario.
        if (pagoForma !== '1') {
            this.recalcularTotalCobranza();
            return;
        }

        // En contado se distribuye automáticamente el total en la caja elegida.
        tipo = tipo ?? document.getElementById('cobranza_tipo_id')?.value;
        if (!tipo) return;

        const total = parseFloat(document.getElementById('total').value) || 0;

        // reset
        depositoEl.value = '0.00';
        principalEl.value = '0.00';
        consorcioEl.value = '0.00';

        if (tipo === '1') {
            principalEl.value = total.toFixed(2);
        } 
        else if (tipo === '2') {
            depositoEl.value = total.toFixed(2);
        } 
        else if (tipo === '3') {
            consorcioEl.value = total.toFixed(2);
        }

        totalCobranzaEl.value = total.toFixed(2);
        this.recalcularTotalCobranza();
    }

    limpiarCobranza() {
        ['principal', 'deposito', 'consorcio'].forEach(id => {
            const input = document.getElementById(id);
            if (input) input.value = '0.00';
        });
        this.recalcularTotalCobranza();
    }


    async showEditModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);
            
            this.isEditing = true;
            this.isRectifying = false;
            this.resetForm();
            this.bindPagoFormaChange();
            this.bindCobranzaInputs();
            
            this.elements.modalTitle.textContent = 'Editar Compra: '+ response.comprobante_tipo_codigo + ' ' + response.serie + '-' + response.correlativo
            this.elements.methodField.value = 'PUT';
            document.getElementById('es_rectificacion').value = '0';
            
            this.populateModalData(response);
            this.modal.show();

        } catch (error) {
            console.error('Error al cargar la compra:', error);
            Swal.fire('Error', 'No se pudo cargar la información', 'error');
        }
    }

    async showRectifyModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);
            
            this.isEditing = false; // Queremos que se comporte como un registro NUEVO
            this.isRectifying = true;
            this.resetForm();
            this.bindPagoFormaChange();
            this.bindCobranzaInputs();
            
            this.elements.modalTitle.textContent = 'Rectificar Compra: '+ response.comprobante_tipo_codigo + ' ' + response.serie + '-' + response.correlativo;
            this.elements.methodField.value = 'POST';
            document.getElementById('es_rectificacion').value = '1';
            document.getElementById('compra_anulada_id').value = id;
            
            this.populateModalData(response);
            this.modal.show();
            
            Swal.fire({
                title: 'Modo Rectificación',
                text: 'Estás usando los datos de una compra anulada. Las modificaciones que hagas se guardarán como una nueva compra y el Kardex se recalculará desde la fecha de la compra anulada.',
                icon: 'info',
                confirmButtonText: 'Entendido'
            });

        } catch (error) {
            console.error('Error al cargar la compra:', error);
            Swal.fire('Error', 'No se pudo cargar la información para rectificar', 'error');
        }
    }

    populateModalData(response) {
        try {
            if (!response) {
                throw new Error("Respuesta vacía del servidor");
            }

            // Campos principales
            document.getElementById('pago_forma_codigo').value = response.pago_forma_codigo || '';
            document.getElementById('comprobante_tipo_codigo').value = response.comprobante_tipo_codigo || '';
            document.getElementById('serie').value = response.serie || '';
            document.getElementById('correlativo').value = response.correlativo || '';

            document.getElementById('proveedor_id').value = response.proveedor_id || '';

            const proveedorTexto = response.proveedor_id
                ? `${response.proveedor_id} - ${response.proveedor_nombre || ''}`
                : '';
            document.getElementById('proveedor_razon_social').value = proveedorTexto;

            this.setFieldValue('fecha_compra', this.formatDateTimeLocal(response.fecha_compra));
            this.setFieldValue('fecha_vencimiento', response.fecha_vencimiento || '');
            document.getElementById('usuario_nombre').textContent = response.user_nombre || '';

            // Determinar cobranza_tipo_id desde los datos de pago
            let cobranzaTipoId = '1';
            const importeP = parseFloat(response.importe_p) || 0;
            const importeD = parseFloat(response.importe_d) || 0;
            const importeC = parseFloat(response.importe_c) || 0;
            const acuenta = parseFloat(response.acuenta) || 0;
            if (importeC > 0 && Math.abs(importeC - acuenta) < 0.01) cobranzaTipoId = '3';
            else if (importeD > 0 && Math.abs(importeD - acuenta) < 0.01) cobranzaTipoId = '2';
            document.getElementById('cobranza_tipo_id').value = cobranzaTipoId;

            // Guardar valores originales de cobranza ANTES de updateDetailsTable
            const originalPrincipal = importeP.toFixed(2);
            const originalDeposito = importeD.toFixed(2);
            const originalConsorcio = importeC.toFixed(2);
            const originalTotalCobranza = (importeP + importeD + importeC).toFixed(2);

            // Tabla detalles - deshabilitar temporalmente updateCobranza
            const wasRectifying = this.isRectifying;
            this.isRectifying = false;
            if (Array.isArray(response.detalles)) {
                this.updateDetailsTable(response.detalles);
            } else {
                this.updateDetailsTable([]);
            }
            this.isRectifying = wasRectifying;

            // Totales
            const op_gravada = parseFloat(response.op_gravada) || 0;
            const op_exonerada = parseFloat(response.op_exonerada) || 0;
            const op_inafecta = parseFloat(response.op_inafecta) || 0;
            const impuesto = parseFloat(response.impuesto) || 0;
            const total = parseFloat(response.total) || 0;

            document.getElementById('op_gravada').value = op_gravada.toFixed(2);
            document.getElementById('op_exonerada').value = op_exonerada.toFixed(2);
            document.getElementById('op_inafecta').value = op_inafecta.toFixed(2);
            document.getElementById('impuesto').value = impuesto.toFixed(2);
            document.getElementById('total').value = total.toFixed(2);

            // Restaurar valores originales de cobranza
            document.getElementById('principal').value = originalPrincipal;
            document.getElementById('deposito').value = originalDeposito;
            document.getElementById('consorcio').value = originalConsorcio;
            document.getElementById('total_cobranza').value = originalTotalCobranza;

            // Preservar exactamente la distribución original, incluso si usa varias cajas.
            this.recalcularTotalCobranza();

            // Acción del formulario
            if (this.elements.methodField.value === 'PUT' && response.id) {
                this.form.action = `${this.baseUrl}/${response.id}`;
            } else {
                this.form.action = this.baseUrl;
            }

        } catch (error) {
            console.error('Error al cargar datos:', error);
            if (typeof this.showNotification === "function") {
                this.showNotification('error', 'Error al cargar los datos de la compra');
            } else {
                alert('Error al cargar los datos de la compra');
            }
        }
    }




    aplicarCobranza(cobranza_tipo_id, acuenta) {
        // ⏳ Esperar a que ambos existan
        if (!cobranza_tipo_id || acuenta === null || acuenta === undefined) {
            return;
        }

        const monto = parseFloat(acuenta);
        if (isNaN(monto)) return;

        const principalEl = document.getElementById('principal');
        const depositoEl = document.getElementById('deposito');
        const consorcioEl = document.getElementById('consorcio');
        const totalCobranzaEl = document.getElementById('total_cobranza');


        // Resetear todos
        principalEl.value = '0.00';
        depositoEl.value = '0.00';
        consorcioEl.value = '0.00';

        // Asignar según tipo
        if (cobranza_tipo_id == 1) principalEl.value = monto.toFixed(2);
        if (cobranza_tipo_id == 2) depositoEl.value = monto.toFixed(2);
        if (cobranza_tipo_id == 3) consorcioEl.value = monto.toFixed(2);

        // Total cobranza (recién aquí)
        totalCobranzaEl.value = monto.toFixed(2);
        this.recalcularTotalCobranza();
    }

    bindCobranzaInputs() {
        ['deposito', 'principal', 'consorcio'].forEach(id => {
            const el = document.getElementById(id);
            if (el && el.dataset.boundCobranzaInput !== '1') {
                el.dataset.boundCobranzaInput = '1';
                el.addEventListener('input', () => this.recalcularTotalCobranza());
            }
        });
    }

    recalcularTotalCobranza() {
        const total = parseFloat(document.getElementById('total').value) || 0;

        const deposito = parseFloat(document.getElementById('deposito').value) || 0;
        const principal = parseFloat(document.getElementById('principal').value) || 0;
        const consorcio = parseFloat(document.getElementById('consorcio').value) || 0;

        const totalCobranza = deposito + principal + consorcio;

        const totalCobranzaEl = document.getElementById('total_cobranza');
        totalCobranzaEl.value = totalCobranza.toFixed(2);

        const saldoPendienteEl = document.getElementById('saldo_pendiente');
        if (saldoPendienteEl) {
            saldoPendienteEl.value = Math.max(total - totalCobranza, 0).toFixed(2);
        }

        const importesNegativos = [deposito, principal, consorcio].some(importe => importe < 0);
        const excedeTotal = totalCobranza - total > 0.009;
        const esContado = document.getElementById('pago_forma_codigo')?.value === '1';
        const contadoIncompleto = esContado && total > 0 && Math.abs(totalCobranza - total) > 0.009;

        ['deposito', 'principal', 'consorcio'].forEach(id => {
            const input = document.getElementById(id);
            if (!input) return;
            const valor = parseFloat(input.value) || 0;
            input.classList.toggle('is-invalid', valor < 0);
        });

        if (importesNegativos || excedeTotal || contadoIncompleto) {
            totalCobranzaEl.classList.add('is-invalid');
        } else {
            totalCobranzaEl.classList.remove('is-invalid');
        }

        return !(importesNegativos || excedeTotal || contadoIncompleto);
    }

    validarDistribucionCobranza() {
        const esValida = this.recalcularTotalCobranza();
        if (esValida) return true;

        const total = parseFloat(document.getElementById('total')?.value) || 0;
        const pagoInicial = parseFloat(document.getElementById('total_cobranza')?.value) || 0;
        const esContado = document.getElementById('pago_forma_codigo')?.value === '1';
        let mensaje = 'Los importes de caja deben ser mayores o iguales a cero.';

        if (pagoInicial - total > 0.009) {
            mensaje = 'El pago inicial no puede ser mayor al total del documento.';
        } else if (esContado && Math.abs(pagoInicial - total) > 0.009) {
            mensaje = 'Una compra al contado debe quedar pagada completamente.';
        }

        Swal.fire({ icon: 'error', title: 'Distribución de caja inválida', text: mensaje });
        return false;
    }
    
    updateDetailsTable(detalles = []) {
        const tbody = document.querySelector('#tablaDetalles tbody');
        tbody.innerHTML = '';

        detalles.forEach(detalle => {
            const producto = detalle.producto || {}; // ✅

            const productoFormateado = {
                id: detalle.producto_id,
                nombre: detalle.producto_nombre,
                costo_unitario: detalle.costo_unitario,

                unidad_codigo: detalle.unidad_codigo,
                empaque: detalle.producto_empaque,

                fracciones: producto.fracciones || [], // ✅

                afectacion_tipo_codigo: producto.afectacion_tipo_codigo,
                afectacion_tipo: {
                    porcentaje: parseFloat(producto.afectacion_tipo?.porcentaje || 0)
                },
                unidad: {
                    codigo: detalle.unidad_codigo,
                    descripcion: detalle.unidad_nombre
                },
                total_manual: "1"
            };

            this.addProductoToTable(
                productoFormateado,
                +detalle.cantidad,
                +detalle.costo_unitario,
                +detalle.costo_unitario_servicio,
                +detalle.total
            );
        });

        this.calculateTotals();
    }

    focusFirstField() {
        document.getElementById('producto_nombre').focus();
        
        const modalEl = this.modal._element;

        modalEl.addEventListener('shown.bs.modal', () => {
            const input = document.getElementById('producto_nombre');
            if (input) input.focus();
        }, { once: true });
    }
    
    async registerSupplier() {
        // Recoge los datos del formulario
        const documento_tipo_codigo = document.getElementById('documento_tipo_codigo').value;
        const documento_numero = document.getElementById('documento_numero').value;
        const razon_social = document.getElementById('razon_social').value;

        if (!documento_tipo_codigo || !razon_social) {
            this.showNotification('warning', 'Completa todos los campos obligatorios de cliente');
            return;
        }
        try {
            const url = "{{ route('proveedores.store') }}";
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

            if (response.ok && data.success && data.proveedor) {
                // Asigna los datos al formulario principal
                document.getElementById('proveedor_id').value = data.proveedor.id;
                document.getElementById('proveedor_razon_social').value = data.proveedor.id +' - '+data.proveedor.razon_social;

                // Cambia a la tab de "Buscar Cliente"
                new bootstrap.Tab(document.getElementById('nav-buscar-tab')).show();
                this.showNotification('success', 'Proveedor registrado correctamente');
                document.getElementById('documento_numero').value = '';
                document.getElementById('razon_social').value = '';
            } 
            else if (response.status === 422) {
                this.handleFormErrors({ status: 422, data }); 
            } else {
                this.showNotification('error', data.message || 'Error al registrar proveedor');
            }
        } catch (error) {
            this.showNotification('error', 'Error de red al registrar proveedor');
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
                this.showNotification('error', (error.data && error.data.message) || 'Error al procesar la solicitud');
            }
        }
    }

    reindexDetalles() {
        const tbody = document.querySelector('#tablaDetalles tbody');
        if (!tbody) return;

        [...tbody.querySelectorAll('tr')].forEach((tr, index) => {
            const newIndex = index + 1;

            // 🔢 Actualizar columna #
            tr.children[1].textContent = newIndex;

            // 🔁 Actualizar todos los name="detalles[x][campo]"
            tr.querySelectorAll('input[name^="detalles["]').forEach(input => {
                input.name = input.name.replace(/detalles\[\d+\]/, `detalles[${newIndex}]`);
            });
        });
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

    async getSerie(codigo) {
        // Si no es NC, limpiar y no consultar
        if (!codigo || codigo !== 'NC') {
            document.getElementById('serie').value = '';
            document.getElementById('correlativo').value = '';
            return;
        }

        try {
            const urlSerie = "{{ route('compras.get-serie') }}";

            const response = await fetch(
                `${urlSerie}?comprobante_tipo_codigo=${codigo}`
            );

            const data = await response.json();

            // Si no hay datos, limpiar
            if (!data.serie || !data.numero) {
                document.getElementById('serie').value = '';
                document.getElementById('correlativo').value = '';
                return;
            }

            // Actualizar inputs
            document.getElementById('serie').value = data.serie;
            document.getElementById('correlativo').value = data.numero;

        } catch (error) {
            console.error('Error al obtener la serie y correlativo:', error);
        }
    }

} // Fin de la clase CompraManager

document.addEventListener('DOMContentLoaded', () => {
    // Agregar form submit handler para validar antes de enviar
    document.getElementById('formUpdate')?.addEventListener('submit', function(e) {
        const invalidInputs = this.querySelectorAll('.is-invalid');
        if (invalidInputs.length > 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'Por favor corrija los errores antes de guardar'
            });
        }
    });

    const compraManager = new CompraManager();

    // Delegación de evento: Rectificar compra (solo visible en compras anuladas)
    document.body.addEventListener('click', function(e) {
        if (e.target && (e.target.matches('.btn-rectificar-compra') || e.target.closest('.btn-rectificar-compra'))) {
            const button = e.target.closest('.btn-rectificar-compra');
            const compraId = button.getAttribute('data-id');
            if (!compraId) return;
            compraManager.showRectifyModal(compraId);
        }
    });

    // Delegación de evento: Ver compra
    document.body.addEventListener('click', function(e) {
        if (e.target && (e.target.matches('.btn-view-compra') || e.target.closest('.btn-view-compra'))) {
            const button = e.target.closest('.btn-view-compra');
            const compraId = button.getAttribute('data-id');
            if (!compraId) return;

            const url = "{{ route('compras.ver', ':id') }}".replace(':id', compraId);

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('No se pudo cargar la compra');
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
    });

    // Delegación de evento: Anular compra
    document.body.addEventListener('click', async function(e) {
        if (e.target && (e.target.matches('.btn-anular-compra') || e.target.closest('.btn-anular-compra'))) {
            const button = e.target.closest('.btn-anular-compra');
            const compraId = button.getAttribute('data-id');
            if (!compraId) return;

            // Confirmación con SweetAlert
            const result = await Swal.fire({
                title: '¿Anular esta compra?',
                text: 'Se revertirá el stock y se recalculará el CPP. Esta acción no se puede deshacer.',
                input: 'textarea',
                inputLabel: 'Motivo (opcional)',
                inputPlaceholder: 'Puede describir el motivo o dejarlo vacío',
                inputAttributes: { maxlength: 500 },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar'
            });

            if (!result.isConfirmed) return;

            try {
                const url = "{{ route('compras.anular', ':id') }}".replace(':id', compraId);
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ motivo: String(result.value).trim() })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Compra anulada',
                        text: data.message,
                        timer: 3000,
                        timerProgressBar: true
                    });
                    // Recargar DataTable
                    compraManager.tabla.ajax.reload(null, false);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo anular la compra'
                    });
                }
            } catch (error) {
                console.error('Error al anular:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de red',
                    text: 'No se pudo conectar con el servidor'
                });
            }
        }
    });
});

document.getElementById('mnuIngreso').classList.add('menu-open');
document.getElementById('itemCompras').classList.add('active');
</script>
<script src="{{asset('js/detallesFlecha.js')}}"></script>

@endpush
