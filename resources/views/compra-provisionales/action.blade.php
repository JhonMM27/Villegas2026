<!-- Modal -->
<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-xl" style="transform: scale(0.93); transform-origin: top center;">
    <div class="modal-content">
      <form id="formUpdate" method="post">
        @csrf
        <input type="hidden" id="method_field" name="_method">

        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Nuevo registro</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <!-- TAB CONTENT -->
          <div class="tab-content mt-3">
            <div class="row g-2">
                <div class="col-lg-7">
                  @include('compra-provisionales.partials.comprobante-form')
                </div>
                <div class="col-lg-5">
                  @include('compra-provisionales.partials.cobranza')
                </div>
                <div class="col-lg-12 mt-3">
                  @include('compra-provisionales.partials.proveedor-form')
                </div>
              </div>

              <hr class="my-3">
          </div>

        </div>

        <div class="modal-footer">
          Usuario: <span class="me-auto fw-bold" id="usuario_nombre"></span>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" id="btnSubmit" class="btn btn-primary btn-sm">Guardar</button>
        </div>

      </form>
    </div>
  </div>
</div>