<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="transform: scale(0.93); transform-origin: top center;">
        <div class="modal-content">
            <form id="formUpdate" method="post">
                @csrf
                <input type="hidden" id="method_field" name="_method">
                <div class="modal-header">
                    <h4 class="modal-title fs-5" id="modalTitle">Dato Nutricional</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="border border-primary rounded p-3 mb-3">
                        <h6 class="text-primary mb-3"><i class="bi bi-info-circle me-2"></i>Datos del Ingrediente</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="ingrediente" class="form-label">Ingrediente <span class="text-danger">*</span></label>
                                <input type="text" id="ingrediente" name="ingrediente" class="form-control form-control-sm" required maxlength="100">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3">
                                <label for="procedencia" class="form-label">Procedencia</label>
                                <select id="procedencia" name="procedencia" class="form-select form-select-sm">
                                    <option value="">—</option>
                                    <option value="Nacional">Nacional</option>
                                    <option value="Importado">Importado</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3">
                                <label for="clasificacion" class="form-label">Clasificación</label>
                                <input type="text" id="clasificacion" name="clasificacion" class="form-control form-control-sm" maxlength="50">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-12">
                                <label for="nutriente" class="form-label">Nutriente</label>
                                <input type="text" id="nutriente" name="nutriente" class="form-control form-control-sm" maxlength="100">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-success rounded p-3 mb-3">
                        <h6 class="text-success mb-3"><i class="bi bi-clipboard-data me-2"></i>Composición Nutricional (Base)</h6>
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label for="materia_seca" class="form-label small">Materia Seca %</label>
                                <input type="number" step="0.01" id="materia_seca" name="materia_seca" class="form-control form-control-sm" min="0" max="100">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-2">
                                <label for="proteina_cruda" class="form-label small">Proteína Cruda %</label>
                                <input type="number" step="0.01" id="proteina_cruda" name="proteina_cruda" class="form-control form-control-sm" min="0">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-2">
                                <label for="enl" class="form-label small">ENL (Mcal/kg)</label>
                                <input type="number" step="0.01" id="enl" name="enl" class="form-control form-control-sm" min="0">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-2">
                                <label for="em" class="form-label small">EM (Mcal/kg)</label>
                                <input type="number" step="0.001" id="em" name="em" class="form-control form-control-sm" min="0">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-2">
                                <label for="fdn" class="form-label small">FDN %</label>
                                <input type="number" step="0.01" id="fdn" name="fdn" class="form-control form-control-sm" min="0" max="100">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-2">
                                <label for="fibra" class="form-label small">Fibra %</label>
                                <input type="number" step="0.01" id="fibra" name="fibra" class="form-control form-control-sm" min="0" max="100">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-2">
                                <label for="fda" class="form-label small">FDA %</label>
                                <input type="number" step="0.01" id="fda" name="fda" class="form-control form-control-sm" min="0" max="100">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-2">
                                <label for="grasa" class="form-label small">Grasa %</label>
                                <input type="number" step="0.01" id="grasa" name="grasa" class="form-control form-control-sm" min="0" max="100">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-2">
                                <label for="almidon" class="form-label small">Almidón %</label>
                                <input type="number" step="0.01" id="almidon" name="almidon" class="form-control form-control-sm" min="0" max="100">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-2">
                                <label for="azucar" class="form-label small">Azúcar %</label>
                                <input type="number" step="0.01" id="azucar" name="azucar" class="form-control form-control-sm" min="0" max="100">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-2">
                                <label for="ceniza" class="form-label small">Ceniza %</label>
                                <input type="number" step="0.01" id="ceniza" name="ceniza" class="form-control form-control-sm" min="0" max="100">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-2">
                                <label for="lactosa" class="form-label small">Lactosa %</label>
                                <input type="number" step="0.01" id="lactosa" name="lactosa" class="form-control form-control-sm" min="0" max="100">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-info rounded p-3 mb-3">
                        <h6 class="text-info mb-3"><i class="bi bi-droplet-half me-2"></i>Minerales</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="calcio" class="form-label small">Calcio %</label>
                                <input type="number" step="0.01" id="calcio" name="calcio" class="form-control form-control-sm" min="0" max="100">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="fosforo" class="form-label small">Fósforo %</label>
                                <input type="number" step="0.01" id="fosforo" name="fosforo" class="form-control form-control-sm" min="0" max="100">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="magnesio" class="form-label small">Magnesio %</label>
                                <input type="number" step="0.01" id="magnesio" name="magnesio" class="form-control form-control-sm" min="0" max="100">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-warning rounded p-3 mb-3">
                        <h6 class="text-warning mb-3"><i class="bi bi-bezier2 me-2"></i>Aminoácidos (%)</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="lisina" class="form-label small">Lisina %</label>
                                <input type="number" step="0.001" id="lisina" name="lisina" class="form-control form-control-sm" min="0">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="metionina" class="form-label small">Metionina %</label>
                                <input type="number" step="0.001" id="metionina" name="metionina" class="form-control form-control-sm" min="0">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="treonina" class="form-label small">Treonina %</label>
                                <input type="number" step="0.001" id="treonina" name="treonina" class="form-control form-control-sm" min="0">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-secondary rounded p-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" checked>
                            <label class="form-check-label" for="activo">Activo</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnSubmit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
