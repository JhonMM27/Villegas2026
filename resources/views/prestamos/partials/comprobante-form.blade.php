<div class="border border-primary rounded p-3">
    <div class="row">
        <div class="col-md-4">
            <label for="movimiento_tipo" class="form-label">Movimiento Tipo<span class="text-danger">*</span></label>
            <input name="movimiento_tipo" id="movimiento_tipo" class="form-control form-control-sm" required readonly>
        </div>
        <div class="col-md-4">
            <x-datetime-picker id="fecha_prestamo" label="Fecha" />
        </div>
        <div class="col-md-4">
            <label for="prestamo_referencia_id" class="form-label">Préstamo referencia<span class="text-danger"></span></label>
            <input name="prestamo_referencia_id" id="prestamo_referencia_id" class="form-control form-control-sm" readonly>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-4">
            <label for="comprobante_tipo_codigo" class="form-label">Comprobante <span
                    class="text-danger">*</span></label>
            <input type="text" name="comprobante_tipo_codigo" id="comprobante_tipo_codigo" class="form-control form-control-sm" readonly required>
            <div class="invalid-feedback"></div>
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