<!-- Modal -->
<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="transform: scale(0.93); transform-origin: top center;">
        <div class="modal-content fs-7">
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
                            @include('ventas.partials.cliente-form')
                        </div>
                        <div class="col-lg-5">
                          @include('ventas.partials.cobranza')
                        </div>
                        <div class="col-lg-7 mt-3">
                          @include('ventas.partials.comprobante-form')
                        </div>
                        <div class="col-lg-5 mt-3">
                          <div class="border border-primary rounded p-3">
                            <div class="row g-2">
                                <!-- Nombre -->
                                <div class="col-12 col-md-12">
                                    <label for="producto_nombre" class="form-label mb-1">Producto</label><span class="text-danger">*</span>
                                    <input type="text"
                                          id="producto_nombre"
                                          class="form-control form-control-sm"
                                          placeholder="Buscar producto"
                                          autocomplete="off">
                                    <input type="hidden" id="producto_id" name="producto_id">
                                </div>
                            </div>
                          </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-lg-12">
                          @include('ventas.partials.tabla-detalles')
                        </div>
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