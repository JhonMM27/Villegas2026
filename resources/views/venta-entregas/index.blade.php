@extends('plantilla.app')

@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">

            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-1">
                    <h3 class="card-title flex-grow-1">Entrega Ventas</h3>

                    @can('venta_entregas_create')
                    <button type="button" class="btn btn-primary" id="btnCreate">
                        <i class="bi bi-plus-circle"></i> Nuevo
                    </button>
                    @endcan
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="listadoTable" class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Opciones</th>
                                    <th>Usuario</th>
                                    <th>Fecha Entrega</th>
                                    <th>Recibo</th>
                                    <th>Comprobante Tipo</th>
                                    <th>Serie</th>
                                    <th>Correlativo</th>
                                    <th>Cliente</th>
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

@canany(['venta_entregas_create', 'venta_entregas_edit'])
    @include('venta-entregas.action')
@endcanany
<div id="modalContainer"></div>
@endsection

@push('scripts')
<script>
class VentaEntregaManager extends CrudManager {
    constructor() {
        super("{{ url('venta-entregas') }}");
        this.afterSuccess = this.handleEntregaSuccess.bind(this);
        this.initializeDataTable();

        this.setupLiveSearchSelect({
            inputId: 'cliente_nombre',
            hiddenId: 'cliente_id',
            url: "{{ route('clientes.buscar') }}",
            template: (item) => {
                const doc = item.documento_numero;
                return doc
                    ? `${item.id} - ${item.razon_social} (${doc})`
                    : `${item.id} - ${item.razon_social}`;
            },
            getId: item => item.id,
            minLength : 1,
            delay : 300,
            onSelect: (item) => this.addVenta(item.id)
        });
        document.body.addEventListener('click', (e) => {
            const btnAdd = e.target.closest('.btn-add-venta');
            if (btnAdd) {
                e.preventDefault();

                const ventaId = btnAdd.dataset.id;
                if (!ventaId) return;

                this.cargarDetalleVenta(ventaId);
                return; // importante: no seguir evaluando
            }

            const btnView = e.target.closest('.btn-view-venta');
            if (btnView) {
                e.preventDefault();

                const id = btnView.dataset.id;
                if (!id) return;

                const url = "{{ route('venta-entregas.ver', ':id') }}".replace(':id', id);

                fetch(url)
                    .then(r => {
                        if (!r.ok) throw new Error('No se pudo cargar la venta');
                        return r.text();
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
                    .catch(err => console.error(err));

                return;
            }
        });
    }

    async addVenta(clienteId) {
        const tbody = document.querySelector('#tablaVentasEntrega tbody');

        const fmt = (n) => {
            const x = parseFloat(n ?? 0);
            return x.toLocaleString('es-PE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };

        if (!clienteId) {
            tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-muted text-center py-3">Seleccione un cliente…</td>
            </tr>`;
            return;
        }

        tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-3">Cargando ventas por entregar...</td>
        </tr>`;

        try {

            const url = `{{ route('venta-entregas.ventas-por-entregar') }}?cliente_id=${encodeURIComponent(clienteId)}`;
            const resp = await this.fetchData(url);

            const rows = Array.isArray(resp) ? resp : (resp.data ?? resp.ventas ?? []);

            if (!rows.length) {
                tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-muted text-center py-3">
                        Este cliente no tiene ventas por entregar.
                    </td>
                </tr>`;
                return;
            }

            tbody.innerHTML = rows.map((r, i) => {

                const doc = `${r.comprobante_tipo_codigo ?? ''} ${r.serie ?? ''}-${r.correlativo ?? ''}`.trim();

                return `
                <tr>
                    <td>
                        <button type="button"
                                class="btn btn-sm btn-primary btn-add-venta"
                                data-id="${r.id}">
                            <i class="bi bi-plus-circle"></i>
                        </button>
                    </td>
                    <td>${r.user_nombre ?? ''}</td>
                    <td>${r.cliente_nombre ?? ''}</td>
                    <td>${r.fecha_venta ?? ''}</td>                    
                    <td>${doc}</td>
                    <td class="text-end">${fmt(r.total)}</td>
                    <td>${r.estado ?? ''}</td>
                </tr>`;
            }).join('');

        } catch (err) {

            console.error(err);

            tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-danger text-center py-3">
                    Error al cargar ventas por entregar
                </td>
            </tr>`;
        }
    }

    async cargarDetalleVenta(ventaId) {
        document.getElementById('venta_id').value = ventaId;
        const url = `{{ route('venta-entregas.ventas-por-entregar-detalle', ':id') }}`
                        .replace(':id', ventaId);

        const tbody = document.querySelector('#tablaDetalles tbody');

        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-3">
                    Cargando detalle...
                </td>
            </tr>`;

        try {

            const data = await this.fetchData(url);

            if (!data.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center text-muted">
                            No hay productos pendientes
                        </td>
                    </tr>`;
                return;
            }

            const fmt = (n) => {
                const x = parseFloat(n ?? 0);
                return x.toLocaleString('es-PE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            tbody.innerHTML = data.map((d, i) => `
                <tr>
                    <td>${i + 1}</td>
                    <td>
                        ${d.producto_nombre ?? ''} 
                        <input type="hidden" name="detalles[${i}][producto_id]" value="${d.producto_id}">
                        <input type="hidden" name="detalles[${i}][producto_nombre]" value="${d.producto_nombre ?? ''}">
                    </td>
                    <td class="text-center">${d.unidad_codigo ?? ''}</td>
                    <td class="text-center">
                        ${d.producto_empaque ?? ''}
                        <input type="hidden" name="detalles[${i}][producto_empaque]" value="${d.producto_empaque ?? ''}">
                    </td>
                    <td class="text-end">${fmt(d.cantidad)}</td>
                    <td class="text-end">${fmt(d.precio_unitario ?? 0)}</td>
                    <td class="text-end">${fmt(d.entregado)}</td>
                    <td class="text-end text-danger">${fmt(d.pendiente)}</td>
                    <td>
                        <input type="number"
                            class="form-control form-control-sm text-end"
                            name="detalles[${i}][cantidad]"
                            min="0"
                            max="${d.pendiente}"
                            step="0.01"
                            value="0.00">
                        <input type="hidden" name="detalles[${i}][venta_detalle_id]" value="${d.id}">
                    </td>
                </tr>
            `).join('');

        } catch (error) {
            console.error(error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-danger text-center">
                        Error al cargar detalle
                    </td>
                </tr>`;
        }
    }

    limpiarTablaVentas() {
        const tbody = document.querySelector('#tablaVentasEntrega tbody');

        if (!tbody) return;

        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-muted text-center py-3">
                    Seleccione un cliente…
                </td>
            </tr>
        `;

        const tbodyD = document.querySelector('#tablaDetalles tbody');

        if (!tbodyD) return;

        tbodyD.innerHTML = `
            <tr>
                <td colspan="9" class="text-muted text-center py-3">
                    Seleccione una venta
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
                error: function (xhr) {
                    console.log('DT error:', xhr.status, xhr.responseText);
                }
            },
            columns: [
                { data: 'action', orderable: false, searchable: false },   // 1 Opciones
                { data: 'user_nombre', name: 'user_nombre' },              // 2 Usuario
                { data: 'fecha_entrega', name: 'fecha_entrega', searchable: false },  // 3 Fecha                           // 4 Tipo
                { data: 'numero_recibo', name: 'numero_recibo' },          // 5 Recibo
                { data: 'comprobante_tipo_codigo', name: 'comprobante_tipo_codigo', searchable: false },        // 6 Interno
                { data: 'serie', name: 'serie'}, 
                { data: 'correlativo', name: 'correlativo'}, 
                { data: 'cliente_nombre', name: 'cliente_nombre' }
            ],
            columnDefs: [
                { targets: 0, width: '10%', className: 'text-center' },
                { targets: 1, width: '15%' },
                { targets: 2, width: '15%' },
                { targets: 3, width: '10%' },
                { targets: 4, width: '10%' },
                { targets: 5, width: '10%' },
                { targets: 6, width: '30%' }
            ],
            responsive: true,
            order: [[3, 'desc']]
        });
    }

    async showEditModal(id) {
        try {
            const response = await this.fetchData(`${this.baseUrl}/${id}`);

            this.isEditing = true;
            this.resetForm();

            this.elements.modalTitle.textContent = 'Editar Entrega #' + response.numero_recibo;
            this.elements.methodField.value = 'PUT';

            // Campos del modal (ajusta IDs según tu action.blade.php)
            document.getElementById('venta_id').value = response.venta_id || '';
            document.getElementById('fecha_entrega').value = (response.fecha_entrega || '').replace(' ', 'T').slice(0,16);
            //document.getElementById('numero_recibo').value = response.numero_recibo || '';
            document.getElementById('cliente_id').value = response.cliente_id || '';
            document.getElementById('cliente_nombre').value = response.cliente_nombre || '';
            const comentario = document.getElementById('comentario');
            if (comentario) comentario.value = response.comentario || '';

            this.cargarDetallesEnTabla(response.detalles || []);

            document.getElementById('usuario_nombre').textContent = response.user_nombre|| '';

            this.form.action = `${this.baseUrl}/${id}`;
            this.modal.show();

        } catch (error) {
            this.showNotification('error', 'Error al cargar los datos');
            console.error(error);
        }
    }

    cargarDetallesEnTabla(detalles = []) {
        const tbody = document.querySelector('#tablaDetalles tbody');
        if (!tbody) return;

        const fmt = (n) => {
            const x = parseFloat(n ?? 0);
            return x.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        if (!detalles.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-muted text-center py-3">
                        Esta entrega no tiene productos.
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = detalles.map((d, i) => {

            const entregaEsta = parseFloat(d.entregado_esta ?? 0);
            const empaque = parseFloat(d.venta_detalle.producto_empaque ?? 0);
            const salidaKg = empaque * entregaEsta;

            return `
                <tr>
                    <td>${i + 1}</td>
                    <td>
                        ${d.producto_nombre ?? ''} 
                        <input type="hidden" name="detalles[${i}][producto_id]" value="${d.producto_id}">
                        <input type="hidden" name="detalles[${i}][producto_nombre]" value="${d.venta_detalle.producto_nombre ?? ''}">
                    </td>
                    <td class="text-center">${d.venta_detalle.unidad_codigo ?? ''}</td>
                    <td class="text-center">
                        ${d.producto_empaque ?? ''}
                        <input type="hidden" name="detalles[${i}][producto_empaque]" value="${d.producto_empaque ?? ''}">
                    </td>
                    <td class="text-end">${fmt(d.venta_detalle.cantidad)}</td>
                    <td class="text-end">${fmt(d.venta_detalle.precio_unitario ?? 0)}</td>
                    <td class="text-end">${fmt(d.venta_detalle.entregado)}</td>
                    <td class="text-end text-danger">${fmt(d.venta_detalle.saldo)}</td>
                    <td>
                        <input type="number"
                            class="form-control form-control-sm text-end"
                            name="detalles[${i}][cantidad]"
                            min="0"
                           max="${(
                                parseFloat(d.cantidad ?? 0) +
                                parseFloat(d.venta_detalle?.saldo ?? 0)
                            ).toFixed(2)}"
                            step="0.01"
                            value="${parseFloat(d.cantidad?? 0)}">
                        <input type="hidden" name="detalles[${i}][venta_detalle_id]" value="${d.venta_detalle.id}">
                    </td>
                </tr>
            `;
        }).join('');
    }

    showCreateModal(){
        super.showCreateModal();
        this.elements.modalTitle.textContent = 'Nueva entrega';
        const usuarioNombre = @json(auth()->user()->name);
        document.getElementById('usuario_nombre').textContent = usuarioNombre;

        // Defaults (si quieres)
        document.getElementById('fecha_entrega').value = this.obtenerFechaHoraActual();
        this.limpiarTablaVentas();
    }

    focusFirstField() {
        const modalEl = this.modal._element;
        modalEl.addEventListener('shown.bs.modal', () => {
            const input = document.getElementById('fecha_provisional');
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
    new VentaEntregaManager();
});

// Menú activo (ajusta IDs según tu sidebar)
document.getElementById('mnuSalida')?.classList.add('menu-open');
document.getElementById('itemVentaEntregas')?.classList.add('active');

// ================================================
// Métodos para opción de imprimir ticket
// ================================================
VentaEntregaManager.prototype.handleEntregaSuccess = async function(response, isEditing) {
    if (!isEditing && response.venta_entrega_id) {
        setTimeout(() => {
            this.showTicketOption(response);
        }, 1000);
    }
};

VentaEntregaManager.prototype.showTicketOption = async function(response) {
    const result = await Swal.fire({
        title: 'Entrega registrada!',
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
        const imprimirRuta = "{{ route('venta-entregas.imprimir', ':id') }}";
        window.open(imprimirRuta.replace(':id', response.venta_entrega_id), '_blank');
    }
};
</script>
@endpush
