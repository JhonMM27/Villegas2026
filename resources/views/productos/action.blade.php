<!-- Modal -->
<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formUpdate" method="post" enctype="multipart/form-data">
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
                <label for="unidad_codigo" class="form-label">Unidad <span class="text-danger">*</span></label>
                <select name="unidad_codigo" id="unidad_codigo" class="form-select form-select-sm" required></select>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="form-group mb-3">
                <label for="afectacion_tipo_codigo" class="form-label">Afectación tipo <span class="text-danger">*</span></label>
                <select name="afectacion_tipo_codigo" id="afectacion_tipo_codigo" class="form-select form-select-sm" required></select>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="form-group mb-3">
                <label for="linea_id" class="form-label">Línea <span class="text-danger">*</span></label>
                <select name="linea_id" id="linea_id" class="form-select form-select-sm" required></select>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="form-group mb-3">
                <label for="codigo" class="form-label">Código <span class="text-danger"></span></label>
                <input type="text" id="codigo" name="codigo" class="form-control form-control-sm" >
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="form-group mb-3">
                <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control form-control-sm"  required>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="form-group mb-3">
                <label for="stock_minimo" class="form-label">Stock mínimo <span class="text-danger">*</span></label>
                <input type="text" id="stock_minimo" name="stock_minimo" class="form-control form-control-sm"  required>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="col-lg-12">
              <div class="form-group mb-3">
                <label for="descripcion" class="form-label">Descripción </label>
                <input type="text" id="descripcion" name="descripcion" class="form-control form-control-sm" >
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <!-- Costo Unitario -->
            <div class="col-lg-4">
              <div class="form-group mb-3">
                <label for="costo_unitario" class="form-label">Costo unitario <span class="text-danger">*</span></label>
                <input type="text" id="costo_unitario" name="costo_unitario" class="form-control form-control-sm"  required>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <!-- Stock -->
            <div class="col-lg-4">
              <div class="form-group mb-3">
                <label for="stock_almacen" class="form-label">Stock almacén <span class="text-danger">*</span></label>
                <input type="text" id="stock_almacen" name="stock_almacen" class="form-control form-control-sm"  required>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            
            <div class="col-lg-4">
              <div class="form-group mb-3">
                <label for="empaque" class="form-label">Empaque <span class="text-danger">*</span></label>
                <input type="text" id="empaque" name="empaque" class="form-control form-control-sm"  required>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            
            <div class="col-lg-4">
              <div class="form-group mb-3">
                <label for="imagen" class="form-label">Imagen</label>
                <input type="file" id="imagen" name="imagen" class="form-control form-control-sm" >
                <div class="invalid-feedback"></div>
                <img src="" width="200px" alt="Imagen del producto" id="imagen_producto">
              </div>
            </div>
            <div class="col-lg-4">
              <div class="form-group mb-3">
                <label for="activo" class="form-label">Activo</label>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-12 mt-3">
            <div class="border border-primary rounded p-3">
              <div class="input-group input-group-sm">
                <input type="text" id="unidad_descripcion" class="form-control" placeholder="Buscar Unidad" autocomplete="off">
                <input type="hidden" id="unidad_codigo_d" name="unidad_codigo_d">
                <button type="button" id="btnAgregarUnidad" class="btn btn-success">Agregar</button>
              </div>
            </div>
          </div>
          <div class="row mt-4">
              <div class="col-lg-12">
                @include('productos.partials.tabla-detalles')
              </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" id="btnSubmit" class="btn btn-primary btn-sm">
            <span id="btnText">Guardar</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>