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
