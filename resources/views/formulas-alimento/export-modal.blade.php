{{--
    Modal de exportación compartido (Vacuno y Cerdo).
    Variables esperadas:
      - $exportExcelRoute : nombre de ruta para Excel (NO usar route() aquí para que sea dinámico)
      - $exportPdfRoute   : nombre de ruta para PDF
--}}
<div class="modal fade" id="modalExportarFormula" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title fs-6">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Exportar Fórmula
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="alert alert-info py-2 mb-3" style="font-size: 0.85rem;">
                    <i class="bi bi-info-circle me-1"></i>
                    <span>Se exportará toda la información de la fórmula: ingredientes, costos y aporte nutricional.</span>
                </div>

                <div class="border rounded p-2 mb-3 bg-light">
                    <small class="text-muted d-block">Fórmula seleccionada</small>
                    <strong id="export_formula_nombre" class="d-block">—</strong>
                </div>

                <p class="text-muted small mb-3">Elige el formato de exportación:</p>

                <div class="row g-2">
                    <div class="col-6">
                        <button type="button" class="btn btn-success w-100" id="btnExportExcel">
                            <i class="bi bi-file-earmark-excel-fill me-2"></i>Excel (.xlsx)
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-danger w-100" id="btnExportPdf">
                            <i class="bi bi-file-earmark-pdf-fill me-2"></i>PDF
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>
