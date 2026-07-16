{{-- ============================================================
    Modal Crear / Editar Fórmula de Alimento (Fullscreen compactado)
    max-width 1300px - Cerdos (17 nutrientes en 2 columnas)
============================================================ --}}
<div class="modal fade" id="modalFormula" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-xl" style="max-width: 1300px;">
        <div class="modal-content" style="font-size: 0.78rem;">
            <form id="formFormula" method="post">
                @csrf
                <input type="hidden" id="method_field_formula" name="_method">
                <input type="hidden" name="sync_detalles" value="1">

                <div class="modal-header bg-light py-2">
                    <h5 class="modal-title fs-6" id="formulaTitle">
                        <i class="bi bi-piggy-bank me-2"></i>Nueva Fórmula (Cerdos)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-lg-3">
                            <div class="border border-primary rounded p-2 mb-2">
                                <h6 class="text-primary mb-2 small">
                                    <i class="bi bi-info-circle me-2"></i>Datos de la Fórmula
                                </h6>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label for="formula_nombre" class="form-label small mb-1">
                                            Nombre <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" id="formula_nombre" name="nombre"
                                               class="form-control form-control-sm" required maxlength="100">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-12">
                                        <x-date-picker id="formula_fecha" name="fecha" label="Fecha" />
                                    </div>
                                    <div class="col-12">
                                        <label for="formula_descripcion" class="form-label small mb-1">Descripción</label>
                                        <textarea id="formula_descripcion" name="descripcion"
                                                  class="form-control form-control-sm" rows="1"></textarea>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="border border-success rounded p-2 mb-2">
                                <h6 class="text-success mb-2 small">
                                    <i class="bi bi-cash-coin me-2"></i>Parámetros de Costo
                                </h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label for="formula_kg_saco" class="form-label small mb-1">Kg/Saco</label>
                                        <input type="number" step="0.01" min="0" id="formula_kg_saco" name="kg_saco"
                                               class="form-control form-control-sm param-costo" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-6">
                                        <label for="formula_saco_vacio" class="form-label small mb-1">Saco vacío</label>
                                        <input type="number" step="0.01" min="0" id="formula_saco_vacio" name="saco_vacio"
                                               class="form-control form-control-sm param-costo" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-6">
                                        <label for="formula_mano_obra" class="form-label small mb-1">Mano de obra</label>
                                        <input type="number" step="0.01" min="0" id="formula_mano_obra" name="mano_obra"
                                               class="form-control form-control-sm param-costo" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-6">
                                        <label for="formula_energia" class="form-label small mb-1">Energía</label>
                                        <input type="number" step="0.01" min="0" id="formula_energia" name="energia"
                                               class="form-control form-control-sm param-costo" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-6">
                                        <label for="formula_merma" class="form-label small mb-1">Merma</label>
                                        <input type="number" step="0.01" min="0" id="formula_merma" name="merma"
                                               class="form-control form-control-sm param-costo" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-6">
                                        <label for="formula_precio_venta" class="form-label small mb-1">Precio Venta</label>
                                        <input type="number" step="0.01" min="0" id="formula_precio_venta" name="precio_venta"
                                               class="form-control form-control-sm param-costo" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check mt-1">
                                            <input class="form-check-input" type="checkbox" id="formula_activo" name="activo" value="1" checked>
                                            <label class="form-check-label small" for="formula_activo">Activa</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-9">
                            <div class="border border-info rounded p-2 mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="text-info mb-0 small">
                                        <i class="bi bi-list-ul me-2"></i>Ingredientes de la Fórmula
                                    </h6>
                                    <div class="input-group input-group-sm" style="max-width: 350px;">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" id="formula_ingrediente_search"
                                               class="form-control" placeholder="Buscar ingrediente para agregar..."
                                               autocomplete="off"
                                               style="position: relative; z-index: 1100;">
                                    </div>
                                </div>
                                <div id="formula_search_results" class="mb-2" style="position: relative; z-index: 100;"></div>

                                <div class="table-responsive" style="max-height: 260px; overflow-y: auto;">
                                    <table class="table table-bordered table-sm table-hover mb-0" id="tablaIngredientes" style="font-size: 0.78rem;">
                                        <thead class="table-light text-center" style="position: sticky; top: 0; z-index: 10;">
                                            <tr>
                                                <th style="width: 30px;"></th>
                                                <th>Ingrediente</th>
                                                <th>Procedencia</th>
                                                <th>Clasificación</th>
                                                <th>Nutriente</th>
                                                <th class="text-end">Aporte</th>
                                                <th class="text-end" style="width: 90px;">S/ Kg</th>
                                                <th class="text-end" style="width: 90px;">Cantidad (kg)</th>
                                                <th class="text-end">Costo</th>
                                            </tr>
                                        </thead>
                                        <tbody id="ingredientes_tbody">
                                            <tr id="ingredientes_empty_row">
                                                <td colspan="9" class="text-center text-muted py-3">
                                                    <i class="bi bi-search me-1"></i>
                                                    Busque y agregue ingredientes usando el buscador de arriba
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr class="fw-bold">
                                                <td colspan="7" class="text-end">Total Kg</td>
                                                <td class="text-end" id="ft_total_kg">0.00</td>
                                                <td class="text-end" id="ft_total_costo">0.00</td>
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
                                            <tr><td>S/ Tonelada</td><td class="text-end fw-bold" id="res_costo_tn">—</td></tr>
                                            <tr><td>S/ Kg</td><td class="text-end fw-bold" id="res_costo_kg">—</td></tr>
                                            <tr class="table-light"><td>&nbsp;&nbsp;+ Saco vacío</td><td class="text-end" id="res_saco_vacio">—</td></tr>
                                            <tr class="table-light"><td>&nbsp;&nbsp;+ Mano de obra</td><td class="text-end" id="res_mano_obra">—</td></tr>
                                            <tr class="table-light"><td>&nbsp;&nbsp;+ Energía</td><td class="text-end" id="res_energia">—</td></tr>
                                            <tr class="table-light"><td>&nbsp;&nbsp;+ Merma</td><td class="text-end" id="res_merma">—</td></tr>
                                            <tr class="table-secondary"><td>Costo / Saco</td><td class="text-end fw-bold" id="res_costo_saco">—</td></tr>
                                            <tr class="table-success"><td>Ganancia / Saco</td><td class="text-end fw-bold" id="res_ganancia">—</td></tr>
                                            <tr class="table-warning"><td>Margen</td><td class="text-end fw-bold" id="res_margen">—</td></tr>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="border border-info rounded p-2">
                                        <h6 class="text-info mb-2 small">
                                            <i class="bi bi-clipboard-data me-2"></i>Aporte Nutricional (Base 1000 kg)
                                        </h6>
                                        <div class="row g-1" style="font-size: 0.78rem;">
                                            <div class="col-6">
                                                <table class="table table-sm mb-0">
                                                    <tr><td>Materia Seca</td><td class="text-end fw-bold" id="nut_ms">—</td></tr>
                                                    <tr><td>Proteína Cruda</td><td class="text-end fw-bold" id="nut_pc">—</td></tr>
                                                    <tr><td>EM (Mcal/kg)</td><td class="text-end fw-bold" id="nut_em">—</td></tr>
                                                    <tr><td>Grasa</td><td class="text-end fw-bold" id="nut_grasa">—</td></tr>
                                                    <tr><td>Fibra</td><td class="text-end fw-bold" id="nut_fibra">—</td></tr>
                                                    <tr><td>FDN</td><td class="text-end fw-bold" id="nut_fdn">—</td></tr>
                                                    <tr><td>FDA</td><td class="text-end fw-bold" id="nut_fda">—</td></tr>
                                                    <tr><td>Calcio</td><td class="text-end fw-bold" id="nut_calcio">—</td></tr>
                                                    <tr><td>Fósforo</td><td class="text-end fw-bold" id="nut_fosforo">—</td></tr>
                                                </table>
                                            </div>
                                            <div class="col-6">
                                                <table class="table table-sm mb-0">
                                                    <tr><td>Magnesio</td><td class="text-end fw-bold" id="nut_magnesio">—</td></tr>
                                                    <tr><td>Almidón</td><td class="text-end fw-bold" id="nut_almidon">—</td></tr>
                                                    <tr><td>Azúcar</td><td class="text-end fw-bold" id="nut_azucar">—</td></tr>
                                                    <tr><td>Ceniza</td><td class="text-end fw-bold" id="nut_ceniza">—</td></tr>
                                                    <tr><td>Lactosa</td><td class="text-end fw-bold" id="nut_lactosa">—</td></tr>
                                                    <tr><td>Lisina</td><td class="text-end fw-bold" id="nut_lisina">—</td></tr>
                                                    <tr><td>Metionina</td><td class="text-end fw-bold" id="nut_metionina">—</td></tr>
                                                    <tr><td>Treonina</td><td class="text-end fw-bold" id="nut_treonina">—</td></tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="btnGuardarFormula">
                        <i class="bi bi-check-circle me-1"></i> Guardar Fórmula
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
