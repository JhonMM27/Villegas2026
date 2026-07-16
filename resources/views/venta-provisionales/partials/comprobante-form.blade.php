<div class="border border-primary rounded p-3">
    <div class="row">
        <div class="col-md-6">
            <label for="numero_interno" class="form-label">Número Interno <span class="text-danger"></span></label>
            <input type="text" id="numero_interno" name="numero_interno" class="form-control form-control-sm" required>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <x-datetime-picker id="fecha_provisional" label="Fecha" />
        </div>        
    </div>
</div>