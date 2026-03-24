<div class="border border-primary rounded p-3">
    <div class="row">
        <div class="col-md-8">
            <label for="producto_nombre_nucleo" class="form-label">Núcleo <span class="text-danger">*</span></label>
            <input type="hidden" id="producto_id_nucleo" name="producto_id_nucleo" class="form-control form-control-sm">
            <input type="text" id="producto_nombre_nucleo" name="producto_nombre_nucleo" class="form-control form-control-sm" autocomplete="off">
            <div class="invalid-feedback"></div>
        </div>        
    </div>
    <div class="row mt-2">
        <div class="col-md-4">
            <label for="producto_empaque" class="form-label">Empaque <span class="text-danger"></span></label><br>
            <span id="producto_empaque_text"></span>
            <input type="hidden" id="producto_empaque" name="producto_empaque" class="form-control form-control-sm">            
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-4">
            <label for="linea" class="form-label">Línea <span class="text-danger"></span></label>
            <br>
            <span id="linea"></span>
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
</div>