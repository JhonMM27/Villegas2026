<!-- Modal -->
<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formUpdate" method="post">
        @csrf
        <input type="hidden" id="method_field" name="_method">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Nuevo registro</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-lg-6">
              <div class="form-group mb-3">
                <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" class="form-control" required>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="form-group mb-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="text" id="email" name="email" class="form-control" required>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="form-group mb-3">
                <label for="password" class="form-label">Password <span class="text-danger">
                  (Si se está actualizando y no se desea modificar el password dejar vacío)</span></label>
                <input type="text" id="password" name="password" class="form-control">
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="form-group mb-3">
                <label for="email" class="form-label">Activo <span class="text-danger">*</span></label>
                <select name="activo" id="activo" class="form-select" required>
                  <option value="1">Activo</option>
                  <option value="0">Inactivo</option>
                </select>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-12">
              <div class="form-group mb-3">
                <label class="form-label">Roles</label>
                <div id="checkbox-roles" class="row">

                </div>
                <div class="invalid-feedback d-block" id="roles-error"></div>
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