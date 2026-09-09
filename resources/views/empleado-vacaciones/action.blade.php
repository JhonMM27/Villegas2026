{{-- Modal de crear/editar vacación --}}
{{-- Contiene: datos del derecho (empleado, año, resumen visual) y período de vacaciones (fechas, días del tramo) --}}
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
                        {{-- Panel izquierdo: Datos del Derecho Vacacional --}}
                        <div class="col-lg-6">
                            <div class="border border-primary rounded p-3 mb-3">
                                <h6 class="text-primary mb-3"><i class="bi bi-person me-2"></i>Datos del Derecho</h6>
                                {{-- Búsqueda de empleado --}}
                                <div class="mb-3">
                                    <label for="empleado_nombre" class="form-label">Empleado <span class="text-danger">*</span></label>
                                    <input type="text" id="empleado_nombre" class="form-control form-control-sm" placeholder="Buscar por nombre o DNI..." autocomplete="off">
                                    <input type="hidden" id="empleado_id" name="empleado_id">
                                    <div class="invalid-feedback"></div>
                                    <small class="text-muted">Solo empleados con más de 1 año de servicio</small>
                                </div>
                                {{-- Año del derecho vacacional --}}
                                <div class="mb-3">
                                    <label for="anio_generado" class="form-label">Año del Derecho <span class="text-danger">*</span></label>
                                    <input type="number" id="anio_generado" name="anio_generado" class="form-control form-control-sm" min="2000" max="2100" required>
                                    <div class="invalid-feedback"></div>
                                    <small class="text-muted">Año laboral que genera los 15 días</small>
                                </div>
                                {{-- Resumen visual de disponibilidad --}}
                                <div id="resumenDisponibilidad" class="alert alert-info py-2 px-3 mb-0 d-flex align-items-center">
                                    <i class="bi bi-info-circle me-2 fs-5"></i>
                                    <div>
                                        <strong id="resumenTexto">Seleccione un empleado</strong>
                                        <div class="small text-muted" id="resumenDetalle"></div>
                                    </div>
                                </div>
                                {{-- Campo oculto para días disponibles (usado en validación JS) --}}
                                <input type="hidden" id="dias_disponibles" value="15">
                                {{-- Campo oculto para dias_generados (siempre 15) --}}
                                <input type="hidden" id="dias_generados" name="dias_generados" value="15">
                            </div>
                        </div>
                        {{-- Panel derecho: Período de vacaciones y observaciones --}}
                        <div class="col-lg-6">
                            <div class="border border-success rounded p-3 mb-3">
                                <h6 class="text-success mb-3"><i class="bi bi-calendar me-2"></i>Período de Vacaciones</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <x-date-picker id="fecha_inicio" label="Fecha Inicio" />
                                    </div>
                                    <div class="col-6">
                                        <x-date-picker id="fecha_fin" label="Fecha Fin" />
                                    </div>
                                </div>
                                {{-- Días del tramo: auto-calculado desde fechas --}}
                                <div class="mt-2">
                                    <label class="form-label">Días de este tramo</label>
                                    <input type="text" id="dias_tramo_display" class="form-control form-control-sm bg-light fw-bold" readonly value="0">
                                    <div id="dias_tramo_feedback" class="invalid-feedback"></div>
                                    <small class="text-muted">Calculado automáticamente desde las fechas</small>
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