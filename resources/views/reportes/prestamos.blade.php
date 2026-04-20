@extends('plantilla.app')
@push('estilos')

@endpush
@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link active" href="#tab1" data-bs-toggle="tab">Estado de cuenta Préstamo A</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab2" data-bs-toggle="tab">Estado de cuenta Préstamo DE</a></li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div id="loadingOverlay" class="loading-overlay d-none">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status"></div>
                                <div class="mt-2 fw-bold">Cargando...</div>
                            </div>
                        </div>
                        <div class="tab-pane fade show active" id="tab1">
                            <div class="d-flex flex-nowrap mb-2" style="overflow: visible;">
                                <input type="text"
                                          id="cliente_destino_nombre"
                                          class="form-control form-control-sm me-2" placeholder="Cliente Destino"
                                          placeholder="Cliente Destino"
                                          autocomplete="off">
                                <input type="hidden" id="cliente_destino_id" name="cliente_destino_id">
                                <input type="date" id="fecha_inicio" class="form-control form-control-sm me-2" placeholder="Fecha inicio">
                                <input type="date" id="fecha_fin" class="form-control form-control-sm me-2" placeholder="Fecha fin">
                                <button id="btnFiltrar" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <a href="#" id="btnPdf" target="_blank" class="btn btn-danger">PDF</a>
                            </div>
                            <div id="reporteTab1">
                                @include('reportes.prestamos.prestamos_a', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null, 'cliente_destino_id' => null])
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab2">
                            <div class="d-flex flex-nowrap mb-2" style="overflow: visible;">
                                <input type="text"
                                          id="cliente_origen_nombre"
                                          class="form-control form-control-sm me-2" placeholder="Cliente origen"
                                          placeholder="Cliente Origen"
                                          autocomplete="off">
                                <input type="hidden" id="cliente_origen_id" name="cliente_origen_id">
                                <input type="date" id="fecha_inicio_origen" class="form-control form-control-sm me-2" placeholder="Fecha inicio">
                                <input type="date" id="fecha_fin_origen" class="form-control form-control-sm me-2" placeholder="Fecha fin">
                                <button id="btnFiltrarOrigen" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <a href="#" id="btnPdfOrigen" target="_blank" class="btn btn-danger">PDF</a>
                            </div>

                            <div id="reporteTab2">
                                @include('reportes.prestamos.prestamos_de', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null, 'cliente_origen_id' => null])
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- /.card -->
        </div>
        <!-- /.col -->
    </div>
    <!--end::Row-->
</div>
@endsection
@include('prestamos.action')
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {

        setupLiveSearchSelect({
            inputId: 'cliente_destino_nombre',
            hiddenId: 'cliente_destino_id',
            url: "{{ route('clientes.buscar') }}",
            template: item => item.documento_numero
                ? `${item.id} - ${item.razon_social} (${item.documento_numero})`
                : `${item.id} - ${item.razon_social}`
        });

        setupLiveSearchSelect({
            inputId: 'cliente_origen_nombre',
            hiddenId: 'cliente_origen_id',
            url: "{{ route('clientes.buscar') }}",
            template: item => item.documento_numero
                ? `${item.id} - ${item.razon_social} (${item.documento_numero})`
                : `${item.id} - ${item.razon_social}`
        });

    });

    function setupLiveSearchSelect({inputId, hiddenId, url, template = item => item.nombre || item.descripcion || '', getId = item => item.id || item.codigo || '', minLength = 3, delay = 300, onSelect}) {
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        if (!input || !hidden) return;
        let timeout = null;
        let activeIndex = -1;
        let suggestions = [];

        const clearSuggestions = () => {
            const oldList = input.parentNode.querySelector('ul.search-list');
            if (oldList) oldList.remove();
            activeIndex = -1;
            suggestions = [];
        };

        const renderSuggestions = (data) => {
            clearSuggestions();
            if (!data.length) return;
            const list = document.createElement('ul');
            list.className = 'list-group position-absolute w-100 search-list';
            list.style.zIndex = '1050';
            list.style.top = '100%';
            suggestions = data;
            data.forEach((item, index) => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action';
                li.textContent = template(item);
                li.dataset.index = index;
                li.onclick = () => {
                    input.value = template(item);
                    hidden.value = getId(item);
                    clearSuggestions();
                    if (onSelect) onSelect(item);
                };
                list.appendChild(li);
            });
            input.parentNode.style.position = 'relative';
            input.parentNode.appendChild(list);
        };

        const fetchSuggestions = (query) => {
            fetch(url + '?q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(renderSuggestions)
                .catch(console.error);
        };

        input.addEventListener('input', () => {
            const query = input.value.trim();
            if (query.length < minLength) {
                hidden.value = '';
                clearSuggestions();
                return;
            }
            clearTimeout(timeout);
            timeout = setTimeout(() => fetchSuggestions(query), delay);
        });

        input.addEventListener('keydown', (e) => {
            const list = input.parentNode.querySelector('ul.search-list');
            if (!list) return;
            const items = list.querySelectorAll('li');
            if (!items.length) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); activeIndex = (activeIndex + 1) % items.length; }
            else if (e.key === 'ArrowUp') { e.preventDefault(); activeIndex = (activeIndex - 1 + items.length) % items.length; }
            else if (e.key === 'Enter') {
                e.preventDefault();
                if (suggestions[activeIndex]) {
                    const item = suggestions[activeIndex];
                    input.value = template(item);
                    hidden.value = getId(item);
                    clearSuggestions();
                    if (onSelect) onSelect(item);
                }
            } else if (e.key === 'Escape') clearSuggestions();
            items.forEach((li,i) => li.classList.toggle('active', i === activeIndex));
        });

        input.addEventListener('blur', () => setTimeout(clearSuggestions, 200));
    }

    // ================== PRESTAMO MANAGER PARA DEVOLUCIONES ==================
    class PrestamoManagerDevolucion {
        constructor() {
            this.baseUrl = "{{ url('prestamos') }}";
            this.modal = null;
            this.saldosPendientes = {};
            this.isEditing = false;
            this.elements = {
                modalTitle: document.getElementById('modalTitle'),
                methodField: document.getElementById('method_field'),
            };
            this.form = document.getElementById('formUpdate');
            this.initModal();
            this.setupFormSubmit();
        }

        initModal() {
            const modalEl = document.getElementById('modalUpdate');
            if (modalEl) {
                this.modal = new bootstrap.Modal(modalEl);
            }
        }

        setupFormSubmit() {
            if (!this.form) return;
            this.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });
        }

        async handleSubmit() {
            const formData = new FormData(this.form);
            const submitBtn = document.getElementById('btnSubmit');
            if (submitBtn) submitBtn.disabled = true;

            try {
                const response = await fetch(this.form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && (data.success === true || data.status === true)) {
                    this.modal?.hide();
                    Swal.fire({
                        icon: 'success',
                        title: data.message || 'Devolución registrada correctamente',
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                    if (typeof window.tablaPrestamos !== 'undefined' && window.tablaPrestamos.ajax) {
                        window.tablaPrestamos.ajax.reload(null, false);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: data.message || 'Error al registrar la devolución',
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión o formato de respuesta',
                });
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        }

        async fetchData(url) {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Error fetching data');
            return response.json();
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

        async getSerie(codigo) {
            const urlSerie = "{{ route('prestamos.get-serie') }}";
            try {
                const response = await fetch(
                    `${urlSerie}?comprobante_tipo_codigo=${codigo}`
                );
                const data = await response.json();
                if (data.serie && data.numero) {
                    document.getElementById('serie').value = data.serie;
                    document.getElementById('correlativo').value = data.numero;
                }
            } catch (error) {
                console.error('Error al obtener la serie:', error);
            }
        }

        async devolucionShowModal(id, tipo) {
            try {
                const response = await this.fetchData(`${this.baseUrl}/${id}`);

                this.isEditing = false;
                this.resetForm();

                this.elements.modalTitle.textContent =
                    'Devolución préstamo ' + response.comprobante_tipo_codigo + ' ' + response.serie + '-' + response.correlativo;

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

                this.updateDetailsTable(response.detalles, response.saldos || {});
                document.getElementById('total').value = parseFloat(response.total).toFixed(2);

                this.form.action = this.baseUrl;
                this.saldosPendientes = response.saldos || {};

                this.modal.show();

            } catch (error) {
                console.error('Error al cargar los datos:', error);
                alert('Error al cargar los datos de devolución');
            }
        }

        resetForm() {
            if (this.form) this.form.reset();
            document.getElementById('producto_nombre').value = '';
            document.getElementById('producto_id').value = '';
            document.getElementById('prestamo_referencia_id').value = '';
            document.getElementById('cliente_razon_social').value = '';
            document.getElementById('cliente_id').value = '';
        }

        buildUnidadSelect(fracciones, unidadCodigo) {
            if (!fracciones || fracciones.length === 0) {
                return '<span class="badge bg-secondary">Sin unidad</span>';
            }
            let optionsHtml = '';
            fracciones.forEach(f => {
                const selected = f.unidad_codigo === unidadCodigo ? 'selected' : '';
                optionsHtml += `<option value="${f.unidad_codigo}" data-empaque="${f.empaque || 1}" ${selected}>${f.descripcion || f.unidad_codigo}</option>`;
            });
            return `<select class="form-select form-select-sm selectUnidad">${optionsHtml}</select>`;
        }

        updateDetailsTable(detalles = [], saldos = {}) {
            const tbody = document.querySelector('#tablaDetalles tbody');
            if (!tbody) return;
            tbody.innerHTML = '';

            detalles.forEach(detalle => {
                const producto = detalle.producto || {};
                const id = detalle.producto_id;

                let cantidadInicial = +detalle.cantidad;
                if (saldos && saldos[id]) {
                    const empaqueBase = parseFloat(detalle.producto_empaque) || 1;
                    cantidadInicial = parseFloat(saldos[id].pendiente) / empaqueBase;
                }

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

                this.addProductoToTable(productoFormateado, cantidadInicial, +detalle.valor_unitario, null);
            });
        }

        addProductoToTable(item, cantidad = 1, precio_unitario = null, subtotal = null) {
            const tbody = document.querySelector('#tablaDetalles tbody');
            document.getElementById('producto_id').value = '';
            document.getElementById('producto_nombre').value = '';

            const fracciones = item.fracciones || [];
            const fraccionActual = fracciones.find(f => f.unidad_codigo === item.unidad_codigo) || fracciones[0];
            const empaqueInicial = fraccionActual ? parseFloat(fraccionActual.empaque || 1) : parseFloat(item.empaque || 1) || 1;
            const baseEmpaque = parseFloat(item.empaque) || 1;
            const costoUnitarioBase = parseFloat(item.costo_unitario) || 0;
            let precioCalculado = (costoUnitarioBase / baseEmpaque) * empaqueInicial;
            const precioConImpuesto = precio_unitario != null ? parseFloat(precio_unitario) : precioCalculado;

            const existingRow = [...tbody.querySelectorAll('tr')].find(row => row.dataset.productoId == item.id);
            if (existingRow) {
                const inputCantidad = existingRow.querySelector('.inputCantidad');
                inputCantidad.value = parseFloat(inputCantidad.value || 0) + cantidad;
                if (this.saldosPendientes && this.saldosPendientes[item.id]) {
                    this.validarFila(existingRow, item.id);
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
                    <input type="hidden" name="detalles[${rowCount}][producto_id]" value="${item.id}">
                    <input type="hidden" name="detalles[${rowCount}][unidad_codigo]" value="${item.unidad_codigo || ''}">
                    <input type="hidden" name="detalles[${rowCount}][empaque]" value="${empaqueInicial}">
                `;

                // ─── Evento: cambio de cantidad_kgm (validar contra saldo pendiente) ───
                const inputCantidadKgm = tr.querySelector('.inputCantidadKgm');
                const inputCantidadObj = tr.querySelector('.inputCantidad');
                inputCantidadKgm.addEventListener('input', (e) => {
                    const empaque = parseFloat(tr.dataset.empaque || 1);
                    const cantidadKgm = parseFloat(e.target.value) || 0;

                    if (this.saldosPendientes && this.saldosPendientes[item.id]) {
                        const saldoPendiente = parseFloat(this.saldosPendientes[item.id].pendiente);
                        if (cantidadKgm > (saldoPendiente + 0.0001)) {
                            e.target.value = saldoPendiente.toFixed(2);
                            inputCantidadObj.value = (saldoPendiente / empaque).toFixed(2);
                        } else {
                            inputCantidadObj.value = (cantidadKgm / empaque).toFixed(2);
                        }
                    } else {
                        inputCantidadObj.value = empaque > 0 ? (cantidadKgm / empaque).toFixed(2) : 0;
                    }

                    tr.dataset.totalManual = "0";
                    this.recalcularFilaAuto(tr);
                });

                // ─── Evento: cambio directo de cantidad (validar contra saldo pendiente) ───
                inputCantidadObj.addEventListener('input', (e) => {
                    const empaque = parseFloat(tr.dataset.empaque || 1);
                    const cantidad = parseFloat(e.target.value) || 0;
                    const cantidadKgm = cantidad * empaque;

                    if (this.saldosPendientes && this.saldosPendientes[item.id]) {
                        const saldoPendiente = parseFloat(this.saldosPendientes[item.id].pendiente);
                        if (cantidadKgm > (saldoPendiente + 0.0001)) {
                            e.target.value = (saldoPendiente / empaque).toFixed(2);
                            inputCantidadKgm.value = saldoPendiente.toFixed(2);
                        } else {
                            inputCantidadKgm.value = cantidadKgm.toFixed(2);
                        }
                    } else {
                        inputCantidadKgm.value = cantidadKgm.toFixed(2);
                    }

                    tr.dataset.totalManual = "0";
                    this.recalcularFilaAuto(tr);
                });

                // ─── Evento: cambio de unidad (recacular cantidad según nuevo empaque) ───
                const selectUnidad = tr.querySelector('.selectUnidad');
                if (selectUnidad) {
                    selectUnidad.addEventListener('change', (e) => {
                        const nuevaUnidadCodigo = e.target.value;
                        const nuevaOpcion = e.target.options[e.target.selectedIndex];
                        const nuevoEmpaque = parseFloat(nuevaOpcion.dataset.empaque) || 1;
                        const kgmActual = parseFloat(inputCantidadKgm.value) || 0;

                        tr.dataset.empaque = nuevoEmpaque;
                        tr.querySelector('.tdEmpaque').textContent = nuevoEmpaque.toFixed(2);
                        tr.querySelector('input[name*="[empaque]"]').value = nuevoEmpaque;

                        const nuevaCantidad = nuevoEmpaque > 0 ? (kgmActual / nuevoEmpaque) : 0;
                        inputCantidadObj.value = nuevaCantidad.toFixed(2);
                        inputCantidadKgm.value = kgmActual.toFixed(2);

                        tr.dataset.totalManual = "0";
                        this.recalcularFilaAuto(tr);
                    });
                }

                // ─── Evento: eliminar fila ───
                tr.querySelector('.btnEliminarFila').addEventListener('click', () => {
                    tr.remove();
                    this.reindexDetalles();
                    this.calculateTotals();
                });

                tbody.appendChild(tr);
                this.calculateTotals();
            }
        }

        recalcularFilaAuto(tr) {
            const inputCantidad = tr.querySelector('.inputCantidad');
            const inputPrecio = tr.querySelector('.inputPrecioUnitario');
            const inputTotal = tr.querySelector('.inputTotal');
            const empaque = parseFloat(tr.dataset.empaque) || 1;

            const cantidad = parseFloat(inputCantidad.value) || 0;
            const precio = parseFloat(inputPrecio.value) || 0;
            const total = cantidad * precio;

            inputTotal.value = total.toFixed(2);
            const inputCantidadKgm = tr.querySelector('.inputCantidadKgm');
            if (inputCantidadKgm) {
                inputCantidadKgm.value = (cantidad * empaque).toFixed(2);
            }
            this.calculateTotals();
        }

        reindexDetalles() {
            const tbody = document.querySelector('#tablaDetalles tbody');
            if (!tbody) return;
            [...tbody.querySelectorAll('tr')].forEach((tr, index) => {
                const num = index + 1;
                tr.querySelector('td:nth-child(2)').textContent = num;
                const inputs = tr.querySelectorAll('input');
                inputs.forEach(input => {
                    const name = input.name;
                    if (name) {
                        input.name = name.replace(/detalles\[\d+\]/, `detalles[${num}]`);
                    }
                });
            });
        }

        calculateTotals() {
            const tbody = document.querySelector('#tablaDetalles tbody');
            if (!tbody) return;
            let total = 0;
            tbody.querySelectorAll('tr').forEach(row => {
                const totalInput = row.querySelector('.inputTotal');
                if (totalInput) {
                    total += parseFloat(totalInput.value) || 0;
                }
            });
            document.getElementById('total').value = total.toFixed(2);
        }

        validarFila(row, productoId) {
            if (!row || !productoId) return;
            const inputCantidad = row.querySelector('.inputCantidad');
            const inputCantidadKgm = row.querySelector('.inputCantidadKgm');
            const inputPrecio = row.querySelector('.inputPrecioUnitario');
            const empaque = parseFloat(row.dataset.empaque) || 1;

            if (!inputCantidad || !inputCantidadKgm) return;

            const cantidad = parseFloat(inputCantidad.value) || 0;
            const cantidadKgm = cantidad * empaque;

            if (this.saldosPendientes && this.saldosPendientes[productoId]) {
                const saldoPendiente = parseFloat(this.saldosPendientes[productoId].pendiente);
                if (cantidadKgm > (saldoPendiente + 0.0001)) {
                    inputCantidad.value = (saldoPendiente / empaque).toFixed(2);
                    inputCantidadKgm.value = saldoPendiente.toFixed(2);
                    const precio = parseFloat(inputPrecio?.value) || 0;
                    row.querySelector('.inputTotal').value = ((saldoPendiente / empaque) * precio).toFixed(2);
                    this.calculateTotals();
                    return;
                }
            }

            inputCantidadKgm.value = cantidadKgm.toFixed(2);
        }
    }

    const prestamoManager = new PrestamoManagerDevolucion();

    document.getElementById('mnuPrestamos').classList.add('menu-open');
    document.getElementById('itemReportePrestamos')?.classList.add('active');
</script>
@endpush