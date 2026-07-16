<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form id="formUpdate" method="post">
                @csrf
                <input type="hidden" id="method_field" name="_method">
                <div class="modal-header">
                    <h4 class="modal-title fs-5" id="modalTitle">Ingreso a Caja</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="fecha" class="form-label">
                                Fecha <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="fecha" name="fecha"
                                   class="form-control form-control-sm date-picker" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="monto" class="form-label">
                                Monto (S/) <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01" id="monto" name="monto"
                                   class="form-control form-control-sm" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-12">
                            <label for="caja_destino" class="form-label">
                                Caja destino <span class="text-danger">*</span>
                            </label>
                            <select id="caja_destino" name="caja_destino" class="form-select form-select-sm" required>
                                @foreach(\App\Models\CajaIngreso::cajaDestinoOptions() as $key => $texto)
                                    <option value="{{ $key }}">{{ $key }} · {{ $texto }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-12">
                            <label for="comentario" class="form-label">Comentario</label>
                            <textarea id="comentario" name="comentario"
                                      class="form-control form-control-sm" rows="3"
                                      placeholder="Detalle del ingreso (opcional)" maxlength="500"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-12">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                El usuario que registra y la fecha de creación se asignarán automáticamente.
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                    <button type="submit" id="btnSubmit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-circle me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
