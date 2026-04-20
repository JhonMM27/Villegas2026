<!-- Modal -->
<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
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
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-6">
                                    <label for="fecha_gasto" class="form-label">Fecha Gasto <span
                                            class="text-danger"></span></label>
                                    <input type="datetime-local" id="fecha_gasto" name="fecha_gasto"
                                        class="form-control form-control-sm">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group mb-3">
                                        <label for="numero_interno" class="form-label">Número interno <span
                                                class="text-danger"></span></label>
                                        <input type="text" id="numero_interno" name="numero_interno"
                                            class="form-control form-control-sm">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group mb-3">
                                        <label for="descripcion" class="form-label">Descripción <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="descripcion" name="descripcion"
                                            class="form-control form-control-sm" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group mb-3">
                                        <label for="tipo" class="form-label">Tipo</label>
                                        <select id="tipo" name="tipo" class="form-select form-select-sm">
                                            <option value="Combustible">Combustible</option>
                                            <option value="Luz">Luz</option>
                                            <option value="Reparaciones">Reparaciones</option>
                                            <option value="Fletes">Fletes</option>
                                            <option value="Administrativo">Administrativo</option>
                                            <option value="Otros" selected>Otros</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group mb-3">
                                        <label for="responsable" class="form-label">Responsable <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="responsable" name="responsable"
                                            class="form-control form-control-sm" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <divv class="col-lg-6">
                            @include('gastos.partials.cobranza')
                        </divv>
                    </div>
                </div>
                <div class="modal-footer">
                    Usuario: <span class="me-auto fw-bold" id="usuario_nombre"></span>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnSubmit" class="btn btn-primary">Enviar</button>
                </div>
            </form>
        </div>
    </div>
</div>
