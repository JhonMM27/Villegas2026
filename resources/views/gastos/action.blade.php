<!-- Modal -->
<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="transform: scale(0.93); transform-origin: top center;">
        <div class="modal-content">
            <form id="formUpdate" method="post">
                @csrf
                <input type="hidden" id="method_field" name="_method">
                <div class="modal-header">
                    <h4 class="modal-title fs-5" id="modalTitle">Nuevo registro</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="border border-primary rounded-3 p-3">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label for="fecha_gasto" class="form-label text-muted small">Fecha Gasto</label>
                                        <input type="datetime-local" id="fecha_gasto" name="fecha_gasto" class="form-control form-control-sm">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-6">
                                        <label for="numero_interno" class="form-label text-muted small">Número interno</label>
                                        <input type="text" id="numero_interno" name="numero_interno" class="form-control form-control-sm">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-6">
                                        <label for="categoria_gasto_id" class="form-label text-muted small">Categoría <span class="text-danger">*</span></label>
                                        <select id="categoria_gasto_id" name="categoria_gasto_id" class="form-select form-select-sm" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-6">
                                        <label for="gasto_tipo_id" class="form-label text-muted small">Tipo <span class="text-danger">*</span></label>
                                        <select id="gasto_tipo_id" name="gasto_tipo_id" class="form-select form-select-sm" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-6">
                                        <label for="responsable" class="form-label text-muted small">Responsable <span class="text-danger">*</span></label>
                                        <input type="text" id="responsable" name="responsable" class="form-control form-control-sm" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-6">
                                        <label for="responsable_dni" class="form-label text-muted small">DNI</label>
                                        <input type="text" id="responsable_dni" name="responsable_dni" class="form-control form-control-sm" maxlength="8" placeholder="12345678">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-12">
                                        <label for="descripcion" class="form-label text-muted small">Descripción</label>
                                        <input type="text" id="descripcion" name="descripcion" class="form-control form-control-sm">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            @include('gastos.partials.cobranza')
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    Usuario: <span class="me-auto fw-bold" id="usuario_nombre"></span>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnSubmit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>