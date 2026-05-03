<div class="modal fade" id="modalCuadre" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formCuadre" method="post">
                @csrf
                <div class="modal-header py-2">
                    <h5 class="modal-title">Nuevo Cuadre de Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-3 border-bottom bg-light">
                        <div class="row g-3 mb-3">
                            <div class="col-lg-3 col-md-4">
                                <label for="fecha" class="form-label form-label-sm small fw-bold">Fecha <span class="text-danger">*</span></label>
                                <input type="date" id="fecha" name="fecha" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-lg-5 col-md-4">
                                <label for="notas" class="form-label form-label-sm small fw-bold">Notas</label>
                                <input type="text" id="notas" name="notas" class="form-control form-control-sm" placeholder="Observaciones...">
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <label for="filtroProductos" class="form-label form-label-sm small fw-bold">Buscar producto:</label>
                                <input type="text" id="filtroProductos" class="form-control form-control-sm" placeholder="Nombre, código o ID..." autocomplete="off">
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" id="btnUsarKardex" class="btn btn-info btn-sm">
                                <i class="bi bi-arrow-down"></i> Stock Kardex
                            </button>
                            <button type="button" id="btnLimpiar" class="btn btn-secondary btn-sm">
                                <i class="bi bi-eraser"></i> Limpiar
                            </button>
                            <button type="button" id="btnPreview" class="btn btn-warning btn-sm ms-auto">
                                <i class="bi bi-calculator"></i> Preview
                            </button>
                        </div>
                        <div id="previewContainer" class="mt-3"></div>
                    </div>

                    <div style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-hover mb-0" id="tablaProductosCuadre">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th style="width: 55%;">Producto</th>
                                    <th class="text-end" style="width: 20%;">Stock Sist.</th>
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
                                            class="form-control form-control-sm stock-fisico-input" 
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
                    <div id="previewSummary" class="text-muted small"></div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" id="btnSubmit" class="btn btn-success btn-sm">
                            <i class="bi bi-check-circle"></i> Confirmar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
