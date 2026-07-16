{{-- ============================================================
    Modal de Solo Lectura: Ver Fórmula de Alimento
    Compacto (max-width 1100px) - Vacunos
============================================================ --}}
<div class="modal fade" id="modalViewFormula" tabindex="-1">
    <div class="modal-dialog modal-xl" style="max-width: 1100px;">
        <div class="modal-content" style="font-size: 0.78rem;">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title fs-6">
                    <i class="bi bi-eye me-2"></i>Detalle de Fórmula
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="card bg-light border-0 mb-2">
                    <div class="card-body py-2 px-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Nombre</small>
                                <h6 class="mb-0 fw-bold" id="view_nombre">—</h6>
                            </div>
                            <div class="col-md-3 text-end">
                                <small class="text-muted d-block">Kg/Saco</small>
                                <span class="fw-bold" id="view_kg_saco">—</span>
                            </div>
                            <div class="col-md-3 text-end">
                                <small class="text-muted d-block">P. Venta</small>
                                <span class="fw-bold" id="view_precio_venta">—</span>
                            </div>
                            <div class="col-md-9">
                                <small class="text-muted d-block">Descripción</small>
                                <span id="view_descripcion" class="text-muted">—</span>
                            </div>
                            <div class="col-md-3 text-end">
                                <small class="text-muted d-block">Fecha</small>
                                <span id="view_fecha">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border border-primary rounded p-2 mb-2">
                    <h6 class="text-primary mb-2 small">
                        <i class="bi bi-list-ul me-2"></i>Ingredientes Usados
                    </h6>
                    <div class="table-responsive" style="max-height: 240px; overflow-y: auto;">
                        <table class="table table-sm table-striped table-bordered table-hover mb-0" style="font-size: 0.78rem;">
                            <thead class="table-light" style="position: sticky; top: 0;">
                                <tr>
                                    <th>Ingrediente</th>
                                    <th>Clasificación</th>
                                    <th>Procedencia</th>
                                    <th>Nutriente</th>
                                    <th class="text-end">Aporte</th>
                                    <th class="text-end">S/ Kg</th>
                                    <th class="text-end">Cantidad kg</th>
                                    <th class="text-end">Costo</th>
                                </tr>
                            </thead>
                            <tbody id="view_ingredientes_body"></tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="6" class="text-end">Total</td>
                                    <td class="text-end" id="view_total_kg_ft">—</td>
                                    <td class="text-end" id="view_total_costo_ft">—</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="border border-success rounded p-2">
                            <h6 class="text-success mb-2 small">
                                <i class="bi bi-cash-coin me-2"></i>Resumen de Costos
                            </h6>
                            <table class="table table-sm mb-0" style="font-size: 0.78rem;">
                                <tr><td>S/ Tonelada</td><td class="text-end fw-bold" id="view_costo_tonelada">—</td></tr>
                                <tr><td>S/ Kg</td><td class="text-end fw-bold" id="view_costo_kg">—</td></tr>
                                <tr class="table-light"><td>&nbsp;&nbsp;+ Saco vacío</td><td class="text-end" id="view_saco_vacio">—</td></tr>
                                <tr class="table-light"><td>&nbsp;&nbsp;+ Mano de obra</td><td class="text-end" id="view_mano_obra">—</td></tr>
                                <tr class="table-light"><td>&nbsp;&nbsp;+ Energía</td><td class="text-end" id="view_energia">—</td></tr>
                                <tr class="table-light"><td>&nbsp;&nbsp;+ Merma</td><td class="text-end" id="view_merma">—</td></tr>
                                <tr class="table-secondary"><td>Costo / Saco</td><td class="text-end fw-bold" id="view_costo_saco">—</td></tr>
                                <tr class="table-success"><td>Ganancia / Saco</td><td class="text-end fw-bold" id="view_ganancia_saco">—</td></tr>
                                <tr class="table-warning"><td>Margen</td><td class="text-end fw-bold" id="view_margen">—</td></tr>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border border-info rounded p-2">
                            <h6 class="text-info mb-2 small">
                                <i class="bi bi-clipboard-data me-2"></i>Aporte Nutricional (Base 1000 kg)
                            </h6>
                            <table class="table table-sm mb-0" style="font-size: 0.78rem;">
                                <tr><td>Materia Seca</td><td class="text-end fw-bold" id="view_ms">—</td></tr>
                                <tr><td>Proteína Cruda</td><td class="text-end fw-bold" id="view_pc">—</td></tr>
                                <tr><td>ENL (Mcal/kg)</td><td class="text-end fw-bold" id="view_enl">—</td></tr>
                                <tr><td>FDN</td><td class="text-end fw-bold" id="view_fdn">—</td></tr>
                                <tr><td>Grasa</td><td class="text-end fw-bold" id="view_grasa">—</td></tr>
                                <tr><td>Almidón</td><td class="text-end fw-bold" id="view_almidon">—</td></tr>
                                <tr><td>Azúcar</td><td class="text-end fw-bold" id="view_azucar">—</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
