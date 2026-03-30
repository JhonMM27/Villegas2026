<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="transform: scale(0.93); transform-origin: top center;">
        <div class="modal-content">
            <form id="formUpdate" method="post">
                @csrf
                <input type="hidden" id="method_field" name="_method">
                <input type="hidden" id="empleado_id" name="empleado_id">
                <div class="modal-header">
                    <h4 class="modal-title fs-5" id="modalTitle">Nuevo Pago de Planilla</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="border border-primary rounded p-3 mb-3">
                                <h6 class="text-primary mb-3"><i class="bi bi-person me-2"></i>Datos del Pago</h6>
                                <div class="mb-3">
                                    <label for="empleado_nombre" class="form-label">Empleado <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="empleado_nombre" class="form-control form-control-sm"
                                        placeholder="Buscar por nombre o DNI" autocomplete="off">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-2">
                                            <label for="mes" class="form-label">Mes <span
                                                    class="text-danger">*</span></label>
                                            <select id="mes" name="mes" class="form-select form-select-sm"
                                                required>
                                                <option value="1">Enero</option>
                                                <option value="2">Febrero</option>
                                                <option value="3">Marzo</option>
                                                <option value="4">Abril</option>
                                                <option value="5">Mayo</option>
                                                <option value="6">Junio</option>
                                                <option value="7">Julio</option>
                                                <option value="8">Agosto</option>
                                                <option value="9">Setiembre</option>
                                                <option value="10">Octubre</option>
                                                <option value="11">Noviembre</option>
                                                <option value="12">Diciembre</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-2">
                                            <label for="anio" class="form-label">Año <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" id="anio" name="anio"
                                                class="form-control form-control-sm" min="2020" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="border border-primary rounded p-3">
                                <h6 class="text-primary mb-3"><i class="bi bi-calculator me-2"></i>Cálculo del Pago</h6>
                                <div class="row">
                                    <div class="col-lg-3">
                                        <div class="mb-2">
                                            <label class="form-label text-muted small">Sueldo Planilla</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">S/</span>
                                                <input type="text" id="sueldo_planilla" class="form-control"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="mb-2">
                                            <label class="form-label text-muted small">Sueldo Real</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">S/</span>
                                                <input type="text" id="sueldo_real" class="form-control" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="mb-2">
                                            <label class="form-label text-muted small">Disponible</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">S/</span>
                                                <span id="disponible_label"
                                                    class="form-control bg-light d-flex align-items-center">0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="mb-2">
                                            <label for="horas_extras" class="form-label text-muted small">H.
                                                Extras</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">S/</span>
                                                <input type="number" id="horas_extras" name="horas_extras"
                                                    class="form-control" step="0.01" min="0"
                                                    value="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="border border-success rounded p-3 mb-3">
                                <h6 class="text-success mb-3"><i class="bi bi-currency-dollar me-2"></i>Total a Pagar
                                </h6>
                                <div class="text-center">
                                    <h2 class="text-success mb-0">S/ <span id="total_pagar_label">0.00</span></h2>
                                    <input type="hidden" name="total_pagar" id="total_pagar" value="0.00">
                                    <p class="text-muted small mb-0 mt-1">Disponible + H. Extras</p>
                                </div>
                            </div>
                            {{-- <div class="border border-info rounded p-3"> --}}
                            {{-- <h6 class="text-info mb-3"><i class="bi bi-wallet2 me-2"></i>Distribución de Caja</h6> --}}
                            @include('planilla.partials.caja-distribution')
                            {{-- </div> --}}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnSubmit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Confirmar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
