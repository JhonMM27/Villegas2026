<div class="border border-primary rounded p-3">
    <div class="row">
        <div class="col-md-4">
            <label for="comprobante_tipo_codigo" class="form-label">Comprobante <span
                    class="text-danger">*</span></label>
            <select name="comprobante_tipo_codigo" id="comprobante_tipo_codigo" class="form-select form-select-sm" required>
                
            </select>
            <input type="hidden" name="cotizacion_ref_id" id="cotizacion_ref_id">
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

        <div class="col-md-4">
            <label for="docpagoi" class="form-label">Doc. Pago Interno <span class="text-danger"></span></label>
            <input type="text" id="docpagoi" name="docpagoi" class="form-control form-control-sm">
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-3">
            <label for="cobranza_tipo_id" class="form-label">Cobranza Tipo <span class="text-danger">*</span></label>
            <select name="cobranza_tipo_id" id="cobranza_tipo_id" class="form-select form-select-sm">
            </select>
        </div>
        <div class="col-md-3">
            <label for="pago_forma_codigo" class="form-label">Forma de pago <span class="text-danger">*</span></label>
            <select name="pago_forma_codigo" id="pago_forma_codigo" class="form-select form-select-sm" required>
            </select>
        </div>
        <div class="col-md-3">
            <label for="fecha_venta" class="form-label">Fecha Venta <span class="text-danger"></span></label>
            <input type="datetime-local" id="fecha_venta" name="fecha_venta" class="form-control form-control-sm">
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-3">
            <label for="fecha_vencimiento" class="form-label">Fecha Vencimiento <span class="text-danger"></span></label>
            <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" class="form-control form-control-sm">
            <div class="invalid-feedback"></div>
        </div>

        <!--
        <div class="col-md-4">
            <label for="acuenta" class="form-label">A cuenta <span class="text-danger">*</span></label>
            <input type="text" id="acuenta" name="acuenta" value="0" class="form-control form-control-sm" required>
            <div class="invalid-feedback"></div>
        </div>
        
        <div class="col-md-3">
            <label for="abonos" class="form-label">Abonos <span class="text-danger">*</span></label>
            <input type="text" id="abonos" name="abonos" value="0"  class="form-control form-control-sm" required>
            <div class="invalid-feedback"></div>
        </div>
        
        <div class="col-md-4">
            <label for="saldo" class="form-label">Saldo <span class="text-danger">*</span></label>
            <input type="text" id="saldo" name="saldo" value="0"  class="form-control form-control-sm" required readonly>
            <div class="invalid-feedback"></div>
        </div>
        -->
        
    </div>
</div>