@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Cuadre de Stock</h3>
                    @can('cuadre_stock_create')
                        <button type="button" class="btn btn-primary" id="btnCreate">
                            <i class="bi bi-plus-circle"></i> Nuevo Cuadre
                        </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="listadoTable" class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Opciones</th>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Items</th>
                                    <th>Usuario</th>
                                    <th>Notas</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="modalVerContainer"></div>
@can('cuadre_stock_create')
    @include('cuadre_stock.action')
@endcan
@can('cuadre_stock_edit')
<div class="modal fade" id="modalRectificar" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formRectificar" method="post">
                @csrf
                <div class="modal-header py-2">
                    <h5 class="modal-title">Rectificar Cuadre de Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-3 border-bottom bg-light">
                        <div class="row g-3 mb-3">
                            <div class="col-lg-3 col-md-4">
                                <label for="rectificarFecha" class="form-label form-label-sm small fw-bold">Fecha</label>
                                <input type="text" id="rectificarFecha" class="form-control form-control-sm date-picker" disabled>
                            </div>
                            <div class="col-lg-5 col-md-4">
                                <label for="rectificarNotas" class="form-label form-label-sm small fw-bold">Notas</label>
                                <input type="text" id="rectificarNotas" class="form-control form-control-sm" placeholder="Observaciones...">
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <label for="filtroProductosRectificar" class="form-label form-label-sm small fw-bold">Buscar producto:</label>
                                <input type="text" id="filtroProductosRectificar" class="form-control form-control-sm" placeholder="Nombre, código o ID..." autocomplete="off">
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" id="btnUsarKardexRectificar" class="btn btn-info btn-sm">
                                <i class="bi bi-arrow-down"></i> Stock Kardex
                            </button>
                        </div>
                    </div>

                    <div style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-hover mb-0" id="tablaProductosRectificar" style="font-size: 0.85rem;">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th style="width: 50%;">Producto</th>
                                    <th class="text-end" style="width: 25%;">Stock Sist.</th>
                                    <th style="width: 25%;">Stock Físico</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productos as $producto)
                                <tr data-producto-id="{{ $producto->id }}" data-nombre="{{ strtolower($producto->nombre) }}" data-codigo="{{ strtolower($producto->codigo ?? '') }}">
                                    <td class="text-nowrap">
                                        <small class="text-muted me-1">({{ $producto->id }})</small>{{ $producto->nombre }}
                                        @if($producto->codigo)
                                            <small class="text-muted">- {{ $producto->codigo }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end align-middle">
                                        {{ number_format((float)$producto->stock_almacen, 4) }}
                                    </td>
                                    <td>
                                        <input type="number" 
                                            class="form-control form-control-sm stock-fisico-rectificar" 
                                            step="0.0001"
                                            min="0"
                                            placeholder="0">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div></div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" id="btnRectificar" class="btn btn-warning btn-sm">
                            <i class="bi bi-check-circle"></i> Confirmar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection
@push('scripts')
<script>
class CuadreStockManager extends CrudManager {
    constructor() {
        super();
        this.baseUrl = "{{ url('cuadre-stock') }}";
        this.productos = @json($productos);
        this.initializeDataTable();
        this.setupEventListeners();
    }

    initializeDataTable() {
        this.tabla = $('#listadoTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: this.baseUrl, type: 'GET' },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'id', name: 'id' },
                { data: 'fecha', name: 'fecha' },
                { data: 'estado', name: 'estado' },
                { data: 'detalles_count', name: 'detalles_count' },
                { data: 'user_id', name: 'user_id' },
                { data: 'notas', name: 'notas' }
            ]
        });
    }

    setupEventListeners() {
        document.getElementById('btnCreate')?.addEventListener('click', () => this.showCreateModal());
        document.getElementById('btnUsarKardex')?.addEventListener('click', () => this.usarKardexComoFisico());
        document.getElementById('btnLimpiar')?.addEventListener('click', () => this.limpiarCampos());
        document.getElementById('formCuadre')?.addEventListener('submit', (e) => this.handleSubmit(e));
        document.getElementById('btnPreview')?.addEventListener('click', () => this.generarPreview());
        document.getElementById('formRectificar')?.addEventListener('submit', (e) => this.handleRectificarSubmit(e));
        document.getElementById('btnUsarKardexRectificar')?.addEventListener('click', () => this.usarKardexComoFisicoRectificar());
        document.getElementById('filtroProductos')?.addEventListener('input', (e) => this.filtrarTablaProductos(e.target.value, 'tablaProductosCuadre'));
        document.getElementById('filtroProductosRectificar')?.addEventListener('input', (e) => this.filtrarTablaProductos(e.target.value, 'tablaProductosRectificar'));
    }

    filtrarTablaProductos(texto, tablaId) {
        const tabla = document.getElementById(tablaId);
        if (!tabla) return;
        const tbody = tabla.querySelector('tbody');
        if (!tbody) return;
        const filas = tbody.querySelectorAll('tr');
        const textoLower = texto.toLowerCase().trim();

        filas.forEach(fila => {
            const nombre = fila.dataset.nombre || '';
            const codigo = fila.dataset.codigo || '';
            const productoId = String(fila.dataset.productoId || '');
            const coincide = nombre.includes(textoLower) || codigo.includes(textoLower) || productoId.includes(textoLower);
            fila.style.display = coincide ? '' : 'none';
        });
    }

    usarKardexComoFisicoRectificar() {
        document.querySelectorAll('#tablaProductosRectificar tbody tr:not([style*="display: none"]) .stock-fisico-rectificar').forEach((input) => {
            const productoId = input.dataset.productoId;
            const producto = this.productos.find(p => p.id == productoId);
            if (producto) {
                input.value = parseFloat(producto.stock_almacen).toFixed(4);
            }
        });
    }

    usarKardexComoFisico() {
        document.querySelectorAll('#tablaProductosCuadre tbody tr:not([style*="display: none"]) .stock-fisico-input').forEach((input) => {
            const productoId = input.closest('tr').dataset.productoId;
            const producto = this.productos.find(p => p.id == productoId);
            if (producto) {
                input.value = parseFloat(producto.stock_almacen).toFixed(4);
            }
        });
        this.generarPreview();
    }

    limpiarCampos() {
        document.querySelectorAll('.stock-fisico-input').forEach(input => {
            input.value = '';
        });
        document.getElementById('previewContainer').innerHTML = '';
        document.getElementById('notas').value = '';
    }

    async generarPreview() {
        const filasVisibles = document.querySelectorAll('#tablaProductosCuadre tbody tr:not([style*="display: none"])');
        const detalles = [];

        filasVisibles.forEach(fila => {
            const input = fila.querySelector('.stock-fisico-input');
            if (input?.value) {
                detalles.push({
                    producto_id: parseInt(fila.dataset.productoId),
                    stock_fisico: parseFloat(input.value)
                });
            }
        });

        if (detalles.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin datos',
                text: 'Ingrese al menos un stock físico',
                toast: true,
                position: 'top-end'
            });
            return;
        }

        try {
            const response = await fetch("{{ route('cuadre-stock.preview') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ detalles })
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarPreview(data.preview);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        } catch (error) {
            console.error(error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error al generar preview' });
        }
    }

    mostrarPreview(preview) {
        const container = document.getElementById('previewContainer');
        
        if (preview.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No hay diferencias calculadas</div>';
            return;
        }

        const conDiferencia = preview.filter(p => p.diferencia !== null && p.diferencia !== 0);
        
        if (conDiferencia.length === 0) {
            container.innerHTML = '<div class="alert alert-success">Todos los stocks coinciden</div>';
            return;
        }

        let html = `
        <div class="table-responsive mt-3">
            <table class="table table-sm table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Producto</th>
                        <th class="text-right">Stock Sistema</th>
                        <th class="text-right">Stock Kardex</th>
                        <th class="text-right">Stock Físico</th>
                        <th class="text-right">Diferencia</th>
                        <th>Tipo</th>
                    </tr>
                </thead>
                <tbody>
        `;

        conDiferencia.forEach(p => {
            const tipoClass = p.tipo === 'entrada' ? 'text-success' : 'text-danger';
            const tipoText = p.tipo === 'entrada' ? 'ENTRADA (+)' : 'SALIDA (-)';
            const diffSign = p.diferencia > 0 ? '+' : '';
            
            html += `
            <tr>
                <td>${p.producto_nombre}</td>
                <td class="text-right">${parseFloat(p.stock_sistema).toFixed(4)}</td>
                <td class="text-right">${parseFloat(p.stock_kardex).toFixed(4)}</td>
                <td class="text-right">${parseFloat(p.stock_fisico).toFixed(4)}</td>
                <td class="text-right ${tipoClass} fw-bold">${diffSign}${parseFloat(p.diferencia).toFixed(4)}</td>
                <td><span class="badge bg-${p.tipo === 'entrada' ? 'success' : 'danger'}">${tipoText}</span></td>
            </tr>
            `;
            
            if (p.discrepancia_kardex) {
                html += `
                <tr class="table-warning">
                    <td colspan="6" class="text-center small">
                        <i class="bi bi-exclamation-triangle"></i> 
                        Alerta: stock_tabla (${parseFloat(p.stock_sistema).toFixed(4)}) ≠ stock_kardex (${parseFloat(p.stock_kardex).toFixed(4)})
                    </td>
                </tr>
                `;
            }
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
    }

    async handleSubmit(e) {
        e.preventDefault();

        const filasVisibles = document.querySelectorAll('#tablaProductosCuadre tbody tr:not([style*="display: none"])');
        const detalles = [];

        filasVisibles.forEach(fila => {
            const input = fila.querySelector('.stock-fisico-input');
            if (input?.value) {
                detalles.push({
                    producto_id: parseInt(fila.dataset.productoId),
                    stock_fisico: parseFloat(input.value)
                });
            }
        });

        const previewEl = document.querySelectorAll('#previewContainer tbody tr');
        if (previewEl.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Preview requerido',
                text: 'Genere el preview antes de confirmar'
            });
            return;
        }

        const formData = {
            fecha: document.getElementById('fecha').value,
            notas: document.getElementById('notas').value,
            detalles: detalles
        };

        try {
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: data.message
                });
                bootstrap.Modal.getInstance(document.getElementById('modalCuadre')).hide();
                this.tabla.ajax.reload();
                this.limpiarCampos();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        } catch (error) {
            console.error(error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error al guardar' });
        }
    }

    showCreateModal() {
        this.setFieldValue('fecha', new Date().toISOString().split('T')[0]);
        document.getElementById('notas').value = '';
        document.getElementById('filtroProductos').value = '';
        document.getElementById('previewContainer').innerHTML = '';
        
        document.querySelectorAll('#tablaProductosCuadre tbody tr').forEach(fila => {
            fila.style.display = '';
        });

        document.querySelectorAll('.stock-fisico-input').forEach(input => {
            input.value = '';
        });

        new bootstrap.Modal(document.getElementById('modalCuadre')).show();
    }

    async verDetalle(id) {
        try {
            const response = await fetch(`${this.baseUrl}/${id}`);
            const data = await response.json();

            if (!data.success) {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                return;
            }

            const cuadre = data.cuadre;
            let html = `
            <div class="modal fade" id="modalVerCuadre" data-bs-backdrop="static">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Detalle del Cuadre #${cuadre.id}</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-4">
                                    <strong>Fecha:</strong> ${cuadre.fecha}
                                </div>
                                <div class="col-4">
                                    <strong>Estado:</strong> 
                                    <span class="badge bg-${cuadre.estado === 'completado' ? 'success' : 'secondary'}">${cuadre.estado}</span>
                                </div>
                                <div class="col-4">
                                    <strong>Usuario:</strong> ${cuadre.user?.name ?? 'N/A'}
                                </div>
                            </div>
                            ${cuadre.notas ? `<div class="mb-3"><strong>Notas:</strong> ${cuadre.notas}</div>` : ''}
                            <hr>
                            <h5>Detalle de Productos</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th class="text-right">Stock Sistema</th>
                                            <th class="text-right">Stock Físico</th>
                                            <th class="text-right">Diferencia</th>
                                            <th>Tipo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
            `;

            cuadre.detalles.forEach(d => {
                const tipoClass = d.tipo === 'entrada' ? 'success' : 'danger';
                const diffSign = parseFloat(d.diferencia) > 0 ? '+' : '';
                
                html += `
                <tr>
                    <td>${d.producto?.nombre ?? 'N/A'}</td>
                    <td class="text-right">${parseFloat(d.stock_sistema).toFixed(4)}</td>
                    <td class="text-right">${parseFloat(d.stock_fisico).toFixed(4)}</td>
                    <td class="text-right text-${tipoClass} fw-bold">${diffSign}${parseFloat(d.diferencia).toFixed(4)}</td>
                    <td><span class="badge bg-${tipoClass}">${d.tipo.toUpperCase()}</span></td>
                </tr>
                `;
            });

            html += '</tbody></table></div></div>';
            html += '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>';
            html += '</div></div></div>';

            document.getElementById('modalVerContainer').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalVerCuadre')).show();
        } catch (error) {
            console.error(error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error al cargar detalles' });
        }
    }

    confirmarAnular(id) {
        Swal.fire({
            title: '¿Anular Cuadre?',
            text: 'Esta acción marcará el cuadre como anulado. Los movimientos en el kardex serán marcados como anulados también.',
            input: 'textarea',
            inputLabel: 'Motivo (opcional)',
            inputPlaceholder: 'Puede describir el motivo o dejarlo vacío',
            inputAttributes: { maxlength: 500 },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch(`${this.baseUrl}/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ motivo: String(result.value).trim() })
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Éxito', text: data.message });
                        this.tabla.ajax.reload();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    }
                } catch (error) {
                    console.error(error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error al anular' });
                }
            }
        });
    }

    async showRectifyModal(id) {
        try {
            const response = await fetch(`${this.baseUrl}/${id}`);
            const data = await response.json();

            if (!data.success) {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                return;
            }

            const cuadre = data.cuadre;
            this.currentRectifyId = id;

            document.getElementById('rectificarNotas').value = cuadre.notas || '';
            document.getElementById('rectificarFecha').value = cuadre.fecha;
            document.getElementById('rectificarFecha').disabled = true;
            document.getElementById('filtroProductosRectificar').value = '';

            document.querySelectorAll('#tablaProductosRectificar tbody tr').forEach(fila => {
                fila.style.display = '';
            });

            cuadre.detalles.forEach(d => {
                const input = document.querySelector(`#tablaProductosRectificar .stock-fisico-rectificar[data-producto-id="${d.producto_id}"]`);
                if (input) {
                    input.value = parseFloat(d.stock_fisico).toFixed(4);
                }
            });

            new bootstrap.Modal(document.getElementById('modalRectificar')).show();
        } catch (error) {
            console.error(error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error al cargar datos para rectificar' });
        }
    }

    async handleRectificarSubmit(e) {
        e.preventDefault();

        const filasVisibles = document.querySelectorAll('#tablaProductosRectificar tbody tr:not([style*="display: none"])');
        const detalles = [];

        filasVisibles.forEach(fila => {
            const input = fila.querySelector('.stock-fisico-rectificar');
            if (input?.value) {
                detalles.push({
                    producto_id: parseInt(fila.dataset.productoId),
                    stock_fisico: parseFloat(input.value)
                });
            }
        });

        if (detalles.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin datos',
                text: 'Ingrese al menos un stock físico'
            });
            return;
        }

        const motivo = await solicitarMotivoAuditoria('Motivo de la rectificación', 'Puede registrar un motivo o continuar dejando el campo vacío.');
        if (motivo === null) return;

        const formData = {
            notas: document.getElementById('rectificarNotas').value,
            detalles: detalles,
            rectificacion_motivo: motivo
        };

        try {
            const response = await fetch(`${this.baseUrl}/${this.currentRectifyId}/rectificar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Éxito', text: data.message });
                bootstrap.Modal.getInstance(document.getElementById('modalRectificar')).hide();
                this.tabla.ajax.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        } catch (error) {
            console.error(error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error al rectificar' });
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.cuadreStockManager = new CuadreStockManager();
});
document.getElementById('mnuKardex')?.classList.add('menu-open');
</script>
@endpush
