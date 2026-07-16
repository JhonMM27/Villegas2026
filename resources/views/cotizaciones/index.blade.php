@extends('plantilla.app')
@push('estilos')
<style>
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type=number] {
        -moz-appearance: textfield;
    }
</style>
@endpush
@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Cotizaciones</h3>
                    @can('cotizaciones_create')
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
@canany(['cotizaciones_create', 'cotizaciones_edit'])
    @include('cotizaciones.action')
@endcanany
<div id="modalContainer"></div>
@endsection
@push('scripts')
<script>
class CotizacionManager extends CrudManager {
    constructor() {
        super("{{ url('cotizaciones') }}");
        this.afterSuccess = this.handleCotizacionSuccess.bind(this);
        this.initializeDataTable();

        this.populateSelect('documento_tipo_codigo', '{{ route("documento-tipos.select") }}', item =>
            `<option value="${item.codigo}">${item.codigo} - ${item.descripcion}</option>`
        );

        this.populateSelect(
            'comprobante_tipo_codigo',
            '{{ route("comprobante-tipos.select") }}?tipo=cotizaciones',
            item => `<option value="${item.codigo}">${item.codigo} - ${item.descripcion}</option>`
        );

        this.populateSelect('pago_forma_codigo', '{{ route("pago-formas.select") }}', item =>
            `<option value="${item.codigo}" data-dias="${item.dias}">${item.codigo} - ${item.descripcion}</option>`
        );

        this.setupLiveSearchSelect({
            inputId: 'producto_nombre',
            hiddenId: 'producto_id',
            url: "{{ route('productos.buscar') }}",
            template: (item) => {
                return item.id
                    ? `${item.id} - ${item.nombre} (S/ ${item.costo_unitario}) - Stock: ${item.stock_almacen}`
                    : `${item.nombre} (S/ ${item.costo_unitario}) - Stock: ${item.stock_almacen}`;
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

        document.getElementById('btnRegistrarCliente').addEventListener('click', () => this.registerCliente());

        // Evento para el select de comprobante_tipo_codigo
        const selectComprobante = document.getElementById('comprobante_tipo_codigo');
        if (selectComprobante) {
            selectComprobante.addEventListener('change', (e) => {
                this.getSerie(e.target.value); // Ahora sí llama al método de la clase
            });
        }

        // Recalcular fecha de vencimiento al cambiar forma de pago
        document.getElementById('pago_forma_codigo')
            ?.addEventListener('change', () => this.calcularFechaVencimiento());

        // Recalcular si cambia la fecha de venta
        document.getElementById('fecha_cotizacion')
            ?.addEventListener('change', () => this.calcularFechaVencimiento());
        
        
    }

    async handleCotizacionSuccess(response, isEditing) {
        // Solo mostrar opción de ticket para compras NUEVAS (no ediciones)
        if (!isEditing && response.cotizacion_id) {
            setTimeout(() => {
                this.showTicketOption(response);
            }, 1000); // Esperar 1 segundo para que se vea la notificación de registro primero
        }
    }

    async showTicketOption(response){
        const result = await Swal.fire({
            title: 'Cotización registrada!',
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
            const imprimirRuta = "{{ route('cotizaciones.imprimir', ['id' => ':id']) }}";
            window.open(imprimirRuta.replace(':id', response.cotizacion_id), '_blank');
        }
    }

    addProductoToTable(item, cantidad = 1, precio_unitario = null, subtotal = null) {
        //console.log('Agregando producto:', item);
        
        const tbody = document.querySelector('#tablaDetalles tbody');
        document.getElementById('producto_id').value = '';
        document.getElementById('producto_nombre').value = '';
        

        // Determinar el precio unitario: si no se pasa, usar costo_unitario del item
        // Determinar el precio unitario: si no se pasa, usar costo_unitario del item
        const fracciones = item.fracciones || [];

        // buscar fracción por unidad actual
        const fraccionActual = fracciones.find(f => f.unidad_codigo === item.unidad_codigo);

        // precio inicial: fracción > costo unitario
        const precioInicial = fraccionActual
            ? parseFloat(fraccionActual.precio_lista)
            : parseFloat(item.costo_unitario) || 0;

        // empaque inicial
        const empaqueInicial = fraccionActual
            ? parseFloat(fraccionActual.empaque)
            : parseFloat(item.empaque) || 1;
            
        const precioConImpuesto = precio_unitario != null
            ? parseFloat(precio_unitario)
            : precioInicial;

        //console.log('precioConImpuesto:', precioConImpuesto);

        // Verificar si el producto ya existe en la tabla
        const existingRow = [...tbody.querySelectorAll('tr')].find(row => row.dataset.productoId == item.id);

        if (existingRow) {
            const inputCantidad = existingRow.querySelector('.inputCantidad');
            const nuevaCantidad = parseInt(inputCantidad.value) + cantidad;
            inputCantidad.value = nuevaCantidad;

            const precio = parseFloat(existingRow.querySelector('.inputPrecioUnitario').value) || precioConImpuesto;
            existingRow.querySelector('.inputSubtotal').value = (precio * nuevaCantidad).toFixed(2);

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

            tr.innerHTML = `
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm btnEliminarFila">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
                <td class="text-center">${rowCount}</td>
                <td>(${item.id}) ${item.nombre || ''}</td>
                <td>${Number(item.costo_unitario || 0).toFixed(2)}</td>
                <td>${Number(item.stock_almacen || 0).toFixed(2)}</td>
                <td>${this.buildUnidadSelect(item.fracciones, item.unidad_codigo)}</td>
                <td>${item.empaque || ''}</td>
                <td>
                    <input type="number" name="detalles[${rowCount}][cantidad]" value="${cantidad}" step="any" class="form-control form-control-sm inputCantidad">
                </td>
                <td>
                    <input type="number" name="detalles[${rowCount}][cantidad_kgm]" value="${cantidad*item.empaque}" step="any" class="form-control form-control-sm inputCantidadKgm">
                </td>
                <td class="text-end">
                    <input type="number" name="detalles[${rowCount}][precio_unitario]" value="${precioConImpuesto.toFixed(4)}" step="0.0001" class="form-control form-control-sm inputPrecioUnitario">
                </td>
                <td class="text-end">
                    <input type="number" name="detalles[${rowCount}][total]" value="${sub.toFixed(2)}" step="0.01" class="form-control form-control-sm inputSubtotal">
                </td>

                <!-- Solo estos inputs viajan al backend -->
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
                    const nuevoPrecio  = parseFloat(option.dataset.precio) || 0;

                    // actualizar hidden inputs (si existen)
                    const inputUnidad  = tr.querySelector('input[name*="[unidad_codigo]"]');
                    const inputEmpaque = tr.querySelector('input[name*="[empaque]"]');
                    if (inputUnidad)  inputUnidad.value = option.value;
                    if (inputEmpaque) inputEmpaque.value = nuevoEmpaque;

                    tr.dataset.empaque = nuevoEmpaque;

                    // actualizar columna empaque
                    tr.children[4].textContent = nuevoEmpaque;

                    const inputCantidad    = tr.querySelector('.inputCantidad');
                    const inputCantidadKgm = tr.querySelector('.inputCantidadKgm');
                    const inputPrecio      = tr.querySelector('.inputPrecioUnitario');
                    const inputEntrega     = tr.querySelector('.inputEntrega'); // en cotización probablemente NO existe

                    const cantidad = parseFloat(inputCantidad.value) || 0;

                    inputPrecio.value = nuevoPrecio.toFixed(4);
                    inputCantidadKgm.value = (cantidad * nuevoEmpaque).toFixed(2);

                    // ✅ SOLO si existe
                    if (inputEntrega) inputEntrega.value = cantidad.toFixed(2);

                    tr.querySelector('.inputSubtotal').value = (cantidad * nuevoPrecio).toFixed(2);

                    this.calculateTotals();
                });

            }

            // Eventos
            //tr.querySelector('.inputCantidad').addEventListener('change', () => this.calculateTotals());
            tr.querySelector('.inputCantidad').addEventListener('input', (e) => {
                const cantidad = parseFloat(e.target.value) || 0;

                // Buscar inputEntrega dentro de la MISMA fila
                const inputEntrega = tr.querySelector('.inputEntrega');

                if (inputEntrega) {
                    inputEntrega.value = cantidad.toFixed(2);
                }

                // Recalcular subtotal al cambiar cantidad
                const precioUnitario = parseFloat(tr.querySelector('.inputPrecioUnitario').value) || 0;
                tr.querySelector('.inputSubtotal').value = (cantidad * precioUnitario).toFixed(2);

                this.calculateTotals();
            });
            tr.querySelector('.inputPrecioUnitario').addEventListener('input', (e) => {
                const precioUnitario = parseFloat(e.target.value) || 0;
                const cantidad = parseFloat(tr.querySelector('.inputCantidad').value) || 0;
                tr.querySelector('.inputSubtotal').value = (cantidad * precioUnitario).toFixed(2);
                this.calculateTotals();
            });
            tr.querySelector('.inputSubtotal').addEventListener('input', (e) => {
                const subtotal = parseFloat(e.target.value) || 0;
                const cantidad = parseFloat(tr.querySelector('.inputCantidad').value) || 0;
                if (cantidad !== 0) {
                    tr.querySelector('.inputPrecioUnitario').value = (subtotal / cantidad).toFixed(4);
                }
                this.calculateTotals();
            });

            tr.querySelector('.btnEliminarFila').addEventListener('click', () => {
                tr.remove();
                this.reindexDetalles();   // 🔑 CLAVE
                this.calculateTotals();
            });

            // Actualizar cantidad
            const inputCantidadKgm = tr.querySelector('input[name*="cantidad_kgm"]');
            inputCantidadKgm.addEventListener('change', (e) => {
                const empaque = parseFloat(tr.dataset.empaque || 1);
                const cantidadKgm = parseFloat(e.target.value) || 0;
                const inputCantidad = tr.querySelector('.inputCantidad');
                const inputEntrega = tr.querySelector('.inputEntrega');
                
                // Calcular cantidad dividiendo kilos entre empaque
                const nuevaCantidad = empaque > 0 ? (cantidadKgm / empaque) : 0;
                inputCantidad.value = nuevaCantidad.toFixed(2);
                if (inputEntrega) inputEntrega.value = nuevaCantidad.toFixed(2);

                // Recalcular subtotal
                const precioUnitario = parseFloat(tr.querySelector('.inputPrecioUnitario').value) || 0;
                tr.querySelector('.inputSubtotal').value = (nuevaCantidad * precioUnitario).toFixed(2);
                
                this.calculateTotals();
            });
            const allInputs = tr.querySelectorAll('input[type="number"]');
            allInputs.forEach(input => this.setupInputErrorClear(input));

            tbody.appendChild(tr);
        }

        this.calculateTotals();
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

            // Actualizar cantidad_kgm
            const inputCantidadKgm = row.querySelector('input[name*="cantidad_kgm"]');
            if (inputCantidadKgm) {
                inputCantidadKgm.value = (cantidad * empaque).toFixed(2);
            }

            // Leer subtotal desde el input (puede haber sido editado manualmente)
            const inputSubtotalEl = row.querySelector('.inputSubtotal');
            const itemSubtotal = parseFloat(inputSubtotalEl?.value) || (cantidad * precioUnitario);
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
                { data: 'fecha_cotizacion', name: 'fecha_cotizacion' },
                { data: 'pago_forma_nombre', name: 'pago_forma_nombre' },
                { data: 'cliente_nombre', name: 'cliente_nombre' },
                { data: 'comprobante_tipo_nombre', name: 'comprobante_tipo_nombre' },
                { data: 'serie', name: 'serie' },
                { data: 'correlativo', name: 'correlativo' },
                { data: 'total', name: 'total' },
                { data: 'estado', name: 'estado' }
            ],
            columnDefs: [
                { targets: 0, width: '10%', className: 'text-center' },
                { targets: 1, width: '15%' },
                { targets: 2, width: '10%' },
                { targets: 3, width: '10%' },
                { targets: 4, width: '10%' },
                { targets: 5, width: '5%' },
                { targets: 6, width: '10%' },
                { targets: 7, width: '10%' },
                { targets: 8, width: '10%' },
                { targets: 9, width: '10%' }
            ],
            responsive: true
        });
    }

    showCreateModal(){
        super.showCreateModal();

        this.elements.modalTitle.textContent = 'Nueva Cotización';

        document.querySelector('#tablaDetalles tbody').innerHTML = '';
        document.getElementById('documento_tipo_codigo').value = '01';
        document.getElementById('cliente_id').value = '';
        document.getElementById('cliente_razon_social').value = '';
        document.getElementById('comprobante_tipo_codigo').value = 'CZ';
        document.getElementById('pago_forma_codigo').value = '1';
        this.setFieldValue('fecha_cotizacion', this.obtenerFechaHoraActual());
        const usuarioNombre = @json(auth()->user()->name);
        document.getElementById('usuario_nombre').textContent = usuarioNombre;
        this.getSerie('CZ');
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
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);
            
            this.isEditing = true;
            this.resetForm();
            
            this.elements.modalTitle.textContent = 'Editar Cotización: '+ response.comprobante_tipo_codigo + ' ' + response.serie + '-' + response.correlativo
            this.elements.methodField.value = 'PUT';
            
            // Llenar campos específicos
            // Llenar campos principales del modal
            //console.log(response);
            document.getElementById('pago_forma_codigo').value = response.pago_forma_codigo || '';
            document.getElementById('comprobante_tipo_codigo').value = response.comprobante_tipo_codigo || '';
            document.getElementById('serie').value = response.serie || '';
            document.getElementById('correlativo').value = response.correlativo || '';
            document.getElementById('cliente_id').value = response.cliente_id || '';
            document.getElementById('cliente_razon_social').value = response.cliente_nombre || '';
            this.setFieldValue('fecha_cotizacion', this.formatDateTimeLocal(response.fecha_cotizacion));

            document.getElementById('usuario_nombre').textContent = response.user_nombre|| '';

            // Llenar tabla de detalles (productos)
            this.updateDetailsTable(response.detalles);

            // Llenar totales
            document.getElementById('op_gravada').value = parseFloat(response.op_gravada).toFixed(2);
            document.getElementById('op_exonerada').value = parseFloat(response.op_exonerada).toFixed(2);
            document.getElementById('op_inafecta').value = parseFloat(response.op_inafecta).toFixed(2);
            document.getElementById('impuesto').value = parseFloat(response.impuesto).toFixed(2);
            document.getElementById('total').value = parseFloat(response.total).toFixed(2);

            this.form.action = `${this.baseUrl}/${id}`;
            
            this.modal.show();
            
        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error('Error al cargar datos:', error);
        }
    }

    async duplicateShowModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);

            this.isEditing = false;
            this.resetForm();

            this.elements.modalTitle.textContent =
                'Duplicar cotización ' + response.comprobante_tipo_codigo + ' ' + response.serie + '-' + response.correlativo;

            this.elements.methodField.value = 'POST';

            document.getElementById('pago_forma_codigo').value = response.pago_forma_codigo || '';
            document.getElementById('comprobante_tipo_codigo').value = response.comprobante_tipo_codigo || '';
            this.getSerie(response.comprobante_tipo_codigo);
            document.getElementById('cliente_id').value = response.cliente_id || '';
            document.getElementById('cliente_razon_social').value = response.cliente_nombre || '';
            this.setFieldValue('fecha_cotizacion', this.obtenerFechaHoraActual());
            document.getElementById('usuario_nombre').textContent = response.user_nombre|| '';

            // Llenar tabla de detalles (productos)
            this.updateDetailsTable(response.detalles);

            // Llenar totales
            document.getElementById('op_gravada').value = parseFloat(response.op_gravada).toFixed(2);
            document.getElementById('op_exonerada').value = parseFloat(response.op_exonerada).toFixed(2);
            document.getElementById('op_inafecta').value = parseFloat(response.op_inafecta).toFixed(2);
            document.getElementById('impuesto').value = parseFloat(response.impuesto).toFixed(2);
            document.getElementById('total').value = parseFloat(response.total).toFixed(2);

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
            // Transformar al formato esperado
             const productoFormateado = {
                id: detalle.producto_id,
                codigo: producto.codigo ?? null,
                nombre: detalle.producto_nombre,

                // ✅ costo_unitario y stock_almacen para visualizacion
                costo_unitario: producto.costo_unitario ?? 0,
                stock_almacen: producto.stock_almacen ?? 0,

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
                }
            };
            /*
            const productoFormateado = {
                id: detalle.producto_id,
                codigo: detalle.producto?.codigo ?? null,
                nombre: detalle.producto_nombre,
                costo_unitario: detalle.precio_unitario,
                afectacion_tipo_codigo: detalle.producto?.afectacion_tipo_codigo,
                unidad_codigo: detalle.unidad_codigo,
                empaque: detalle.producto_empaque,
                afectacion_tipo: {
                    codigo: detalle.producto?.afectacion_tipo?.codigo,
                    porcentaje: detalle.producto?.afectacion_tipo?.porcentaje
                },
                unidad: {
                    codigo: detalle.unidad_codigo,
                    descripcion: detalle.unidad_nombre
                }
            };
            */
            // Enviar a la tabla con la estructura correcta
            this.addProductoToTable(
                productoFormateado,
                +detalle.cantidad,
                +detalle.precio_unitario,
                +detalle.total
            );
        });
    }


    focusFirstField() {
        document.getElementById('producto_nombre').focus();
        const modalEl = this.modal._element;

        modalEl.addEventListener('shown.bs.modal', () => {
            const input = document.getElementById('producto_nombre');
            if (input) input.focus();
        }, { once: true });
    }
    
    async registerCliente() {
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
        const urlSerie = "{{ route('cotizaciones.get-serie') }}";

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
    const cotizacionManager = new CotizacionManager();
    document.body.addEventListener('click', function(e) {
        // Event listener para ver venta desde cotización
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
                    modalContainer.querySelector('.btn-duplicate-venta')?.remove();
                    modal.show();
                })
                .catch(err => {
                    console.error(err);
                    alert('Ocurrió un error al cargar el detalle de la venta.');
                });
        }
        if (e.target && (e.target.matches('.btn-view-cotizacion') || e.target.closest('.btn-view-cotizacion'))) {
            const button = e.target.closest('.btn-view-cotizacion');
            const cotizacionId = button.getAttribute('data-id');
            if (!cotizacionId) return;

            const url = "{{ route('cotizaciones.ver', ':id') }}".replace(':id', cotizacionId);

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
        if (e.target && (e.target.matches('.btn-duplicate-cotizacion') || e.target.closest('.btn-duplicate-cotizacion'))) {
            const button = e.target.closest('.btn-duplicate-cotizacion');
            const cotizacionId = button.getAttribute('data-id');
            if (!cotizacionId) return;

            cotizacionManager.duplicateShowModal(cotizacionId);
        }
    });
});

document.getElementById('mnuSalida').classList.add('menu-open');
document.getElementById('itemCotizaciones').classList.add('active');
</script>
<script src="{{asset('js/detallesFlecha.js')}}"></script>
@endpush