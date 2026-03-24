<div class="border border-primary rounded p-3">
    <div class="row">
        <div class="col-md-4">
            <label for="pago_forma_codigo" class="form-label">Forma de pago <span class="text-danger">*</span></label>
            <select name="pago_forma_codigo" id="pago_forma_codigo" class="form-select form-select-sm" required>
            </select>
        </div>
        <div class="col-md-4">
            <label for="fecha_cotizacion" class="form-label">Fecha Cotización <span class="text-danger"></span></label>
            <input type="datetime-local" id="fecha_cotizacion" name="fecha_cotizacion" class="form-control form-control-sm">
            <div class="invalid-feedback"></div>
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