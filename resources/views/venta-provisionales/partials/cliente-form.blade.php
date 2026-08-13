<div class="border border-primary rounded p-3">
    <label class="form-label mb-1 fw-bold">Cliente</label>
    <div class="input-group input-group-sm">
        <input type="text" id="cliente_nombre" name="cliente_nombre" class="form-control form-control-sm"
            autocomplete="off" placeholder="Buscar cliente..." data-error-field="cliente_id">
        <button type="button" id="btnCargarPendientes" class="btn btn-outline-primary" title="Cargar ventas pendientes con saldo de este cliente">
            <i class="bi bi-arrow-clockwise"></i> Cargar pendientes
        </button>
        <button type="button" id="btnDistribuir" class="btn btn-primary" title="Asignación automática secuencial">
            <i class="bi bi-lightning-fill"></i> Distribuir
        </button>
    </div>

    <input type="hidden" id="cliente_id" name="cliente_id">
</div>

<div class="table-responsive mt-2">
    <table class="table table-sm table-bordered table-hover align-middle mb-0 table-app" id="tablaVentasSaldo">
        <thead class="table-light">
            <tr>
                <th style="width:70px">ID</th>
                <th>Documento</th>
                <th>Fecha</th>
                <th class="text-end">Total</th>
                <th class="text-end">A cuenta</th>
                <th class="text-end">Abonos</th>
                <th class="text-end">Saldo Disp.</th>
                <th class="text-end" style="width:130px">Monto a Aplicar</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="8" class="text-muted text-center py-3">Seleccione un cliente…</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Resumen y Estados Visuales de Distribución -->
<div class="card mt-2 bg-light border">
    <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-2 text-sm">
        <div>
            <span class="text-muted">Total Recibido:</span>
            <strong class="fs-6 text-dark ms-1">S/ <span id="summary_recibido">0.00</span></strong>
        </div>
        <div>
            <span class="text-muted">Total Distribuido:</span>
            <strong class="fs-6 text-dark ms-1">S/ <span id="summary_distribuido">0.00</span></strong>
        </div>
        <div>
            <span id="summary_estado" class="badge bg-secondary fs-6 py-2 px-3">
                Sin documentos seleccionados
            </span>
        </div>
    </div>
</div>