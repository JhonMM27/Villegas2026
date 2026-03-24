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
                    <h3 class="card-title flex-grow-1">Ventas</h3>
                    @can('ventas_create')
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
                                    <th>Cliente</th>
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
@canany(['ventas_create', 'ventas_edit'])
    @include('ventas.action')
@endcanany
<div id="modalContainer"></div>
@endsection
@push('scripts')
<script>
class VentaManager extends CrudManager {
    constructor() {
        super("{{ url('ventas') }}");
        this.afterSuccess = this.handleVentaSuccess.bind(this);
        this.initializeDataTable();

        this.populateSelect('documento_tipo_codigo', '{{ route("documento-tipos.select") }}', item =>
            `<option value="${item.codigo}">${item.codigo} - ${item.descripcion}</option>`
        );

        this.populateSelect(
            'comprobante_tipo_codigo',
            '{{ route("comprobante-tipos.select") }}?tipo=ventas',
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
            inputId: 'cliente_razon_social',
            hiddenId: 'cliente_id',
            url: "{{ route('clientes.buscar') }}",
            template: (item) => {
                const doc = item.documento_numero;
                return doc
                    ? `${item.id} - ${item.razon_social} (${doc})`
                    : `${item.id} - ${item.razon_social}`;
            },
            minLength : 1,
            delay : 300,
        });

        document.getElementById('btnRegistrarCliente').addEventListener('click', () => this.registerSupplier());

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
        document.getElementById('pago_forma_codigo')
            ?.addEventListener('change', () => this.calcularFechaVencimiento());

        // Recalcular si cambia la fecha de venta
        document.getElementById('fecha_venta')
            ?.addEventListener('change', () => this.calcularFechaVencimiento());
        
        // Verificar si viene desde una cotización
        this.checkPendienteCotizacion();
    }

    async handleVentaSuccess(response, isEditing) {
        // Limpiar parámetro de URL si venía de una cotización
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('cotizacion_id')) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
        
        // Solo mostrar opción de ticket para compras NUEVAS (no ediciones)
        if (!isEditing && response.venta_id) {
            setTimeout(() => {
                this.showTicketOption(response);
            }, 1000); // Esperar 1 segundo para que se vea la notificación de registro primero
        }
    }

    async showTicketOption(response){
        const result = await Swal.fire({
            title: 'Venta registrada!',
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
            const imprimirRuta = "{{ route('ventas.imprimir', ['id' => ':id']) }}";
            window.open(imprimirRuta.replace(':id', response.venta_id), '_blank');
        }
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

    addProductoToTable(item, cantidad = 1, precio_unitario = null,entregado=null, subtotal = null) {
        //console.log('Agregando producto:', item);
        
        const tbody = document.querySelector('#tablaDetalles tbody');
        document.getElementById('producto_id').value = '';
        document.getElementById('producto_nombre').value = '';

        entregado = entregado ?? 1;
        

        // Determinar el precio unitario: si no se pasa, usar costo_unitario del item
        const fracciones = item.fracciones || [];

        // buscar fracción por unidad actual o usar la primera disponible
        const fraccionActual = fracciones.find(f => f.unidad_codigo === item.unidad_codigo) || fracciones[0];

        // En ventas: usar precio_lista de la fracción (aunque sea 0)
        // Solo si no hay fracciones, usar costo_unitario como último respaldo
        const precioInicial = fraccionActual
            ? parseFloat(fraccionActual.precio_lista ?? 0)
            : parseFloat(item.costo_unitario || 0);

        // empaque inicial
        const empaqueInicial = fraccionActual
            ? parseFloat(fraccionActual.empaque || 1)
            : parseFloat(item.empaque || 1) || 1;
            
        const precioConImpuesto = precio_unitario != null
            ? parseFloat(precio_unitario || 0)
            : precioInicial;

        //console.log('precioConImpuesto:', precioConImpuesto);

        // Verificar si el producto ya existe en la tabla
        const existingRow = [...tbody.querySelectorAll('tr')].find(row => row.dataset.productoId == item.id);

        if (existingRow) {
            const inputCantidad = existingRow.querySelector('.inputCantidad');
            const nuevaCantidad = parseInt(inputCantidad.value) + cantidad;
            inputCantidad.value = nuevaCantidad;

            const precio = parseFloat(existingRow.querySelector('.inputPrecioUnitario').value) || precioConImpuesto;
            const inputTotal = existingRow.querySelector('.inputTotal');
            if (inputTotal) {
                inputTotal.value = (precio * nuevaCantidad).toFixed(2);
            }
            this.calculateTotals();

        } else {
            const rowCount = tbody.rows.length + 1;
            const porcentaje = parseFloat(item.afectacion_tipo?.porcentaje || 0);
            const sub = subtotal ?? (precioConImpuesto * cantidad);
            const valorUnitario = precioConImpuesto / (1 + porcentaje);
            const impuesto = precioConImpuesto - valorUnitario;
            
            const unidadDet = item.unidad.descripcion  ?? item.unidad_nombre;

            const tr = document.createElement('tr');
            tr.dataset.productoId = item.id;
            tr.dataset.afectacionPorcentaje = porcentaje;
            tr.dataset.afectacionCodigo = item.afectacion_tipo_codigo || '10';
            tr.dataset.fracciones = JSON.stringify(item.fracciones || []);
            tr.dataset.empaque = item.empaque || 1;
            tr.dataset.totalManual = (item.total_manual === "1") ? "1" : "0";

            tr.innerHTML = `
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm btnEliminarFila">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
                <td class="text-center">${rowCount}</td>
                <td>(${item.id}) ${item.nombre || ''}</td>
                <td>${Number(item.costo_unitario).toFixed(2)}</td>
                <td>${Number(item.stock_almacen).toFixed(2)}</td>
                <td>${this.buildUnidadSelect(item.fracciones, item.unidad_codigo)}</td>
                <td class="tdEmpaque">${empaqueInicial || ''}</td>
                <td>
                    <input type="number" step="any" name="detalles[${rowCount}][cantidad]" value="${cantidad}" class="form-control form-control-sm inputCantidad">
                </td>
                <td>
                    <input type="number" step="any" name="detalles[${rowCount}][cantidad_kgm]" value="${cantidad*item.empaque}" class="form-control form-control-sm inputCantidadKgm">
                </td>
                <td class="text-end">
                    <input type="number" step="any" name="detalles[${rowCount}][precio_unitario]" value="${precioConImpuesto.toFixed(4)}" class="form-control form-control-sm inputPrecioUnitario">
                </td>
                <td class="text-end">
                    <input type="number" step="any" name="detalles[${rowCount}][entrega]" value="${entregado}" class="form-control form-control-sm inputEntrega">
                </td>
                <td class="text-end">
                    <input type="number" step="any" name="detalles[${rowCount}][total]" value="${sub.toFixed(2)}" class="form-control form-control-sm inputTotal">
                </td>

                <!-- Inputs ocultos que viajan al backend -->
                <input type="hidden" name="detalles[${rowCount}][producto_id]" value="${item.id}">
                <input type="hidden" name="detalles[${rowCount}][unidad_codigo]" value="${item.unidad_codigo}">
                <input type="hidden" name="detalles[${rowCount}][empaque]" value="${empaqueInicial}">
            `;

            //Select fracciones
            const selectUnidad = tr.querySelector('.selectUnidad');

            if (selectUnidad) {
                selectUnidad.addEventListener('change', (e) => {
                    const option = e.target.selectedOptions[0];

                    const nuevoEmpaque = parseFloat(option.dataset.empaque) || 1;
                    // En ventas usar precio_lista aunque sea 0
                    const nuevoPrecio = parseFloat(option.dataset.precio ?? 0);

                    // Actualizar hidden inputs
                    const inputUnidad = tr.querySelector('input[name*="[unidad_codigo]"]');
                    const inputEmpaque = tr.querySelector('input[name*="[empaque]"]');
                    if (inputUnidad) inputUnidad.value = option.value;
                    if (inputEmpaque) inputEmpaque.value = nuevoEmpaque;

                    // Actualizar dataset empaque
                    tr.dataset.empaque = nuevoEmpaque;

                    // Actualizar columna EMPAQUE (no stock)
                    const tdEmpaque = tr.querySelector('.tdEmpaque');
                    if (tdEmpaque) tdEmpaque.textContent = nuevoEmpaque;

                    const inputCantidad = tr.querySelector('.inputCantidad');
                    const inputCantidadKgm = tr.querySelector('.inputCantidadKgm');
                    const inputPrecio = tr.querySelector('.inputPrecioUnitario');
                    const inputEntrega = tr.querySelector('.inputEntrega');
                    const inputTotal = tr.querySelector('.inputTotal');

                    const cantidad = parseFloat(inputCantidad.value) || 0;

                    // Al cambiar unidad siempre se recalcula desde cero (modo automático)
                    tr.dataset.totalManual = "0";
                    inputPrecio.value = nuevoPrecio.toFixed(2);
                    inputCantidadKgm.value = (cantidad * nuevoEmpaque).toFixed(2);
                    if (inputEntrega) inputEntrega.value = cantidad.toFixed(2);
                    inputTotal.value = (cantidad * nuevoPrecio).toFixed(2);

                    this.calculateTotals();
                });
            }


            // Eventos
            const inputCantidadObj = tr.querySelector('.inputCantidad');
            const inputPrecioObj = tr.querySelector('.inputPrecioUnitario');
            const inputTotalObj = tr.querySelector('.inputTotal');

            const recalcularFilaAuto = () => {
                const cant = parseFloat(inputCantidadObj.value) || 0;
                const prec = parseFloat(inputPrecioObj.value) || 0;
                inputTotalObj.value = (cant * prec).toFixed(2);
                this.calculateTotals();
            };

            inputCantidadObj.addEventListener('input', (e) => {
                tr.dataset.totalManual = "0";
                const cantidad = parseFloat(e.target.value) || 0;
                const empaque = parseFloat(tr.dataset.empaque || 1);
                const inputEntrega = tr.querySelector('.inputEntrega');
                const inputCantidadKgm = tr.querySelector('.inputCantidadKgm');
                
                if (inputEntrega) {
                    inputEntrega.value = cantidad.toFixed(2);
                }
                if (inputCantidadKgm) {
                    inputCantidadKgm.value = (cantidad * empaque).toFixed(2);
                }
                recalcularFilaAuto();
            });

            inputPrecioObj.addEventListener('input', () => {
                tr.dataset.totalManual = "0";
                recalcularFilaAuto();
            });

            inputTotalObj.addEventListener('input', (e) => {
                tr.dataset.totalManual = "1"; // Pasa a manual
                const cant = parseFloat(inputCantidadObj.value) || 0;
                const tot = parseFloat(e.target.value) || 0;
                if (cant > 0) {
                    inputPrecioObj.value = (tot / cant).toFixed(4);
                }
                this.calculateTotals();
            });

            tr.querySelector('.btnEliminarFila').addEventListener('click', () => {
                tr.remove();
                this.reindexDetalles();   // 🔑 CLAVE
                this.calculateTotals();
            });

            // Actualizar cantidad
            const inputCantidadKgm = tr.querySelector('.inputCantidadKgm');
            inputCantidadKgm.addEventListener('input', (e) => {
                const empaque = parseFloat(tr.dataset.empaque || 1);
                const cantidadKgm = parseFloat(e.target.value) || 0;
                const inputEntrega = tr.querySelector('.inputEntrega');
                
                // Calcular cantidad dividiendo kilos entre empaque
                const nuevaCantidad = empaque > 0 ? (cantidadKgm / empaque) : 0;
                inputCantidadObj.value = nuevaCantidad.toFixed(2);
                if (inputEntrega) {
                    inputEntrega.value = nuevaCantidad.toFixed(2);
                }
                
                tr.dataset.totalManual = "0";
                recalcularFilaAuto();
            });
            const allInputs = tr.querySelectorAll('input[type="number"]');
            allInputs.forEach(input => this.setupInputErrorClear(input));

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
        this.updateCobranza();
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
                { data: 'user_nombre', name: 'user_nombre' },
                { data: 'fecha_venta', name: 'fecha_venta' },
                { data: 'pago_forma_nombre', name: 'pago_forma_nombre' },
                { data: 'cliente_nombre', name: 'cliente_nombre' },
                { data: 'comprobante_tipo_nombre', name: 'comprobante_tipo_nombre' },
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
                { targets: 3, width: '5%' },
                { targets: 4, width: '5%' },
                { targets: 5, width: '5%' },
                { targets: 6, width: '5%' },
                { targets: 7, width: '10%' },
                { targets: 8, width: '10%' },
                { targets: 9, width: '10%' },
                { targets: 10, width: '10%' },
                { targets: 11, width: '10%' }
            ],
            responsive: true
        });
    }

    showCreateModal(){
        super.showCreateModal();
        this.bindPagoFormaChange();
        this.bindCobranzaInputs();
        this.elements.modalTitle.textContent = 'Nueva Venta';

        document.querySelector('#tablaDetalles tbody').innerHTML = '';
        document.getElementById('documento_tipo_codigo').value = '01';
        document.getElementById('cliente_id').value = '';
        document.getElementById('cliente_razon_social').value = '';
        document.getElementById('comprobante_tipo_codigo').value = 'NP';
        document.getElementById('pago_forma_codigo').value = '1';
        document.getElementById('cobranza_tipo_id').value = '1';
        document.getElementById('fecha_venta').value = this.obtenerFechaHoraActual();
        document.getElementById('fecha_vencimiento').value = this.obtenerFechaActual();
        const usuarioNombre = @json(auth()->user()->name);
        document.getElementById('usuario_nombre').textContent = usuarioNombre;
        this.getSerie('NP');
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
        const fechaVentaInput = document.getElementById('fecha_venta');
        const fechaVencInput = document.getElementById('fecha_vencimiento');

        if (!select || !fechaVentaInput || !fechaVentaInput.value) return;

        const option = select.options[select.selectedIndex];
        const dias = parseInt(option.dataset.dias || 0);

        const fechaVenta = new Date(fechaVentaInput.value);
        fechaVenta.setDate(fechaVenta.getDate() + dias);

        const yyyy = fechaVenta.getFullYear();
        const mm = String(fechaVenta.getMonth() + 1).padStart(2, '0');
        const dd = String(fechaVenta.getDate()).padStart(2, '0');

        fechaVencInput.value = `${yyyy}-${mm}-${dd}`;
    }

    checkPendienteCotizacion() {
        const urlParams = new URLSearchParams(window.location.search);
        const cotizacionId = urlParams.get('cotizacion_id');
        
        if (cotizacionId) {
            this.cargarDatosDeCotizacion(cotizacionId);
        }
    }

    async cargarDatosDeCotizacion(cotizacionId) {
        try {
            const response = await this.fetchData(`{{ url('cotizaciones') }}/${cotizacionId}`);
            
            // Abrir modal de nueva venta
            this.showCreateModal();
            
            // Llenar datos del cliente
            document.getElementById('cliente_id').value = response.cliente_id || '';
            document.getElementById('cliente_razon_social').value = response.cliente_id + ' - ' + response.cliente_nombre || '';
            
            // Llenar forma de pago
            document.getElementById('pago_forma_codigo').value = response.pago_forma_codigo || '';
            
            // Guardar referencia a la cotización
            document.getElementById('cotizacion_ref_id').value = cotizacionId;

            const detallesMapeados = response.detalles.map(detalle => ({
                ...detalle,
                entregado: detalle.cantidad  // La cantidad de cotización se asigna a entrega
            }));
            
            // Cargar detalles de productos
            this.updateDetailsTable(detallesMapeados);
            
            // Mensaje informativo
            this.showNotification('info', 'Datos cargados desde cotización #' + cotizacionId);
            
        } catch (error) {
            this.showNotification('error', 'Error al cargar datos de la cotización');
            console.error('Error:', error);
        }
    }

    bindPagoFormaChange() {
        const select = document.getElementById('pago_forma_codigo');
        if (!select) return;

        // ✅ para que no se duplique el listener cada vez que abres el modal
        if (select.dataset.boundCobranza === '1') return;
        select.dataset.boundCobranza = '1';

        select.addEventListener('change', () => {
            this.updateCobranza();
        });
    }

    updateCobranza(tipo = null) {
        const pagoForma = document.getElementById('pago_forma_codigo')?.value;

        const depositoEl = document.getElementById('deposito');
        const principalEl = document.getElementById('principal');
        const consorcioEl = document.getElementById('consorcio');
        const totalCobranzaEl = document.getElementById('total_cobranza');

        // 🔴 SI NO ES FORMA 1 → TODO EN CERO
        if (pagoForma !== '1') {
            depositoEl.value = '0.00';
            principalEl.value = '0.00';
            consorcioEl.value = '0.00';
            totalCobranzaEl.value = '0.00';
            return;
        }

        // 🟢 SI ES 1 → aplicar lógica normal
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
    }

    async showEditModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);
            
            this.isEditing = true;
            this.resetForm();
            this.bindPagoFormaChange();
            this.bindCobranzaInputs();
            
            this.elements.modalTitle.textContent = 'Editar Venta: '+ response.comprobante_tipo_codigo + ' ' + response.serie + '-' + response.correlativo
            this.elements.methodField.value = 'PUT';
            
            // Llenar campos específicos
            // Llenar campos principales del modal
            //console.log(response);
            document.getElementById('pago_forma_codigo').value = response.pago_forma_codigo || '';
            document.getElementById('comprobante_tipo_codigo').value = response.comprobante_tipo_codigo || '';
            document.getElementById('serie').value = response.serie || '';
            document.getElementById('correlativo').value = response.correlativo || '';
            document.getElementById('cliente_id').value = response.cliente_id || '';
            document.getElementById('cliente_razon_social').value = response.cliente_id +' - '+ response.cliente_nombre || '';
            document.getElementById('fecha_venta').value =
                this.formatDateTimeLocal(response.fecha_venta);

            document.getElementById('fecha_vencimiento').value =response.fecha_vencimiento;
            document.getElementById('usuario_nombre').textContent = response.user_nombre|| '';
            //document.getElementById('acuenta').value = parseFloat(response.acuenta).toFixed(2);
            //document.getElementById('saldo').value = parseFloat(response.saldo).toFixed(2);
            document.getElementById('docpagoi').value = response.docpagoi || '';
            this.aplicarCobranza(response.cobranza_tipo_id, response.acuenta);
            // Llenar tabla de detalles (productos)
            this.updateDetailsTable(response.detalles);

            // Llenar totales
            document.getElementById('op_gravada').value = parseFloat(response.op_gravada).toFixed(2);
            document.getElementById('op_exonerada').value = parseFloat(response.op_exonerada).toFixed(2);
            document.getElementById('op_inafecta').value = parseFloat(response.op_inafecta).toFixed(2);
            document.getElementById('impuesto').value = parseFloat(response.impuesto).toFixed(2);
            document.getElementById('total').value = parseFloat(response.total).toFixed(2);

            document.getElementById('principal').value = parseFloat(response.importe_p).toFixed(2);
            document.getElementById('deposito').value = parseFloat(response.importe_d).toFixed(2);
            document.getElementById('consorcio').value = parseFloat(response.importe_c).toFixed(2);
            document.getElementById('total_cobranza').value = (
                                                                Number(response.importe_p) + Number(response.importe_d) + Number(response.importe_c)
                                                            ).toFixed(2);

            this.form.action = `${this.baseUrl}/${id}`;
            
            this.modal.show();
            
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error('Error al cargar datos:', error);
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
        recalcularTotalCobranza();
    }

    bindCobranzaInputs() {
        ['deposito', 'principal', 'consorcio'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
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

        // 🚨 VALIDACIÓN
        if (totalCobranza > total) {
            this.showNotification('error', 'El total de la cobranza no puede ser mayor al total del documento');
            totalCobranzaEl.classList.add('is-invalid');
        } else {
            totalCobranzaEl.classList.remove('is-invalid');
        }
    }


    async showRectifyModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);

            this.isEditing = false;
            this.resetForm();

            this.elements.modalTitle.textContent =
                'Rectificar venta ' + response.comprobante_tipo_codigo + ' ' + response.serie + '-' + response.correlativo;

            this.elements.methodField.value = 'POST';

            document.getElementById('pago_forma_codigo').value = response.pago_forma_codigo || '';
            document.getElementById('comprobante_tipo_codigo').value = response.comprobante_tipo_codigo || '';
            this.getSerie(response.comprobante_tipo_codigo);
            document.getElementById('cliente_id').value = response.cliente_id || '';
            document.getElementById('cliente_razon_social').value = response.cliente_nombre || '';
            document.getElementById('fecha_venta').value = this.obtenerFechaHoraActual();
            document.getElementById('fecha_vencimiento').value = this.obtenerFechaActual();
            document.getElementById('usuario_nombre').textContent = response.user_nombre|| '';
            this.aplicarCobranza(response.cobranza_tipo_id, response.acuenta);

            // Llenar tabla de detalles (productos)
            this.updateDetailsTable(response.detalles);

            // Llenar totales
            document.getElementById('op_gravada').value = parseFloat(response.op_gravada).toFixed(2);
            document.getElementById('op_exonerada').value = parseFloat(response.op_exonerada).toFixed(2);
            document.getElementById('op_inafecta').value = parseFloat(response.op_inafecta).toFixed(2);
            document.getElementById('impuesto').value = parseFloat(response.impuesto).toFixed(2);
            document.getElementById('total').value = parseFloat(response.total).toFixed(2);

            document.getElementById('principal').value = parseFloat(response.importe_p).toFixed(2);
            document.getElementById('deposito').value = parseFloat(response.importe_d).toFixed(2);
            document.getElementById('consorcio').value = parseFloat(response.importe_c).toFixed(2);
            document.getElementById('total_cobranza').value = (
                                                    Number(response.importe_p) + Number(response.importe_d) + Number(response.importe_c)
                                                ).toFixed(2);

            // ⚠️ La ruta de acción es RECTIFICAR
            this.form.action = `${this.baseUrl}/${id}/rectificar`;

            this.modal.show();

        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos para rectificar');
            console.error(error);
        }
    }

    async duplicateShowModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);

            this.isEditing = false;
            this.resetForm();

            this.elements.modalTitle.textContent =
                'Duplicar venta ' + response.comprobante_tipo_codigo + ' ' + response.serie + '-' + response.correlativo;

            this.elements.methodField.value = 'POST';

            document.getElementById('pago_forma_codigo').value = response.pago_forma_codigo || '';
            document.getElementById('comprobante_tipo_codigo').value = response.comprobante_tipo_codigo || '';
            this.getSerie(response.comprobante_tipo_codigo);
            document.getElementById('cliente_id').value = response.cliente_id || '';
            document.getElementById('cliente_razon_social').value = response.cliente_nombre || '';
            document.getElementById('fecha_venta').value = this.obtenerFechaHoraActual();
            document.getElementById('fecha_vencimiento').value = this.obtenerFechaActual();
            document.getElementById('usuario_nombre').textContent = response.user_nombre|| '';
            this.aplicarCobranza(response.cobranza_tipo_id, response.acuenta);

            // Llenar tabla de detalles (productos)
            this.updateDetailsTable(response.detalles);

            // Llenar totales
            document.getElementById('op_gravada').value = parseFloat(response.op_gravada).toFixed(2);
            document.getElementById('op_exonerada').value = parseFloat(response.op_exonerada).toFixed(2);
            document.getElementById('op_inafecta').value = parseFloat(response.op_inafecta).toFixed(2);
            document.getElementById('impuesto').value = parseFloat(response.impuesto).toFixed(2);
            document.getElementById('total').value = parseFloat(response.total).toFixed(2);


            document.getElementById('principal').value = parseFloat(response.importe_p).toFixed(2);
            document.getElementById('deposito').value = parseFloat(response.importe_d).toFixed(2);
            document.getElementById('consorcio').value = parseFloat(response.importe_c).toFixed(2);
            document.getElementById('total_cobranza').value = (
                                                    Number(response.importe_p) + Number(response.importe_d) + Number(response.importe_c)
                                                ).toFixed(2);

            this.form.action = this.baseUrl; // POST nueva venta

            this.modal.show();

        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error(error);
        }
    }
  
    updateDetailsTable(detalles = []) {
        const tbody = document.querySelector('#tablaDetalles tbody');
        tbody.innerHTML = '';

        detalles.forEach(detalle => {

            const producto = detalle.producto || {};

            const productoFormateado = {
                id: detalle.producto_id,
                codigo: producto.codigo ?? null,
                nombre: detalle.producto_nombre,

                // ⚠️ Importante: costo_unitario SOLO como respaldo
                costo_unitario: detalle.costo_unitario || producto.costo_unitario || 0,
                stock_almacen: producto.stock_almacen || 0,

                // ✅ unidad seleccionada en la venta
                unidad_codigo: detalle.unidad_codigo,

                // ✅ empaque real usado en la venta
                empaque: detalle.producto_empaque,

                // ✅ fracciones COMPLETAS (clave)
                fracciones: producto.fracciones || [],

                afectacion_tipo_codigo: producto.afectacion_tipo_codigo,
                afectacion_tipo: {
                    codigo: producto.afectacion_tipo?.codigo,
                    porcentaje: parseFloat(producto.afectacion_tipo?.porcentaje || 0)
                },

                unidad: {
                    codigo: detalle.unidad_codigo,
                    descripcion: detalle.unidad_nombre
                },
                total_manual: "1"
            };

            // 🔁 Agregar fila con datos EXACTOS de la venta
            this.addProductoToTable(
                productoFormateado,
                parseFloat(detalle.cantidad),        // cantidad
                parseFloat(detalle.precio_unitario), // precio unitario
                parseFloat(detalle.entregado),       // entregado
                parseFloat(detalle.total)            // subtotal
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
                document.getElementById('cliente_razon_social').value= data.cliente.id + ' - ' + data.cliente.razon_social;

                // Cambia a la tab de "Buscar Cliente"
                new bootstrap.Tab(document.getElementById('nav-buscar-tab')).show();
                this.showNotification('success', 'Cliente registrado correctamente');
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

    async getSerie(codigo) {
        if (!codigo) return;
        const urlSerie = "{{ route('ventas.get-serie') }}";

        fetch(`${urlSerie}?comprobante_tipo_codigo=${codigo}`)
            .then(response => response.json())
            .then(data => {
                // Si no hay datos, limpiar
                if (!data.serie || !data.numero) {
                    document.getElementById('serie').value = '';
                    document.getElementById('correlativo').value = '';
                    return;
                }
                // Actualizar inputs
                document.getElementById('serie').value = data.serie;
                document.getElementById('correlativo').value = data.numero;
            })
            .catch(error => console.error('Error al obtener la serie y correlativo:', error));
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

    formatDateTimeLocal(fecha) {
        if (!fecha || fecha.startsWith('-000')) return '';

        // "2025-12-29 09:58:00" → "2025-12-29T09:58"
        return fecha.replace(' ', 'T').substring(0, 16);
    }

}
document.addEventListener('DOMContentLoaded', () => {
    const ventaManager = new VentaManager();
    document.body.addEventListener('click', function(e) {
        if (e.target && (e.target.matches('.btn-view-venta') || e.target.closest('.btn-view-venta'))) {
            const button = e.target.closest('.btn-view-venta');
            const ventaId = button.getAttribute('data-id');
            if (!ventaId) return;
            
            const url = "{{ route('ventas.ver', ':id') }}".replace(':id', ventaId);

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('No se pudo cargar la venta');
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

        if (e.target && (e.target.matches('.btn-rectificar-venta') || e.target.closest('.btn-rectificar-venta'))) {
            const button = e.target.closest('.btn-rectificar-venta');
            const ventaId = button.getAttribute('data-id');
            if (!ventaId) return;

            ventaManager.showRectifyModal(ventaId);
        }

        if (e.target && (e.target.matches('.btn-duplicate-venta') || e.target.closest('.btn-duplicate-venta'))) {
            const button = e.target.closest('.btn-duplicate-venta');
            const ventaId = button.getAttribute('data-id');
            if (!ventaId) return;

            ventaManager.duplicateShowModal(ventaId);
        }
        
        if (e.target && (e.target.matches('.btn-duplicate-venta') || e.target.closest('.btn-duplicate-venta'))) {
            const button = e.target.closest('.btn-duplicate-venta');
            const ventaId = button.getAttribute('data-id');
            if (!ventaId) return;

            // 🔴 CERRAR MODAL "VER"
            const modalVerEl = document.querySelector('#modalContainer .modal.show');
            if (modalVerEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalVerEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }

            // 🟢 ABRIR MODAL DUPLICAR
            ventaManager.duplicateShowModal(ventaId);
        }

        // ─── Botón Anular Venta ──────────────────────────────────
        if (e.target && (e.target.matches('.btn-anular-venta') || e.target.closest('.btn-anular-venta'))) {
            const button = e.target.closest('.btn-anular-venta');
            const ventaId = button.getAttribute('data-id');
            if (!ventaId) return;

            // Confirmación con SweetAlert
            Swal.fire({
                title: '¿Anular venta?',
                text: 'Esta acción revertirá el stock vendido y recalculará el CPP en cascada. No se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (!result.isConfirmed) return;

                const url = '{{ url("ventas") }}/' + ventaId + '/anular';
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
                        // Recargar DataTable para reflejar cambio de estado
                        ventaManager.tabla.ajax.reload();
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

document.getElementById('mnuSalida').classList.add('menu-open');
document.getElementById('itemVentas').classList.add('active');
</script>
<script src="{{asset('js/detallesFlecha.js')}}"></script>
@endpush