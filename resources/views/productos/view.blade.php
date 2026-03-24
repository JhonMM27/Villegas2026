<!-- Modal de visualización de producto -->
<div class="modal fade" id="modalViewProducto" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
     aria-labelledby="modalViewProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content fs-7">
            <div class="modal-header">
                <h4 class="modal-title fs-5" id="modalTitle">Detalle de Producto: {{ $producto->nombre }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Datos principales del producto -->
                    <div class="col-lg-6">
                        <div class="border border-primary rounded p-3">
                            <h6 class="text-primary mb-3">Información General</h6>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">Código:</label>
                                    <p class="fw-bold">{{ $producto->codigo ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nombre:</label>
                                    <p class="fw-bold">{{ $producto->nombre }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Unidad:</label>
                                    <p class="fw-bold">{{ $producto->unidad->descripcion }} ({{ $producto->unidad->codigo }})</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Empaque:</label>
                                    <p class="fw-bold">{{ $producto->empaque ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Línea:</label>
                                    <p class="fw-bold">{{ $producto->linea->nombre }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Afectación:</label>
                                    <p class="fw-bold">{{ $producto->afectacionTipo->nombre }} ({{ $producto->afectacionTipo->codigo }})</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Descripción:</label>
                                    <p class="fw-bold">{{ $producto->descripcion ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Stock mínimo:</label>
                                    <p class="fw-bold">{{ $producto->stock_minimo ?? '0' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos de stock y costos -->
                    <div class="col-lg-6">
                        <div class="border border-primary rounded p-3">
                            <h6 class="text-primary mb-3">Stock y Precios</h6>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">Estado:</label>
                                    <p class="fw-bold">
                                        <span class="badge {{ $producto->activo ? 'bg-success' : 'bg-danger' }}">
                                            {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Stock Almacén:</label>
                                    <p class="fw-bold">{{ number_format($producto->stock_almacen, 4) }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Costo Unitario:</label>
                                    <p class="fw-bold">S/ {{ number_format($producto->costo_unitario, 4) }}</p>
                                </div>                              
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de fracciones -->
                    <div class="col-lg-12 mt-3">
                        <div class="border border-primary rounded p-3">
                            <h6 class="text-primary mb-3">Fracciones del Producto</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th>Código Detalle</th>
                                            <th>Unidad</th>
                                            <th>Empaque</th>                                            
                                            <th>Precio Lista</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($producto->fracciones as $index => $fraccion)
                                        <tr>
                                            <td class="text-center">{{ $fraccion->codigo_detalle }}</td>
                                            <td class="text-center">{{ $fraccion->unidad->codigo }} ({{ $fraccion->unidad->descripcion }})</td>
                                            <td class="text-end">{{ $fraccion->empaque }}</td>                                            
                                            <td class="text-end">S/ {{ number_format($fraccion->precio_lista, 2) }}</td>
                                            <td class="text-center">
                                                <span class="badge {{ $fraccion->activo ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $fraccion->activo ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No hay fracciones registradas</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span class="me-auto text-muted">
                    <small>Creado: {{ $producto->created_at ? $producto->created_at->format('d/m/Y H:i') : 'N/A' }}</small>
                </span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>