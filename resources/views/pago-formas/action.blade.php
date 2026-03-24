<!-- Modal -->
<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formUpdate" method="post" novalidate>
        @csrf
        <input type="hidden" id="method_field" name="_method">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Nuevo registro</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-lg-4">
              <div class="form-group mb-3">
                <label for="codigo" class="form-label">Código <span class="text-danger">*</span></label>
                <input type="text" id="codigo" name="codigo" class="form-control" required>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="form-group mb-3">
                <label for="descripcion" class="form-label">Descripción <span class="text-danger">*</span></label>
                <input type="text" id="descripcion" name="descripcion" class="form-control" required>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group mb-3">
                <label for="dias" class="form-label">Días <span class="text-danger">*</span></label>
                <input type="text" id="dias" name="dias" class="form-control" required>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group mb-3">
                <label for="activo" class="form-label">Activo</label>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" value="1" id="activo" name="activo" checked>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" id="btnSubmit" class="btn btn-primary btn-sm">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
