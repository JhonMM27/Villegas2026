<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formUpdate" method="post">
                @csrf
                <input type="hidden" id="method_field" name="_method">
                <div class="modal-header">
                    <h4 class="modal-title fs-5" id="modalTitle">Nueva Vacación</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="border border-primary rounded p-3 mb-3">
                                <h6 class="text-primary mb-3"><i class="bi bi-person me-2"></i>Datos de la Vacación</h6>
                                <div class="mb-3">
                                    <label for="empleado_nombre" class="form-label">Empleado <span class="text-danger">*</span></label>
                                    <input type="text" id="empleado_nombre" class="form-control form-control-sm" placeholder="Buscar por nombre o DNI..." autocomplete="off">
                                    <input type="hidden" id="empleado_id" name="empleado_id">
                                    <div class="invalid-feedback"></div>
                                    <small class="text-muted">Solo empleados con más de 1 año de servicio</small>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="anio_generado" class="form-label">Año Generado <span class="text-danger">*</span></label>
                                            <input type="number" id="anio_generado" name="anio_generado" class="form-control form-control-sm" min="2000" max="2100" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="dias_generados" class="form-label">Días Generados</label>
                                            <input type="number" id="dias_generados" name="dias_generados" class="form-control form-control-sm" value="15" min="1" max="15">
                                            <div class="invalid-feedback"></div>
                                            <small class="text-muted">15 por año</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="dias_tomados" class="form-label">Días Tomados</label>
                                            <input type="number" id="dias_tomados" name="dias_tomados" class="form-control form-control-sm" value="0" min="0">
                                            <div class="invalid-feedback"></div>
                                            <small class="text-muted">Días usados</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="dias_faltantes" class="form-label">Días Pendientes</label>
                                            <input type="text" id="dias_faltantes" class="form-control form-control-sm bg-light" readonly value="15">
                                            <small class="text-muted">Disponibles</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="border border-success rounded p-3 mb-3">
                                <h6 class="text-success mb-3"><i class="bi bi-calendar me-2"></i>Período de Vacaciones</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                            <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control form-control-sm">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                            <input type="date" id="fecha_fin" name="fecha_fin" class="form-control form-control-sm">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="border border-secondary rounded p-3">
                                <h6 class="text-secondary mb-3"><i class="bi bi-card-text me-2"></i>Observaciones</h6>
                                <textarea id="observaciones" name="observaciones" class="form-control form-control-sm" rows="2" placeholder="Notas adicionales..."></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
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