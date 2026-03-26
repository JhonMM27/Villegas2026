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
                        <h3 class="card-title flex-grow-1">Préstamos</h3>
                        @can('prestamos_create')
                            <button type="button" class="btn btn-primary" id="btnCreate">
                                <i class="bi bi-plus-circle"></i> Nuevo
                            </button>
                        @endcan
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" id="prestamoTabs">
                            <li class="nav-item">
                                <button class="nav-link active" data-tipo="PA">Préstamo A</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-tipo="DD">Devolución DE</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-tipo="PD">Préstamo DE</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-tipo="DA">Devolución A</button>
                            </li>
                        </ul>
                        <div class="table-responsive">
                            <table id="listadoTable" class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Opciones</th>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Fecha</th>
                                        <th>Movimiento</th>
                                        <th>Origen</th>
                                        <th>Destino</th>
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
    @canany(['prestamos_create', 'prestamos_edit'])
        @include('prestamos.action')
    @endcanany
    <div id="modalContainer"></div>
@endsection
@push('scripts')
    <script>
        class PrestamoManager extends CrudManager {

            constructor() {
                super("{{ url('prestamos') }}");
                this.tipoMovimiento = 'PA';
                this.saldosPendientes = {};
                this.TIPOS_VALIDOS = ['PA', 'PD', 'DD', 'DA'];
                this.afterSuccess = this.handlePrestamoSuccess.bind(this);
                this.initializeDataTable();

                this.populateSelect('documento_tipo_codigo', '{{ route('documento-tipos.select') }}', item =>
                    `<option value="${item.codigo}">${item.codigo} - ${item.descripcion}</option>`
                );
                /*
                this.populateSelect(
                    'comprobante_tipo_codigo',
                    '{{ route('comprobante-tipos.select') }}?tipo=prestamos',
                    item => `<option value="${item.codigo}">${item.codigo} - ${item.descripcion}</option>`
                );
                */

                this.setupLiveSearchSelect({
                    inputId: 'producto_nombre',
                    hiddenId: 'producto_id',
                    url: "{{ route('productos.buscar') }}",
                    template: (item) => {
                        return item.id ?
                            `${item.id} - ${item.nombre} (S/ ${item.costo_unitario})` :
                            `${item.nombre} (S/ ${item.costo_unitario})`;
                    },
                    getId: item => item.id,
                    minLength: 1,
                    delay: 300,
                    onSelect: (item) => this.addProductoToTable(item)
                });

                this.setupLiveSearchSelect({
                    inputId: 'cliente_razon_social',
                    hiddenId: 'cliente_id',
                    url: "{{ route('clientes.buscar') }}",
                    template: (item) => {
                        return item.documento_numero ?
                            `${item.id} - ${item.razon_social} (${item.documento_numero})` :
                            `${item.id} - ${item.razon_social}`;
                    },
                    minLength: 1,
                    delay: 300
                });

                document.getElementById('btnRegistrarCliente').addEventListener('click', () => this.registerSupplier());

                // Evento para el select de comprobante_tipo_codigo
                const selectComprobante = document.getElementById('comprobante_tipo_codigo');
                if (selectComprobante) {
                    selectComprobante.addEventListener('change', (e) => {
                        this.getSerie(e.target.value); // Ahora sí llama al método de la clase
                    });
                }


                window.addEventListener('popstate', () => {
                    const tipo = prestamoManager.getTipoFromUrl();

                    prestamoManager.tipoMovimiento = tipo;

                    document.querySelectorAll('#prestamoTabs button').forEach(btn => {
                        btn.classList.toggle('active', btn.dataset.tipo === tipo);
                    });

                    prestamoManager.tabla.ajax.reload();
                });

                document.querySelectorAll('#prestamoTabs button').forEach(btn => {
                    btn.addEventListener('click', e => {

                        const tipo = e.target.dataset.tipo;

                        // UI
                        document.querySelectorAll('#prestamoTabs button')
                            .forEach(b => b.classList.remove('active'));
                        e.target.classList.add('active');

                        // Estado
                        prestamoManager.tipoMovimiento = tipo;

                        // URL
                        this.updateUrl(tipo);

                        // Reload DataTable
                        prestamoManager.tabla.ajax.reload();
                    });
                });

                // Prevenir que el Enter envíe el formulario accidentalmente (especialmente con Swals)
                this.form.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && !e.target.classList.contains('btn-registrar-cliente')) {
                        e.preventDefault();
                    }
                });


            }

            tiposConfig = {
                PA: {
                    movimientoTexto: 'PA',
                    movimientoDetalle: 'Préstamo A',
                    comprobante: 'SP',
                    clienteLabel: 'Destino'
                },
                PD: {
                    movimientoTexto: 'PD',
                    movimientoDetalle: 'Préstamo DE',
                    comprobante: 'IP',
                    clienteLabel: 'Origen'
                },
                DD: {
                    movimientoTexto: 'DD',
                    movimientoDetalle: 'Devolución DE',
                    comprobante: 'DP',
                    clienteLabel: 'Origen'
                },
                DA: {
                    movimientoTexto: 'DA',
                    movimientoDetalle: 'Devolución A',
                    comprobante: 'SD',
                    clienteLabel: 'Destino'
                }
            };

            getTipoFromUrl() {
                const params = new URLSearchParams(window.location.search);
                const tipo = params.get('tipo');
                return this.TIPOS_VALIDOS.includes(tipo) ? tipo : 'PA';
            }

            updateUrl(tipo) {
                const params = new URLSearchParams(window.location.search);

                if (params.get('tipo') === tipo) return;

                params.set('tipo', tipo);
                window.history.pushState({
                    tipo
                }, '', `${location.pathname}?${params}`);
            }
            updateClienteLabelByTipo(tipo) {
                const label = document.getElementById('cliente_prestamo');

                const textByTipo = {
                    PA: 'Destino',
                    PD: 'Origen',
                    DD: 'Origen',
                    DA: 'Destino'
                };

                label.textContent = textByTipo[tipo] ?? 'Cliente';
            }

            async handlePrestamoSuccess(response, isEditing) {
                // Solo mostrar opción de ticket para compras NUEVAS (no ediciones)
                if (!isEditing && response.prestamo_id) {
                    setTimeout(() => {
                        this.showTicketOption(response);
                    }, 1000); // Esperar 1 segundo para que se vea la notificación de registro primero
                }
            }

            async showTicketOption(response) {
                const result = await Swal.fire({
                    title: '¡Préstamo registrado!',
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
                    window.open(imprimirRuta.replace(':id', response.prestamo_id), '_blank');
                }
            }

            /**
             * Construye un <select> HTML con las fracciones (unidades) disponibles
             * del producto. Similar al buildUnidadSelect de compras.
             *
             * @param {Array}  fracciones   Lista de fracciones del producto
             * @param {string} unidadActual Código de unidad actualmente seleccionado
             * @returns {string} HTML del <select>
             */
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

            /**
             * Agrega un producto a la tabla de detalles del préstamo.
             * Soporta selección de unidad/fracción (kg/saco) mediante un <select>.
             * Envía unidad_codigo y empaque al backend como hidden inputs.
             *
             * @param {Object}      item             Producto con fracciones cargadas
             * @param {number}      cantidad         Cantidad inicial (default 1)
             * @param {number|null} precio_unitario  Precio override (null = usar costo del producto)
             * @param {number|null} subtotal         Subtotal override (null = calcular)
             */
            addProductoToTable(item, cantidad = 1, precio_unitario = null, subtotal = null) {
                const tbody = document.querySelector('#tablaDetalles tbody');
                document.getElementById('producto_id').value = '';
                document.getElementById('producto_nombre').value = '';

                // Fracciones del producto (para el selector de unidad)
                const fracciones = item.fracciones || [];

                // Buscar fracción por unidad actual o usar la primera disponible
                const fraccionActual = fracciones.find(f => f.unidad_codigo === item.unidad_codigo) || fracciones[0];

                // Empaque inicial según la fracción seleccionada
                const empaqueInicial = fraccionActual ?
                    parseFloat(fraccionActual.empaque || 1) :
                    parseFloat(item.empaque || 1) || 1;

                const baseEmpaque = parseFloat(item.empaque) || 1;

                // Precio unitario: si no se pasa, usar costo_unitario del item ajustado al empaque inicial
                const costoUnitarioBase = parseFloat(item.costo_unitario) || 0;
                // REGLA: El precio unitario base siempre se divide entre el empaque del producto para obtener precio por Kg
                // y luego se multiplica por el empaque de la unidad seleccionada.
                let precioCalculado = (costoUnitarioBase / baseEmpaque) * empaqueInicial;

                const precioConImpuesto = precio_unitario != null ? parseFloat(precio_unitario) : precioCalculado;

                // Verificar si el producto ya existe en la tabla
                const existingRow = [...tbody.querySelectorAll('tr')].find(row => row.dataset.productoId == item.id);

                if (existingRow) {
                    const inputCantidad = existingRow.querySelector('.inputCantidad');
                    inputCantidad.value = parseFloat(inputCantidad.value || 0) + cantidad;

                    if (this.saldosPendientes && this.saldosPendientes[item.id]) {
                        this.validarFila(existingRow);
                    } else {
                        const inputCantidadKgm = existingRow.querySelector('.inputCantidadKgm');
                        const empaque = parseFloat(existingRow.dataset.empaque || 1);
                        if (inputCantidadKgm) {
                            inputCantidadKgm.value = (parseFloat(inputCantidad.value) * empaque).toFixed(2);
                        }
                    }

                    const precio = parseFloat(existingRow.querySelector('.inputPrecioUnitario').value) || precioConImpuesto;
                    existingRow.querySelector('.inputTotal').value = (precio * parseFloat(inputCantidad.value)).toFixed(2);
                    this.calculateTotals();

                } else {
                    const rowCount = tbody.rows.length + 1;
                    const porcentaje = parseFloat(item.afectacion_tipo?.porcentaje || 0);
                    const sub = subtotal ?? (precioConImpuesto * cantidad);

                    const tr = document.createElement('tr');
                    tr.dataset.productoId = item.id;
                    tr.dataset.afectacionPorcentaje = porcentaje;
                    tr.dataset.afectacionCodigo = item.afectacion_tipo_codigo || '10';
                    tr.dataset.empaque = empaqueInicial;
                    tr.dataset.totalManual = "0";

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
                    <input type="number" name="detalles[${rowCount}][cantidad_kgm]" value="${(cantidad * empaqueInicial).toFixed(2)}" step="any" class="form-control form-control-sm inputCantidadKgm">
                </td>
                <td class="text-end">
                    <input type="number" name="detalles[${rowCount}][precio_unitario]" value="${precioConImpuesto.toFixed(4)}" step="any" class="form-control form-control-sm inputPrecioUnitario">
                </td>
                <td class="text-end">
                    <input type="number" name="detalles[${rowCount}][total]" value="${sub.toFixed(2)}" step="any" class="form-control form-control-sm inputTotal">
                </td>

                <!-- Hidden inputs que viajan al backend -->
                <input type="hidden" name="detalles[${rowCount}][producto_id]" value="${item.id}">
                <input type="hidden" name="detalles[${rowCount}][unidad_codigo]" value="${item.unidad_codigo || ''}">
                <input type="hidden" name="detalles[${rowCount}][empaque]" value="${empaqueInicial}">
            `;

                    // ─── Evento: cambio de unidad (select fracción) ───
                    const selectUnidad = tr.querySelector('.selectUnidad');
                    if (selectUnidad) {
                        selectUnidad.addEventListener('change', (e) => {
                            const option = e.target.selectedOptions[0];
                            const nuevoEmpaque = parseFloat(option.dataset.empaque) || 1;
                            const empaqueActual = parseFloat(tr.dataset.empaque) || 1;
                            const inputPrecio = tr.querySelector('.inputPrecioUnitario');
                            const precioActual = parseFloat(inputPrecio.value) || 0;

                            // Siempre calculamos el precio de forma proporcional al empaque actual
                            // para que se mantenga la relación de costo en los préstamos.
                            const nuevoPrecio = (precioActual / empaqueActual) * nuevoEmpaque;

                            // Actualizar hidden inputs
                            const inputUnidad = tr.querySelector('input[name*="[unidad_codigo]"]');
                            const inputEmpaque = tr.querySelector('input[name*="[empaque]"]');
                            if (inputUnidad) inputUnidad.value = option.value;
                            if (inputEmpaque) inputEmpaque.value = nuevoEmpaque;

                            // Actualizar dataset y celda visible
                            tr.dataset.empaque = nuevoEmpaque;
                            const tdEmpaque = tr.querySelector('.tdEmpaque');
                            if (tdEmpaque) tdEmpaque.textContent = nuevoEmpaque;

                            // Recalcular cantidades y precio
                            const inputCantidad = tr.querySelector('.inputCantidad');
                            const inputCantidadKgm = tr.querySelector('.inputCantidadKgm');
                            const inputTotal = tr.querySelector('.inputTotal');

                            const cant = parseFloat(inputCantidad.value) || 0;
                            inputPrecio.value = nuevoPrecio.toFixed(4);
                            inputCantidadKgm.value = (cant * nuevoEmpaque).toFixed(2);

                            if (tr.dataset.totalManual !== "1") {
                                inputTotal.value = (cant * nuevoPrecio).toFixed(2);
                            }
                            this.calculateTotals();
                        });
                    }

                    // ─── Referencias a inputs de la fila ───
                    const inputCantidadObj = tr.querySelector('.inputCantidad');
                    const inputPrecioObj = tr.querySelector('.inputPrecioUnitario');
                    const inputTotalObj = tr.querySelector('.inputTotal');

                    // Función auxiliar para recalcular subtotal automáticamente
                    const recalcularFilaAuto = () => {
                        const cant = parseFloat(inputCantidadObj.value) || 0;
                        const prec = parseFloat(inputPrecioObj.value) || 0;
                        inputTotalObj.value = (cant * prec).toFixed(2);
                        this.calculateTotals();
                    };

                    // ─── Evento: cambio de cantidad ───
                    inputCantidadObj.addEventListener('input', (e) => {
                        tr.dataset.totalManual = "0";
                        if (this.saldosPendientes && this.saldosPendientes[item.id]) {
                            this.validarFila(tr);
                        } else {
                            const cantidadVal = parseFloat(e.target.value) || 0;
                            const empaque = parseFloat(tr.dataset.empaque || 1);
                            const inputCantidadKgm = tr.querySelector('.inputCantidadKgm');
                            if (inputCantidadKgm) {
                                inputCantidadKgm.value = (cantidadVal * empaque).toFixed(2);
                            }
                        }
                        recalcularFilaAuto();
                    });

                    // ─── Evento: cambio de precio unitario ───
                    inputPrecioObj.addEventListener('input', () => {
                        tr.dataset.totalManual = "0";
                        recalcularFilaAuto();
                    });

                    // ─── Evento: edición manual del total (recalcula precio) ───
                    inputTotalObj.addEventListener('input', (e) => {
                        tr.dataset.totalManual = "1";
                        const cant = parseFloat(inputCantidadObj.value) || 0;
                        const tot = parseFloat(e.target.value) || 0;
                        if (cant > 0) {
                            inputPrecioObj.value = (tot / cant).toFixed(4);
                        }
                        this.calculateTotals();
                    });

                    // ─── Evento: eliminar fila ───
                    tr.querySelector('.btnEliminarFila').addEventListener('click', () => {
                        tr.remove();
                        this.reindexDetalles();
                        this.calculateTotals();
                    });

                    // ─── Evento: cambio de cantidad_kgm (inverso: kg → unidades) ───
                    const inputCantidadKgm = tr.querySelector('.inputCantidadKgm');
                    inputCantidadKgm.addEventListener('input', (e) => {
                        const empaque = parseFloat(tr.dataset.empaque || 1);
                        const cantidadKgm = parseFloat(e.target.value) || 0;
                        
                        if (this.saldosPendientes && this.saldosPendientes[item.id]) {
                            const saldoPendiente = parseFloat(this.saldosPendientes[item.id].pendiente);
                            if (cantidadKgm > (saldoPendiente + 0.0001)) {
                                Swal.fire({ icon: 'warning', title: 'Cantidad excedida', text: `No puedes devolver más de lo pendiente (${saldoPendiente.toFixed(2)} Kg).` });
                                e.target.value = saldoPendiente.toFixed(2);
                                inputCantidadObj.value = (saldoPendiente / empaque).toFixed(2);
                            } else {
                                inputCantidadObj.value = (cantidadKgm / empaque).toFixed(2);
                            }
                        } else {
                            inputCantidadObj.value = empaque > 0 ? (cantidadKgm / empaque).toFixed(2) : 0;
                        }

                        tr.dataset.totalManual = "0";
                        recalcularFilaAuto();
                    });

                    // Limpiar errores al escribir
                    const allInputs = tr.querySelectorAll('input[type="number"]');
                    allInputs.forEach(input => this.setupInputErrorClear(input));

                    tbody.appendChild(tr);
                }

                this.calculateTotals();
            }


            /**
             * Recalcula los totales sumando el inputTotal de cada fila.
             * Actualiza el campo total del formulario.
             */
            calculateTotals() {
                const tbody = document.querySelector('#tablaDetalles tbody');
                if (!tbody) return;

                let totalGeneral = 0;

                [...tbody.querySelectorAll('tr')].forEach(row => {
                    const itemSubtotal = parseFloat(row.querySelector('.inputTotal')?.value) || 0;
                    totalGeneral += itemSubtotal;
                });

                document.getElementById('total').value = totalGeneral.toFixed(2);
            }

            /**
             * Re-indexa los nombres de los inputs de la tabla de detalles
             * después de eliminar una fila. Asegura que los índices
             * detalles[1], detalles[2], etc. sean consecutivos.
             */
            reindexDetalles() {
                const tbody = document.querySelector('#tablaDetalles tbody');
                if (!tbody) return;

                [...tbody.querySelectorAll('tr')].forEach((tr, index) => {
                    const newIndex = index + 1;

                    // Actualizar columna #
                    tr.children[1].textContent = newIndex;

                    // Actualizar todos los name="detalles[x][campo]"
                    tr.querySelectorAll('input[name^="detalles["]').forEach(input => {
                        input.name = input.name.replace(/detalles\[\d+\]/, `detalles[${newIndex}]`);
                    });
                });
            }

            initializeDataTable() {
                this.tabla = $(this.elements.table).DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: this.baseUrl,
                        type: 'GET',
                        data: d => {
                            d.tipo = this.tipoMovimiento;
                        }
                    },
                    columns: [{
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'id',
                            name: 'id'
                        },
                        {
                            data: 'usuario',
                            name: 'usuario'
                        },
                        {
                            data: 'fecha_prestamo',
                            name: 'fecha_prestamo'
                        },
                        {
                            data: 'movimiento_tipo',
                            name: 'movimiento_tipo'
                        },
                        {
                            data: 'origen',
                            name: 'origen'
                        },
                        {
                            data: 'destino',
                            name: 'destino'
                        },
                        {
                            data: 'comprobante_tipo_codigo',
                            name: 'comprobante_tipo_codigo'
                        },
                        {
                            data: 'serie',
                            name: 'serie'
                        },
                        {
                            data: 'correlativo',
                            name: 'correlativo'
                        },
                        {
                            data: 'total',
                            name: 'total'
                        },
                        {
                            data: 'estado',
                            name: 'estado'
                        }
                    ],
                    columnDefs: [{
                            targets: 0,
                            width: '10%',
                            className: 'text-center'
                        },
                        {
                            targets: 1,
                            width: '10%'
                        },
                        {
                            targets: 2,
                            width: '10%'
                        },
                        {
                            targets: 3,
                            width: '10%'
                        },
                        {
                            targets: 4,
                            width: '10%'
                        },
                        {
                            targets: 5,
                            width: '10%'
                        },
                        {
                            targets: 6,
                            width: '5%'
                        },
                        {
                            targets: 7,
                            width: '5%'
                        },
                        {
                            targets: 8,
                            width: '10%'
                        },
                        {
                            targets: 9,
                            width: '10%'
                        },
                        {
                            targets: 10,
                            width: '10%'
                        }
                    ],
                    responsive: true
                });
            }

            showCreateModal() {
                super.showCreateModal();
                this.elements.modalTitle.textContent = 'Nuevo Préstamo';
                document.getElementById('es_rectificacion').value = '0';
                document.getElementById('prestamo_anulado_id').value = '';
                document.querySelector('#tablaDetalles tbody').innerHTML = '';
                document.getElementById('documento_tipo_codigo').value = '01';
                document.getElementById('cliente_id').value = '';
                document.getElementById('cliente_razon_social').value = '';
                document.getElementById('fecha_prestamo').value = this.obtenerFechaHoraActual();
                const usuarioNombre = @json(auth()->user()->name);
                document.getElementById('usuario_nombre').textContent = usuarioNombre;

                this.applyTipoMovimiento(this.tipoMovimiento);
            }

            applyTipoMovimiento(tipo) {
                const config = this.tiposConfig[tipo];
                if (!config) return;

                // Texto del movimiento
                document.getElementById('movimiento_tipo').value = config.movimientoTexto;

                // Tipo de comprobante
                const comprobanteSelect = document.getElementById('comprobante_tipo_codigo');
                comprobanteSelect.value = config.comprobante;
                this.elements.modalTitle.textContent = `Nuevo ${config.movimientoDetalle}:`;

                // Cargar serie automáticamente
                this.getSerie(config.comprobante);

                // Cambiar label Origen / Destino
                document.getElementById('cliente_prestamo').textContent = config.clienteLabel;
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

            resetForm() {
                if (super.resetForm) super.resetForm();
                this.saldosPendientes = {};
                
                // Asegurar que los campos de rectificación se limpien siempre
                const inputRect = document.getElementById('es_rectificacion');
                const inputAnulado = document.getElementById('prestamo_anulado_id');
                if (inputRect) inputRect.value = '0';
                if (inputAnulado) inputAnulado.value = '';
            }

            validarFila(tr) {
                const itemID = tr.dataset.id; 
                if (!this.saldosPendientes || !this.saldosPendientes[itemID]) return true;

                const inputCantidad = tr.querySelector('.inputCantidad');
                const inputCantidadKgm = tr.querySelector('.inputCantidadKgm');
                const empaque = parseFloat(tr.dataset.empaque || 1);
                const saldoPendiente = parseFloat(this.saldosPendientes[itemID].pendiente);
                
                let cantVal = parseFloat(inputCantidad.value) || 0;
                let totalKg = cantVal * empaque;

                if (totalKg > (saldoPendiente + 0.0001)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cantidad excedida',
                        text: `No puedes devolver más de lo pendiente (${saldoPendiente.toFixed(2)} Kg).`,
                    });
                    
                    const maxUnidades = saldoPendiente / empaque;
                    inputCantidad.value = maxUnidades.toFixed(2);
                    inputCantidadKgm.value = saldoPendiente.toFixed(2);
                    return false;
                }
                
                inputCantidadKgm.value = totalKg.toFixed(2);
                return true;
            }

            async showEditModal(id) {
                try {
                    const response = await this.fetchData(`${this.baseUrl}/${id}`);

                    this.isEditing = true;
                    this.resetForm();

                    this.elements.modalTitle.textContent = 'Editar: ' + response.comprobante_tipo_codigo + ' ' +
                        response.serie + '-' + response.correlativo
                    this.elements.methodField.value = 'PUT';

                    // Llenar campos específicos
                    // Llenar campos principales del modal
                    //console.log(response);
                    document.getElementById('movimiento_tipo').value = response.movimiento_tipo || '';
                    document.getElementById('comprobante_tipo_codigo').value = response.comprobante_tipo_codigo || '';
                    document.getElementById('serie').value = response.serie || '';
                    document.getElementById('correlativo').value = response.correlativo || '';

                    let razonSocial = '';
                    let clienteId = '';
                    let movimiento_tipo = response.movimiento_tipo;

                    switch (movimiento_tipo) {
                        case 'PA':
                        case 'DA':
                            razonSocial = response.cliente_destino?.razon_social || '';
                            clienteId = response.cliente_destino?.id || '';
                            break;

                        case 'PD':
                        case 'DD':
                            razonSocial = response.cliente_origen?.razon_social || '';
                            clienteId = response.cliente_origen?.id || '';
                            break;
                    }

                    document.getElementById('cliente_razon_social').value = razonSocial;
                    document.getElementById('cliente_id').value = clienteId;

                    document.getElementById('fecha_prestamo').value = response.fecha_prestamo || '';
                    document.getElementById('usuario_nombre').textContent = response.user_nombre || '';

                    // Llenar tabla de detalles (productos)
                    this.updateDetailsTable(response.detalles, response.saldos || {});

                    // Llenar totales
                    document.getElementById('total').value = parseFloat(response.total).toFixed(2);

                    this.form.action = `${this.baseUrl}/${id}`;

                    this.modal.show();

                } catch (error) {
                    this.showNotification('error', 'Error al cargar los datos');
                    console.error('Error al cargar datos:', error);
                }
            }

            /**
             * Llena la tabla de detalles al editar un préstamo.
             * Transforma cada detalle al formato esperado por addProductoToTable,
             * incluyendo las fracciones del producto para el selector de unidad.
             */
            updateDetailsTable(detalles = [], saldos = {}) {
                const tbody = document.querySelector('#tablaDetalles tbody');
                tbody.innerHTML = '';

                detalles.forEach(detalle => {
                    const producto = detalle.producto || {};
                    const id = detalle.producto_id;
                    
                    // Si hay saldos (es una devolución), el valor inicial es lo pendiente
                    let cantidadInicial = +detalle.cantidad;
                    if (saldos && saldos[id]) {
                        // El saldo pendiente viene en Kg, debemos convertirlo a unidades de empaque base
                        const empaqueBase = parseFloat(detalle.producto_empaque) || 1;
                        cantidadInicial = parseFloat(saldos[id].pendiente) / empaqueBase;
                    }

                    // Transformar al formato esperado con fracciones incluidas
                    const productoFormateado = {
                        id: id,
                        codigo: producto.codigo ?? null,
                        nombre: detalle.producto_nombre,
                        costo_unitario: detalle.valor_unitario,
                        afectacion_tipo_codigo: producto.afectacion_tipo_codigo,
                        unidad_codigo: detalle.unidad_codigo,
                        empaque: detalle.producto_empaque,
                        fracciones: producto.fracciones || [],
                        afectacion_tipo: {
                            codigo: producto.afectacion_tipo?.codigo,
                            porcentaje: producto.afectacion_tipo?.porcentaje
                        },
                        unidad: {
                            codigo: detalle.unidad_codigo,
                            descripcion: detalle.unidad_nombre
                        },
                        total_manual: "1"
                    };

                    // Enviar a la tabla con la estructura correcta
                    this.addProductoToTable(
                        productoFormateado,
                        cantidadInicial,
                        +detalle.valor_unitario,
                        null // Dejar que recalcule el subtotal basado en la nueva cantidad
                    );
                });
            }

            focusFirstField() {
                document.getElementById('producto_nombre').focus();

                const modalEl = this.modal._element;

                modalEl.addEventListener('shown.bs.modal', () => {
                    const input = document.getElementById('producto_nombre');
                    if (input) input.focus();
                }, {
                    once: true
                });
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
                    } else if (response.status === 422) {
                        this.handleFormErrors({
                            status: 422,
                            data
                        });
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
                            const input = document.querySelector(
                                `input[name="detalles[${indice}][${nombreCampo}]"]`);

                            if (input) {
                                input.classList.add('is-invalid');

                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'invalid-feedback d-block';
                                errorDiv.style.cssText = 'font-size: 0.875rem; margin-top: 0.25rem;';
                                errorDiv.textContent = errors[campo][0];

                                input.parentElement.appendChild(errorDiv);

                                // Scroll al primer error
                                if (Object.keys(errors)[0] === campo) {
                                    input.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'center'
                                    });
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

            async getSerie(codigo) {
                const urlSerie = "{{ route('prestamos.get-serie') }}";

                try {
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

            async devolucionShowModal(id, tipo) {
                try {
                    const response = await this.fetchData(`${this.baseUrl}/${id}`);

                    this.isEditing = false;
                    this.resetForm();

                    this.elements.modalTitle.textContent =
                        'Devolución préstamo ' + response.comprobante_tipo_codigo + ' ' + response.serie + '-' +
                        response.correlativo;

                    this.elements.methodField.value = 'POST';
                    document.getElementById('es_rectificacion').value = '0';
                    document.getElementById('prestamo_anulado_id').value = '';
                    let nuevoTipo = '';
                    let nuevoComprobante = '';
                    let razonSocial = '';
                    let clienteId = '';

                    switch (tipo) {
                        case 'PA':
                            nuevoTipo = 'DD';
                            nuevoComprobante = 'DP';
                            razonSocial = response.cliente_destino?.razon_social || '';
                            clienteId = response.cliente_destino?.id || '';
                            break;

                        case 'PD':
                            nuevoTipo = 'DA';
                            nuevoComprobante = 'SD';
                            razonSocial = response.cliente_origen?.razon_social || '';
                            clienteId = response.cliente_origen?.id || '';
                            break;
                    }
                    document.getElementById('prestamo_referencia_id').value = id;
                    document.getElementById('cliente_razon_social').value = razonSocial;
                    document.getElementById('cliente_id').value = clienteId;

                    document.getElementById('movimiento_tipo').value = nuevoTipo;
                    document.getElementById('comprobante_tipo_codigo').value = nuevoComprobante;
                    this.getSerie(nuevoComprobante);

                    document.getElementById('fecha_prestamo').value = this.obtenerFechaHoraActual();
                    document.getElementById('usuario_nombre').textContent = @json(auth()->user()->name);

                    // Llenar tabla de detalles (productos)
                    this.updateDetailsTable(response.detalles, response.saldos || {});

                    // Llenar totales
                    document.getElementById('total').value = parseFloat(response.total).toFixed(2);

                    this.form.action = this.baseUrl; // POST nueva venta
                    this.saldosPendientes = response.saldos || {};

                    this.modal.show();

                } catch (error) {
                    this.showNotification('error', 'Error al cargar los datos');
                    console.error(error);
                }
            }

            async showRectifyModal(id) {
                try {
                    const response = await this.fetchData(`${this.baseUrl}/${id}`);
                    
                    this.isEditing = false; // Queremos que se comporte como un registro NUEVO
                    this.resetForm();
                    
                    // Formatear fecha para datetime-local input
                    const formatDateTimeLocal = (fecha) => {
                        if (!fecha || fecha.startsWith('-000') || fecha === 'null') return '';
                        if (typeof fecha !== 'string') return '';
                        return fecha.replace(' ', 'T').substring(0, 16);
                    };
                    
                    this.elements.modalTitle.textContent = 'Rectificar Préstamo: '+ response.comprobante_tipo_codigo + ' ' + response.serie + '-' + response.correlativo;
                    this.elements.methodField.value = 'POST';
                    document.getElementById('es_rectificacion').value = '1';
                    document.getElementById('prestamo_anulado_id').value = id;
                    
                    // Asignar tipo movimiento
                    document.getElementById('movimiento_tipo').value = response.movimiento_tipo || '';
                    document.getElementById('fecha_prestamo').value = formatDateTimeLocal(response.fecha_prestamo);

                    // Llenar cliente
                    document.getElementById('cliente_id').value = response.cliente_origen_id && response.cliente_origen_id !== 11 ? response.cliente_origen_id : (response.cliente_destino_id || '');
                    
                    const clienteTexto = response.cliente_origen_id && response.cliente_origen_id !== 11
                        ? `${response.cliente_origen_id} - ${response.cliente_origen?.razon_social || ''}`
                        : `${response.cliente_destino_id} - ${response.cliente_destino?.razon_social || ''}`;
                    document.getElementById('cliente_razon_social').value = clienteTexto;

                    // Cargar prestamo_referencia_id si existe (para DD/DA tipo devoluciones)
                    const prestamoRefIdInput = document.getElementById('prestamo_referencia_id');
                    if (prestamoRefIdInput) {
                        prestamoRefIdInput.value = response.prestamo_referencia_id || '';
                    }

                    // Asignar comprobante
                    document.getElementById('comprobante_tipo_codigo').value = response.comprobante_tipo_codigo || '';
                    document.getElementById('serie').value = response.serie || '';
                    document.getElementById('correlativo').value = response.correlativo || '';

                    // Llenar detalles
                    if (response.detalles && response.detalles.length > 0) {
                        this.updateDetailsTable(response.detalles, null);
                    }

                    // Llenar totales
                    document.getElementById('total').value = parseFloat(response.total).toFixed(2);

                    this.modal.show();
                    
                    Swal.fire({
                        title: 'Modo Rectificación',
                        text: 'Estás usando los datos de un préstamo anulado. Las modificaciones que hagas se guardarán como un nuevo préstamo.',
                        icon: 'info',
                        confirmButtonText: 'Entendido'
                    });

                } catch (error) {
                    console.error('Error al cargar préstamo:', error);
                    Swal.fire('Error', 'No se cargar la información para rectificar', 'error');
                }
            }
        }



        document.addEventListener('DOMContentLoaded', () => {
            prestamoManager = new PrestamoManager();

            const tipoInicial = prestamoManager.getTipoFromUrl();

            prestamoManager.tipoMovimiento = tipoInicial;
            prestamoManager.updateClienteLabelByTipo(tipoInicial);

            // Activar tab inicial
            document.querySelectorAll('#prestamoTabs button').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.tipo === tipoInicial);

                btn.addEventListener('click', e => {
                    const tipo = e.currentTarget.dataset.tipo;

                    prestamoManager.tipoMovimiento = tipo;
                    prestamoManager.updateClienteLabelByTipo(tipo);

                    // Actualizar URL
                    const url = new URL(window.location);
                    url.searchParams.set('tipo', tipo);
                    window.history.pushState({}, '', url);

                    prestamoManager.tabla.ajax.reload();
                });
            });

            document.body.addEventListener('click', function(e) {
                if (e.target && (e.target.matches('.btn-view-prestamo') || e.target.closest(
                        '.btn-view-prestamo'))) {
                    const button = e.target.closest('.btn-view-prestamo');
                    const prestamoId = button.getAttribute('data-id');
                    if (!prestamoId) return;

                    const url = "{{ route('prestamos.ver', ':id') }}".replace(':id', prestamoId);

                    fetch(url)
                        .then(response => {
                            if (!response.ok) throw new Error('No se pudo cargar el préstamo');
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

                if (e.target && (e.target.matches('.btn-rectificar-prestamo') || e.target.closest('.btn-rectificar-prestamo'))) {
                    const button = e.target.closest('.btn-rectificar-prestamo');
                    const prestamoId = button.getAttribute('data-id');
                    if (!prestamoId) return;
                    prestamoManager.showRectifyModal(prestamoId);
                }

                if (e.target && (e.target.matches('.btn-anular-prestamo') || e.target.closest('.btn-anular-prestamo'))) {
                    const button = e.target.closest('.btn-anular-prestamo');
                    const prestamoId = button.getAttribute('data-id');
                    if (!prestamoId) return;

                    // Confirmación con SweetAlert
                    Swal.fire({
                        title: '¿Anular este préstamo?',
                        text: 'Se revertirá el stock y se ajustarán los registros. Esta acción no se puede deshacer.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, anular',
                        cancelButtonText: 'Cancelar'
                    }).then(async (result) => {
                        if (result.isConfirmed) {
                            try {
                                const url = "{{ route('prestamos.anular', ':id') }}".replace(':id', prestamoId);
                                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                                const response = await fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken
                                    }
                                });

                                const data = await response.json();

                                if (response.ok && data.success) {
                                    prestamoManager.showNotification('success', data.message);
                                    prestamoManager.tabla.ajax.reload();
                                } else {
                                    prestamoManager.showNotification('error', data.message || 'Error al anular el préstamo');
                                }
                            } catch (error) {
                                prestamoManager.showNotification('error', 'Error de red al intentar anular el préstamo');
                                console.error(error);
                            }
                        }
                    });
                }

                if (e.target && (e.target.matches('.btn-registrar-devolucion') || e.target.closest(
                        '.btn-registrar-devolucion'))) {
                    const button = e.target.closest('.btn-registrar-devolucion');
                    const prestamoId = button.getAttribute('data-id');
                    const tipo = button.getAttribute('data-tipo');
                    if (!prestamoId) return;

                    // 🔴 CERRAR MODAL "VER"
                    const modalVerEl = document.querySelector('#modalContainer .modal.show');
                    if (modalVerEl) {
                        const modalInstance = bootstrap.Modal.getInstance(modalVerEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }

                    // 🟢 ABRIR MODAL DUPLICAR
                    prestamoManager.devolucionShowModal(prestamoId, tipo);
                }
            });
        });

        document.getElementById('mnuPrestamos').classList.add('menu-open');
        document.getElementById('itemPrestamos')?.classList.add('active');
    </script>
    <script src="{{ asset('js/detallesFlecha.js') }}"></script>
@endpush
