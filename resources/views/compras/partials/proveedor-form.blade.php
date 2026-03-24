<div class="border border-primary rounded p-3">
    <div class="row">
        <div class="col-md-12">
            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link active" id="nav-buscar-tab" data-bs-toggle="tab"
                        data-bs-target="#nav-buscar" type="button" role="tab" aria-controls="nav-buscar"
                        aria-selected="true">Buscar Proveedor</button>
                    <button class="nav-link" id="nav-registra-tab" data-bs-toggle="tab" data-bs-target="#nav-registrar"
                        type="button" role="tab" aria-controls="nav-registrar" aria-selected="false">Registrar
                        Proveedor</button>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav-buscar" role="tabpanel" aria-labelledby="nav-buscar-tab">
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <label for="proveedor_razon_social" class="form-label">Proveedor</label>
                            <input type="text" id="proveedor_razon_social" name="proveedor_razon_social" class="form-control form-control-sm"
                                autocomplete="off" placeholder="Buscar proveedor..." data-error-field="proveedor_id">
                            
                            <input type="hidden" id="proveedor_id" name="proveedor_id">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="nav-registrar" role="tabpanel" aria-labelledby="nav-registra-tab">
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label for="documento_tipo_codigo" class="form-label">Documento Tipo</label>
                            <select name="documento_tipo_codigo" id="documento_tipo_codigo"
                                class="form-select form-select-sm">

                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="documento_numero" class="form-label">Número documento</label>
                            <input type="text" class="form-control form-control-sm" id="documento_numero"
                                name="documento_numero">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="razon_social" class="form-label">Razón Social</label>
                            <input type="text" class="form-control form-control-sm" id="razon_social"
                                name="razon_social">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 d-flex align-items-end justify-content-end">
                            <button type="button" class="btn btn-primary btn-sm" id="btnRegistrarProveedor">Guardar Proveedor</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>