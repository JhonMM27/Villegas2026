<style>
    #rectificacionHistorialModal .modal-content {
        border: 1px solid var(--bs-border-color);
        border-radius: .5rem;
        box-shadow: 0 .75rem 2rem rgba(0, 0, 0, .18);
        overflow: hidden;
    }
    #rectificacionHistorialModal .rect-history-header {
        padding: .85rem 1rem;
        background: var(--bs-tertiary-bg);
        border-bottom: 1px solid var(--bs-border-color);
    }
    #rectificacionHistorialModal .rect-history-icon {
        display: grid;
        place-items: center;
        width: 2.4rem;
        height: 2.4rem;
        flex: 0 0 2.4rem;
        color: var(--bs-primary);
        border: 1px solid rgba(var(--bs-primary-rgb), .2);
        border-radius: .375rem;
        background: rgba(var(--bs-primary-rgb), .1);
        font-size: 1.05rem;
    }
    #rectificacionHistorialModal .rect-history-subtitle {
        color: var(--bs-secondary-color);
        font-size: .82rem;
    }
    #rectificacionHistorialModal .modal-body {
        padding: 1rem;
        background: var(--bs-body-bg);
    }
    #rectificacionHistorialModal .rect-summary {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .7rem .85rem;
        margin-bottom: .75rem;
        border: 1px solid var(--bs-border-color);
        border-left: 4px solid var(--bs-primary);
        border-radius: .375rem;
        background: var(--bs-tertiary-bg);
    }
    #rectificacionHistorialModal .rect-summary-icon {
        display: grid;
        place-items: center;
        width: 2rem;
        height: 2rem;
        color: var(--bs-primary);
        font-size: 1rem;
    }
    #rectificacionHistorialModal .rect-history-card {
        border: 1px solid var(--bs-border-color);
        border-radius: .375rem;
        box-shadow: none;
        overflow: hidden;
    }
    #rectificacionHistorialModal .rect-card-header {
        padding: .65rem .85rem;
        background: var(--bs-tertiary-bg);
        border-bottom: 1px solid var(--bs-border-color);
    }
    #rectificacionHistorialModal .rect-number {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        color: var(--bs-primary);
        font-weight: 700;
    }
    #rectificacionHistorialModal .rect-meta-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);
        gap: .5rem;
        margin-bottom: .75rem;
    }
    #rectificacionHistorialModal .rect-meta-item {
        padding: .6rem .7rem;
        border: 1px solid var(--bs-border-color);
        border-radius: .375rem;
        background: var(--bs-tertiary-bg);
    }
    #rectificacionHistorialModal .rect-meta-label {
        display: block;
        margin-bottom: .15rem;
        color: var(--bs-secondary-color);
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    #rectificacionHistorialModal .rect-section-title {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin: .85rem 0 .4rem;
        padding-bottom: .35rem;
        color: var(--bs-body-color);
        border-bottom: 1px solid var(--bs-border-color);
        font-size: .9rem;
        font-weight: 700;
    }
    #rectificacionHistorialModal .rect-section-count {
        padding: .1rem .42rem;
        color: var(--bs-primary);
        border-radius: 2rem;
        background: rgba(var(--bs-primary-rgb), .1);
        font-size: .7rem;
    }
    #rectificacionHistorialModal .rect-diff-table {
        overflow: hidden;
        border: 1px solid var(--bs-border-color);
        border-radius: .375rem;
    }
    #rectificacionHistorialModal .rect-diff-table table { margin: 0; }
    #rectificacionHistorialModal .rect-diff-table thead th {
        padding: .5rem .65rem;
        color: var(--bs-body-color);
        background: var(--bs-tertiary-bg);
        border-color: var(--bs-border-color);
        font-size: .75rem;
        text-transform: uppercase;
    }
    #rectificacionHistorialModal .rect-diff-table td {
        padding: .5rem .65rem;
        border-color: var(--bs-border-color);
        vertical-align: middle;
    }
    #rectificacionHistorialModal .rect-value-old { color: var(--bs-danger-text-emphasis); background: var(--bs-danger-bg-subtle); }
    #rectificacionHistorialModal .rect-value-new { color: var(--bs-success-text-emphasis); background: var(--bs-success-bg-subtle); font-weight: 600; }
    #rectificacionHistorialModal .rect-change-badge {
        display: inline-flex;
        align-items: center;
        margin-left: .4rem;
        padding: .12rem .4rem;
        border-radius: 2rem;
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    #rectificacionHistorialModal .rect-change-modificado { color: #854d0e; background: #fef3c7; }
    #rectificacionHistorialModal .rect-change-agregado { color: #166534; background: #dcfce7; }
    #rectificacionHistorialModal .rect-change-eliminado { color: #991b1b; background: #fee2e2; }
    #rectificacionHistorialModal.audit-event-cancel .rect-history-icon,
    #rectificacionHistorialModal.audit-event-cancel .rect-number,
    #rectificacionHistorialModal.audit-event-cancel .rect-summary-icon { color: var(--bs-danger); }
    #rectificacionHistorialModal.audit-event-cancel .rect-history-icon { border-color: rgba(var(--bs-danger-rgb), .2); background: rgba(var(--bs-danger-rgb), .1); }
    #rectificacionHistorialModal.audit-event-cancel .rect-summary { border-left-color: var(--bs-danger); }
    @media (max-width: 767.98px) {
        #rectificacionHistorialModal .modal-body { padding: .8rem; }
        #rectificacionHistorialModal .rect-meta-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="modal fade" id="rectificacionHistorialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header rect-history-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="rect-history-icon"><i class="bi bi-shield-check" id="auditoriaDetalleIcono"></i></div>
                    <div>
                        <h5 class="modal-title mb-1" id="auditoriaDetalleTitulo">Detalle de auditoría</h5>
                        <div class="rect-history-subtitle" id="rectificacionHistorialSubtitulo"></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="rectificacionHistorialContenido">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Cerrar</button>
            </div>
        </div>
    </div>
</div>
