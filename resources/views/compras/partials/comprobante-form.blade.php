<div class="border border-primary rounded p-3">
    <div class="row">
        <div class="col-md-4">
            <label for="pago_forma_codigo" class="form-label">Forma de pago <span class="text-danger">*</span></label>
            <select name="pago_forma_codigo" id="pago_forma_codigo" class="form-select form-select-sm" required>
            </select>
        </div>
        <div class="col-md-4">
            <label for="fecha_compra" class="form-label">Fecha Compra <span class="text-danger"></span></label>
            <input type="datetime-local" id="fecha_compra" name="fecha_compra" class="form-control form-control-sm">
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-4">
            <label for="fecha_vencimiento" class="form-label">Fecha Vencimiento <span class="text-danger"></span></label>
            <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" class="form-control form-control-sm">
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-4">
            <label for="cobranza_tipo_id" class="form-label">Cobranza Tipo <span class="text-danger">*</span></label>
            <select name="cobranza_tipo_id" id="cobranza_tipo_id" class="form-select form-select-sm">
            </select>
        </div>
        <div class="col-md-4">
            <label for="comprobante_tipo_codigo" class="form-label">Comprobante <span
                    class="text-danger">*</span></label>
            <select name="comprobante_tipo_codigo" id="comprobante_tipo_codigo" class="form-select form-select-sm" required>
                
            </select>
        </div>

        <div class="col-md-2">
            <label for="serie" class="form-label">Serie <span class="text-danger">*</span></label>
            <input type="text" id="serie" name="serie" class="form-control form-control-sm" required>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-2">
            <label for="correlativo" class="form-label">Correlativo <span class="text-danger">*</span></label>
            <input type="text" id="correlativo" name="correlativo" class="form-control form-control-sm" required>
            <div class="invalid-feedback"></div>
        </div>        
    </div>
</div>