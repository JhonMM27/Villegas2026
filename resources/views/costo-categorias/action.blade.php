<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formUpdate" method="post">
                @csrf
                <input type="hidden" id="method_field" name="_method">
                <div class="modal-header">
                    <h4 class="modal-title fs-5" id="modalTitle">Nueva Categoría de Costo</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="border border-primary rounded p-3 mb-3">
                        <h6 class="text-primary mb-3">Datos de la Categoría</h6>
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="nombre" name="nombre" class="form-control form-control-sm" required maxlength="50" placeholder="Ej: Costos Operativos">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="border border-secondary rounded p-3">
                        <h6 class="text-secondary mb-3">Estado</h6>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                            <label class="form-check-label" for="activo">Activo / Inactivo</label>
                        </div>
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
