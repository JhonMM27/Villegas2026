<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formUpdate" method="post">
                @csrf
                <input type="hidden" id="method_field" name="_method">
                <input type="hidden" id="empleado_id" name="empleado_id">
                <div class="modal-header">
                    <h4 class="modal-title fs-5" id="modalTitle">Nueva Inasistencia</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="empleado_nombre" class="form-label">Empleado <span class="text-danger">*</span></label>
                        <input type="text" id="empleado_nombre" class="form-control form-control-sm"
                            placeholder="Buscar por nombre o DNI" autocomplete="off">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
                                <input type="date" id="fecha" name="fecha"
                                    class="form-control form-control-sm" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="medio_dia" class="form-label">Tipo</label>
                                <div class="form-check mt-2">
                                    <input type="checkbox" id="medio_dia" name="medio_dia" class="form-check-input" value="1">
                                    <label for="medio_dia" class="form-check-label">Medio Día</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="observacion" class="form-label">Observación</label>
                        <input type="text" id="observacion" name="observacion"
                            class="form-control form-control-sm" placeholder="Motivo de la inasistencia...">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnSubmit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
