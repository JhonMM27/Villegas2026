@extends('plantilla.app')

@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">

            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-1">
                    <h3 class="card-title flex-grow-1">Provisional Compras</h3>

                    @can('compra_provisionales_create')
                    <button type="button" class="btn btn-primary" id="btnCreate">
                        <i class="bi bi-plus-circle"></i> Nuevo
                    </button>
                    @endcan
                </div>

                <div class="card-body">
                    <ul class="nav nav-tabs mb-3" id="provisionalTabs">
                        <li class="nav-item">
                            <button class="nav-link active" data-tipo="T">Todos</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-tipo="PL">Provisionales libres (adelantos)</button>
                        </li>
                    </ul>
                    <div class="table-responsive">
                        <table id="listadoTable" class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Opciones</th>
                                    <th>Usuario</th>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Recibo</th>
                                    <th>Interno</th>
                                    <th>Proveedor</th>
                                    <th>Monto</th>
                                    <th>Principal</th>
                                    <th>Depósito</th>
                                    <th>Consorcio</th>
                                    <th>Documentos</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                </div>

                <div class="card-footer clearfix"></div>
            </div>

        </div>
    </div>
    <!--end::Row-->
</div>

@canany(['compra_provisionales_create', 'compra_provisionales_edit'])
    @include('compra-provisionales.action')
@endcanany
<div id="modalContainer"></div>
@endsection

@push('scripts')
<script>
class CompraProvisionalManager extends CrudManager {
    constructor() {
        super("{{ url('compra-provisionales') }}");
         this.consulta = 'T';
        this.TIPOS_VALIDOS = ['T', 'PL'];
        this.initializeDataTable();

        this.setupLiveSearchSelect({
            inputId: 'proveedor_nombre',
            hiddenId: 'proveedor_id',
            url: "{{ route('proveedores.buscar') }}",
            template: (item) => {
                const doc = item.documento_numero;
                return doc
                    ? `${item.id} - ${item.razon_social} (${doc})`
                    : `${item.id} - ${item.razon_social}`;
            },
            getId: item => item.id,
            minLength : 1,
            delay : 300,
            onSelect: (item) => this.addCompra(item.id)
        });

        // Botón distribuir
        document.getElementById('btnDistribuir')?.addEventListener('click', () => this.distribuirCobranza());

        // (Opcional) si cambia total_cobranza, vuelve a distribuir
        document.getElementById('total_cobranza')?.addEventListener('input', () => this.distribuirSiHayCompras());
        document.getElementById('consorcio')?.addEventListener('input', () => this.distribuirSiHayCompras());
        document.getElementById('principal')?.addEventListener('input', () => this.distribuirSiHayCompras());
        document.getElementById('deposito')?.addEventListener('input', () => this.distribuirSiHayCompras());

        window.addEventListener('popstate', () => {
            const tipo = provisionalManager.getTipoFromUrl();

            provisionalManager.consulta = tipo;

            document.querySelectorAll('#provisionalTabs button').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.tipo === tipo);
            });

            provisionalManager.tabla.ajax.reload();
        });

        document.querySelectorAll('#provisionalTabs button').forEach(btn => {
            btn.addEventListener('click', e => {

                const tipo = e.target.dataset.tipo;

                // UI
                document.querySelectorAll('#provisionalTabs button')
                    .forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');

                // Estado
                provisionalManager.consulta = tipo;

                // URL
                this.updateUrl(tipo);

                // Reload DataTable
                provisionalManager.tabla.ajax.reload();
            });
        });
    }

    tiposConfig = {
        T: {
            tipo: 'T'
        },
        PL: {
            tipo: 'PL'
        }
    };

    getTipoFromUrl() {
        const params = new URLSearchParams(window.location.search);
        const tipo = params.get('tipo');
        return (tipo === 'PL' || tipo === 'T') ? tipo : 'T';
    }

    updateUrl(tipo) {
        const params = new URLSearchParams(window.location.search);

        if (params.get('tipo') === tipo) return;

        params.set('tipo', tipo);
        window.history.pushState({ tipo }, '', `${location.pathname}?${params}`);
    }

    tieneComprasEnTabla() {
        return document.querySelectorAll('#tablaVentasSaldo tbody input[name*="[monto]"]').length > 0;
    }

    distribuirSiHayCompras() {
        if (!this.tieneComprasEnTabla()) return;
        this.distribuirCobranza();
    }

    async addCompra(proveedorId) {
        const tbody = document.querySelector('#tablaComprasSaldo tbody');

        const fmt = (n) => {
            const x = parseFloat(n ?? 0);
            return x.toLocaleString('es-PE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };

        if (!proveedorId) {
            tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-muted text-center py-3">Seleccione un proveedor…</td>
            </tr>`;
            return;
        }

        tbody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center py-3">Cargando compras con saldo...
        </tr>`;

        try {

            const url = `{{ route('compra-provisionales.compras-con-saldo') }}?proveedor_id=${encodeURIComponent(proveedorId)}`;
            const resp = await this.fetchData(url);

            const rows = Array.isArray(resp) ? resp : (resp.data ?? resp.compras ?? []);

            if (!rows.length) {
                tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-muted text-center py-3">
                        Este proveedor no tiene compras con saldo.
                    </td>
                </tr>`;
                return;
            }

            tbody.innerHTML = rows.map((r, i) => {

                const doc = `${r.comprobante_tipo_codigo ?? ''} ${r.serie ?? ''}-${r.correlativo ?? ''}`.trim();

                return `
                <tr>

                    <td>
                        ${r.id}

                        <input type="hidden" name="compras[${i}][compra_id]" value="${r.id}">
                        <input type="hidden" name="compras[${i}][comprobante_tipo_codigo]" value="${r.comprobante_tipo_codigo}">
                        <input type="hidden" name="compras[${i}][serie]" value="${r.serie}">
                        <input type="hidden" name="compras[${i}][correlativo]" value="${r.correlativo}">
                    </td>

                    <td>${doc}</td>
                    <td>${r.fecha_compra ?? ''}</td>

                    <td class="text-end">${fmt(r.total)}</td>
                    <td class="text-end">${fmt(r.acuenta)}</td>
                    <td class="text-end">${fmt(r.abonos)}</td>

                    <td class="text-end fw-bold">
                        ${fmt(r.saldo)}
                    </td>

                    <td>
                        <input type="number"
                            step="0.01"
                            min="0"
                            max="${r.saldo}"
                            value="0.00"
                            class="form-control form-control-sm text-end"
                            name="compras[${i}][monto]">
                    </td>

                </tr>`;
            }).join('');
            this.distribuirCobranza();

        } catch (err) {

            console.error(err);

            tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-danger text-center py-3">
                    Error al cargar compras con saldo.
                </td>
            </tr>`;
        }
    }

    

    distribuirCobranza() {
        const totalEl = document.getElementById('total_cobranza');
        const tbody = document.querySelector('#tablaComprasSaldo tbody');

        if (!totalEl || !tbody) return;

        const toNumber = (val) => {
            return parseFloat(
                (val ?? '0').toString()
                    .replace(',', '.')
                    .replace(/[^\d.-]/g, '')
            ) || 0;
        };

        const round2 = (n) => Math.round((n + Number.EPSILON) * 100) / 100;

        // disponible = total_cobranza
        let disponible = round2(toNumber(totalEl.value));

        // Si no hay filas reales (solo el mensaje), salir
        const montoInputs = tbody.querySelectorAll('input[name*="[monto]"]');
        if (!montoInputs.length) return;

        // Reset montos antes de distribuir
        montoInputs.forEach(inp => inp.value = '0.00');

        // Distribuir en orden de la tabla
        montoInputs.forEach(inp => {
            if (disponible <= 0) return;

            const maxAttr = inp.getAttribute('max');
            const saldo = round2(toNumber(maxAttr));

            if (saldo <= 0) {
                inp.value = '0.00';
                return;
            }

            const asignado = round2(Math.min(disponible, saldo));
            inp.value = asignado.toFixed(2);

            disponible = round2(disponible - asignado);
        });
    }

    limpiarTablaCompras() {
        const tbody = document.querySelector('#tablaComprasSaldo tbody');

        if (!tbody) return;

        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-muted text-center py-3">
                    Seleccione un proveedor…
                </td>
            </tr>
        `;
    }


    initializeDataTable() {
        this.tabla = $(this.elements.table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: this.baseUrl,
                type: 'GET',
                 data: d => {
                    d.tipo = this.consulta;
                },
                error: function (xhr) {
                    console.log('DT error:', xhr.status, xhr.responseText);
                }
            },
            columns: [
                { data: 'action', orderable: false, searchable: false },   // 1 Opciones

                { data: 'user_nombre', name: 'user_nombre' },              // 2 Usuario
                { data: 'fecha_provisional', name: 'fecha_provisional' },  // 3 Fecha
                { data: 'tipo', name: 'tipo' },                            // 4 Tipo
                { data: 'numero_recibo', name: 'numero_recibo' },          // 5 Recibo
                { data: 'numero_interno', name: 'numero_interno' },        // 6 Interno
                { data: 'proveedor_nombre', name: 'proveedor_nombre' },        // 7 Proveedor

                { data: 'monto', name: 'monto', className: 'text-end' },   // 8 Monto
                { data: 'importe_p', name: 'importe_p', className: 'text-end' }, // 9 Principal
                { data: 'importe_d', name: 'importe_d', className: 'text-end' }, // 10 Depósito
                { data: 'importe_c', name: 'importe_c', className: 'text-end' }, // 11 Consorcio

                { data: 'documentos', orderable: false, searchable: false } // 12 Documentos
            ],
            columnDefs: [
                { targets: 0, width: '10%', className: 'text-center' },
                { targets: 1, width: '10%' },
                { targets: 2, width: '17%' },
                { targets: 3, width: '8%' },
                { targets: 4, width: '8%' },
                { targets: 5, width: '8%' },
                { targets: 6, width: '8%' },
                { targets: 7, width: '8%', className: 'text-end' },
                { targets: 8, width: '5%' },
                { targets: 9, width: '5%' },
                { targets: 10, width: '5%' },
                { targets: 11, width: '8%' }
            ],
            responsive: true,
            order: [[2, 'desc']]
        });
    }

    async showEditModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);

            this.isEditing = true;
            this.resetForm();

            this.elements.modalTitle.textContent = 'Editar Provisional #' + response.numero_recibo;
            this.elements.methodField.value = 'PUT';

            // Campos del modal (ajusta IDs según tu action.blade.php)
            this.setFieldValue('fecha_provisional', this.formatDateTimeLocal(response.fecha_provisional));
            //document.getElementById('numero_recibo').value = response.numero_recibo || '';
            document.getElementById('numero_interno').value = response.numero_interno || '';
            document.getElementById('proveedor_id').value = response.proveedor_id || '';
            document.getElementById('proveedor_nombre').value = response.proveedor_nombre || '';
            document.getElementById('total_cobranza').value = response.monto || 0;
            document.getElementById('principal').value = response.importe_p || 0;
            document.getElementById('deposito').value = response.importe_d || 0;
            document.getElementById('consorcio').value = response.importe_c || 0;

            this.cargarComprasEnTabla(response.detalles || []);

            document.getElementById('usuario_nombre').textContent = response.user_nombre|| '';

            this.form.action = `${this.baseUrl}/${id}`;
            this.modal.show();

        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error(error);
        }
    }

    cargarComprasEnTabla(detalles = []) {
        const tbody = document.querySelector('#tablaComprasSaldo tbody');

        const fmt = (n) => {
            const x = parseFloat(n ?? 0);
            return x.toLocaleString('es-PE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };

        if (!tbody) return;

        if (!detalles.length) {
            tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-muted text-center py-3">
                    Este provisional no tiene ventas aplicadas.
                </td>
            </tr>`;
            return;
        }

        tbody.innerHTML = detalles.map((d, i) => {

            // Venta relacionada (traída desde backend con with('detalles.venta'))
            const v = d.compra ?? {};

            // Documento desde la VENTA (más confiable que duplicarlo en detalle)
            const doc = `${v.comprobante_tipo_codigo ?? ''} ${v.serie ?? ''}-${v.correlativo ?? ''}`.trim();

            // ✅ ESTE es el monto aplicado guardado en venta_provisional_detalles
            const montoDetalle = parseFloat(d.monto ?? 0);

            return `
            <tr data-compra-id="${d.compra_id}">

                <td style="width:70px">
                    ${d.compra_id}

                    <input type="hidden" name="compras[${i}][compra_id]" value="${d.compra_id}">
                    <input type="hidden" name="compras[${i}][detalle_id]" value="${d.id ?? ''}">
                    <input type="hidden" name="compras[${i}][comprobante_tipo_codigo]" value="${d.comprobante_tipo_codigo}">
                    <input type="hidden" name="compras[${i}][serie]" value="${d.serie}">
                    <input type="hidden" name="compras[${i}][correlativo]" value="${d.correlativo}">
                </td>

                <td>${doc}</td>

                <td>${v.fecha_compra ?? ''}</td>

                <td class="text-end">${fmt(v.total)}</td>

                <td class="text-end">${fmt(v.acuenta)}</td>

                <td class="text-end">${fmt(v.abonos)}</td>

                <td class="text-end fw-bold">${fmt(v.saldo)}</td>

                <!-- ✅ Monto = venta_provisional_detalle.monto -->
                <td class="text-end">
                    <input type="number"
                        step="0.01"
                        min="0"
                        value="${montoDetalle.toFixed(2)}"
                        class="form-control form-control-sm text-end"
                        name="compras[${i}][monto]">
                </td>

            </tr>`;
        }).join('');
        if (typeof this.distribuirCobranza === 'function' && !this.isEditing) {
            this.distribuirCobranza();
        }
    }

    showCreateModal(){
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nuevo Provisional';
        const usuarioNombre = @json(auth()->user()->name);
        document.getElementById('usuario_nombre').textContent = usuarioNombre;

        // Defaults (si quieres)
        this.setFieldValue('fecha_provisional', this.obtenerFechaHoraActual());
        this.limpiarTablaCompras();
    }

    focusFirstField() {
        const modalEl = this.modal._element;
        modalEl.addEventListener('shown.bs.modal', () => {
            // Se enfoca 'numero_interno' en lugar de 'fecha_provisional' para evitar
            // que se abra automáticamente el selector de fecha (calendario).
            const input = document.getElementById('numero_interno');
            if (input) input.focus();
        }, { once: true });
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
}

document.addEventListener('DOMContentLoaded', () => {
    provisionalManager = new CompraProvisionalManager();
    document.body.addEventListener('click', function(e) {
        if (e.target && (e.target.matches('.btn-view-compra') || e.target.closest('.btn-view-compra'))) {
            const button = e.target.closest('.btn-view-compra');
            const compraId = button.getAttribute('data-id');
            if (!compraId) return;
            
            const url = "{{ route('compra-provisionales.ver', ':id') }}".replace(':id', compraId);

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('No se pudo cargar el provisional');
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

// Menú activo (ajusta IDs según tu sidebar)
document.getElementById('mnuCaja')?.classList.add('menu-open');
document.getElementById('itemCompraProvisionales')?.classList.add('active');
</script>
@endpush
