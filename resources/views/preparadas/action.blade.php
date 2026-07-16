<!-- Modal -->
<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="max-width: 95vw;">
        <div class="modal-content fs-7" style=" font-size: 0.82rem;">
            <form id="formUpdate" method="post">
                @csrf
                <input type="hidden" id="method_field" name="_method">
                <input type="hidden" id="es_rectificacion" name="es_rectificacion" value="0">
                <input type="hidden" id="preparada_anulada_id" name="preparada_anulada_id" value="">
                <div class="modal-header">
                    <h4 class="modal-title fs-5" id="modalTitle">Nuevo registro</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6 mt-2">
                          <div class="border border-primary rounded p-3">
                            <div class="row">
                              <div class="col-lg-12">
                                <label for="formulacion_nombre" class="form-label">
                                  Formulación <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                  <input type="text" id="formulacion_nombre" class="form-control" placeholder="Buscar formulación" autocomplete="off">
                                  <input type="hidden" id="formulacion_id" name="formulacion_id">
                                </div>
                              </div>
                            </div>
                            <div class="row mt-2 d-none" id="info_formulacion">
                              <div class="col-md-4">
                                  <label for="producto_empaque" class="form-label">Empaque</label><br>
                                  <span id="producto_empaque_text"></span>
                              </div>
                              <div class="col-md-4">
                                  <label for="producto_empaque" class="form-label">Unidad</label><br>
                                  <span id="unidad_text"></span>
                              </div>
                              <div class="col-md-4">
                                  <label for="cliente_nombre" class="form-label">Cliente</label>
                                  <br>
                                  <span id="cliente_nombre"></span>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="col-lg-6 mt-2">
                          <div class="row border border-primary rounded p-3">
                            <div class="col-lg-3">
                                <x-datetime-picker id="fecha" label="Fecha Servicio" :required="true" />
                            </div>
                            <div class="col-lg-3">
                              <label for="costo_servicio" class="form-label">Servicio<span class="text-danger">*</span></label>
                              <input type="number" id="costo_servicio" value="0.00" name="costo_servicio" class="form-control form-control-sm" readonly>
                              <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-lg-3">
                              <label for="numero_interno" class="form-label">Interno<span class="text-danger">*</span></label>
                              <input type="text" id="numero_interno" name="numero_interno" class="form-control form-control-sm">
                              <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-lg-3">
                              <label for="proporcion" class="form-label">
                                Proporción <span class="text-danger">*</span>
                              </label>

                              <div class="input-group input-group-sm">
                                <input type="text" id="proporcion" name="proporcion"
                                      class="form-control">

                                <button type="button" id="btnProcesar"
                                        class="btn btn-primary">
                                  Procesar
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        @include('preparadas.partials.tabla-detalles')
                    </div>
                </div>
                <div class="modal-footer">
                    Usuario: <span class="me-auto fw-bold" id="usuario_nombre"></span>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnSubmit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
window.ENTER_SEQUENCE = {
    'formulacion_nombre': 'numero_interno',
    'numero_interno': 'proporcion',
    'proporcion': 'btnProcesar'
};
</script>
<script src="{{ asset('js/detallesFlecha.js') }}"></script>