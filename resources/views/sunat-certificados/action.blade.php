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
            <div class="col-lg-12">
              <div class="form-group mb-3">
                <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control" required>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-12">
              <div class="form-group mb-3">
                <label for="certificado_path" class="form-label">Ruta del certificado</label>
                <input type="text" id="certificado_path" name="certificado_path" class="form-control">
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="form-group mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="text" id="password" name="password" class="form-control">
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="form-group mb-3">
                <x-datetime-picker id="expires_at" label="Expira" />
              </div>
            </div>
            <div class="col-lg-12">
              <div class="form-group mb-3">
                <label for="activo" class="form-label">Activo</label>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
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
