<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formUpdate" method="post">
                @csrf
                <input type="hidden" id="method_field" name="_method">
                <div class="modal-header">
                    <h4 class="modal-title fs-5" id="modalTitle">Nuevo Empleado</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="border border-primary rounded p-3 mb-3">
                                <h6 class="text-primary mb-3"><i class="bi bi-person me-2"></i>Datos Personales</h6>
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="nombre" name="nombre"
                                        class="form-control form-control-sm" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="dni" class="form-label">DNI <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="dni" name="dni"
                                                class="form-control form-control-sm" maxlength="8" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input type="text" id="telefono" name="telefono"
                                                class="form-control form-control-sm" maxlength="9">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="correo" class="form-label">Correo</label>
                                    <input type="email" id="correo" name="correo"
                                        class="form-control form-control-sm">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <x-date-picker id="fecha_ingreso" label="Fecha de Ingreso" />
                                </div>
                                <div class="mb-3">
                                    <x-date-picker id="fecha_salida" label="Fecha de Salida" />
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="border border-success rounded p-3 mb-3">
                                <h6 class="text-success mb-3"><i class="bi bi-currency-dollar me-2"></i>Información
                                    Salarial</h6>
                                <div class="mb-3">
                                    <label for="sueldo_base" class="form-label">Sueldo no planilla</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">S/</span>
                                        <input type="number" id="sueldo_base" name="sueldo_base"
                                            class="form-control form-control-sm" step="0.01" min="0" readonly>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="sueldo_planilla" class="form-label">Sueldo Planilla <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">S/</span>
                                        <input type="number" id="sueldo_planilla" name="sueldo_planilla"
                                            class="form-control form-control-sm" step="0.01" min="0" required>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="sueldo_real" class="form-label">Sueldo Real <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">S/</span>
                                        <input type="number" id="sueldo_real" name="sueldo_real"
                                            class="form-control form-control-sm" step="0.01" min="0" required>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="vigente_mes" class="form-label">Vigente desde el mes <span class="text-danger">*</span></label>
                                    <input type="month" id="vigente_mes" class="form-control form-control-sm" required>
                                    <input type="hidden" id="vigente_desde" name="vigente_desde">
                                    <div class="form-text">El cambio se aplicará desde el primer día del mes seleccionado.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="motivo" class="form-label">Motivo del cambio</label>
                                    <input type="text" id="motivo" name="motivo" class="form-control form-control-sm"
                                        maxlength="255" placeholder="Ingreso, aumento, reducción, regularización...">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="observaciones_sueldo" class="form-label">Observaciones salariales</label>
                                    <textarea id="observaciones_sueldo" name="observaciones_sueldo"
                                        class="form-control form-control-sm" rows="2"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="border border-secondary rounded p-3">
                                <h6 class="text-secondary mb-3"><i class="bi bi-circle-fill me-2"
                                        style="font-size:0.5rem;"></i>Estado</h6>
                                <select id="estado" name="estado" class="form-select form-select-sm" required>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
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

@can('empleados_edit')
    <div class="modal fade" id="modalRectificarSueldo" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="rectificarSueldoTitulo" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content payroll-correction-modal">
                <form id="formRectificarSueldo" novalidate>
                    @csrf
                    <input type="hidden" id="rect_empleado_id">

                    <div class="modal-header payroll-correction-header">
                        <div class="d-flex align-items-center gap-3">
                            <span class="payroll-correction-icon" aria-hidden="true">
                                <i class="bi bi-cash-coin"></i>
                            </span>
                            <div>
                                <h4 class="modal-title fs-5 mb-1" id="rectificarSueldoTitulo">Rectificar sueldo</h4>
                                <p class="mb-0 small text-body-secondary">Corrige un período sin alterar pagos confirmados.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div id="rect_alerta" class="alert alert-danger d-none" role="alert"></div>

                        <div class="employee-summary mb-4">
                            <div class="employee-avatar" aria-hidden="true"><i class="bi bi-person"></i></div>
                            <div class="flex-grow-1 min-w-0">
                                <span class="small text-body-secondary d-block">Empleado</span>
                                <strong id="rect_empleado_nombre" class="d-block text-truncate">—</strong>
                                <span id="rect_empleado_dni" class="small text-body-secondary">DNI: —</span>
                            </div>
                            <span class="badge text-bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                Rectificación
                            </span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-5">
                                <label for="rect_periodo" class="form-label fw-semibold">
                                    Período afectado <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                    <input id="rect_periodo" type="month" class="form-control" required>
                                    <div class="invalid-feedback">Seleccione el mes que desea rectificar.</div>
                                </div>
                                <div class="form-text">El sueldo se aplicará desde el primer día del mes.</div>
                            </div>
                            <div class="col-md-7">
                                <div class="correction-notice h-100">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Solo se recalcularán pagos pendientes. Si el período ya fue pagado, primero deberá revertirlo.</span>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h5 class="fs-6 fw-bold mb-1">Importes corregidos</h5>
                                <p class="small text-body-secondary mb-0">El importe no planilla se calcula automáticamente.</p>
                            </div>
                            <span class="formula-pill">Real − Planilla</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="rect_real" class="form-label">Sueldo real <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg salary-input">
                                    <span class="input-group-text">S/</span>
                                    <input id="rect_real" type="number" min="0" step="0.01" class="form-control"
                                        inputmode="decimal" required>
                                    <div class="invalid-feedback">Ingrese un sueldo real válido.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="rect_planilla" class="form-label">Sueldo planilla <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg salary-input">
                                    <span class="input-group-text">S/</span>
                                    <input id="rect_planilla" type="number" min="0" step="0.01" class="form-control"
                                        inputmode="decimal" required>
                                    <div class="invalid-feedback">No puede superar al sueldo real.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="rect_no_planilla" class="form-label">No planilla</label>
                                <div class="input-group input-group-lg salary-result">
                                    <span class="input-group-text">S/</span>
                                    <input id="rect_no_planilla" type="text" class="form-control fw-bold" value="0.00"
                                        readonly tabindex="-1">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="rect_motivo" class="form-label fw-semibold">
                                Motivo de la rectificación <span class="text-danger">*</span>
                            </label>
                            <textarea id="rect_motivo" class="form-control" rows="3" maxlength="255" required
                                placeholder="Ejemplo: Se registró S/ 2,200 por error; el sueldo correcto es S/ 1,900."></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <div class="invalid-feedback">Explique brevemente por qué se realiza la corrección.</div>
                                <small class="text-body-secondary ms-auto"><span id="rect_motivo_contador">0</span>/255</small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer px-4 py-3">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" id="btnRectificarSueldo" class="btn btn-warning px-4">
                            <i class="bi bi-arrow-repeat me-1"></i> Rectificar y recalcular
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
