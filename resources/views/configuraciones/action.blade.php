<!-- Modal Editar Configuracion -->
<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formUpdate" method="post" novalidate>
                @csrf
                <input type="hidden" id="method_field" name="_method" value="PUT">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Editar Configuración</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group mb-3">
                                <label for="config_clave" class="form-label">Clave</label>
                                <input type="hidden" id="config_id" name="id">
                                <input type="text" id="config_clave" class="form-control" readonly disabled>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group mb-3">
                                <label for="config_valor" class="form-label">Valor <span class="text-danger">*</span></label>
                                <input type="text" id="config_valor" name="valor" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group mb-3">
                                <label for="config_descripcion" class="form-label">Descripción</label>
                                <input type="text" id="config_descripcion" class="form-control" readonly disabled>
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
