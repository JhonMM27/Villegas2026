<div class="border border-primary rounded p-3">
    <label class="form-label mb-0">Proveedor</label>
    <div class="input-group input-group-sm">
        <input type="text" id="proveedor_nombre" name="proveedor_nombre" class="form-control form-control-sm"
            autocomplete="off" placeholder="Buscar proveedor..." data-error-field="proveedor_id">
        <button type="button" id="btnDistribuir" class="btn btn-primary">
            Distribuir
        </button>
    </div>

    <input type="hidden" id="proveedor_id" name="proveedor_id">
</div>
<div class="table-responsive mt-2">
    <table class="table table-sm table-bordered table-hover align-middle mb-0 table-app" id="tablaComprasSaldo">
        <thead class="table-light">
            <tr>
                <th style="width:70px">ID</th>
                <th>Documento</th>
                <th>Fecha</th>
                <th class="text-end">Total</th>
                <th class="text-end">A cuenta</th>
                <th class="text-end">Abonos</th>
                <th class="text-end">Saldo</th>
                <th class="text-end">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="8" class="text-muted text-center py-3">Seleccione un proveedor…</td>
            </tr>
        </tbody>
    </table>
</div>