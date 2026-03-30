<div class="modal fade" id="modalUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="transform: scale(0.93); transform-origin: top center;">
        <div class="modal-content">
            <form id="formUpdate" method="post">
                @csrf
                <input type="hidden" id="method_field" name="_method">
                <input type="hidden" id="empleado_id" name="empleado_id">
                <div class="modal-header">
                    <h4 class="modal-title fs-5" id="modalTitle">Nuevo Adelanto</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="border border-primary rounded p-3 mb-3">
                                <h6 class="text-primary mb-3"><i class="bi bi-person me-2"></i>Datos del Adelanto</h6>
                                <div class="mb-3">
                                    <label for="empleado_nombre" class="form-label">Empleado <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="empleado_nombre" class="form-control form-control-sm"
                                        placeholder="Buscar por nombre o DNI" autocomplete="off">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="fecha" class="form-label">Fecha <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" id="fecha" name="fecha"
                                                class="form-control form-control-sm" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="monto" class="form-label">Monto <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">S/</span>
                                                <input type="number" id="monto" name="monto"
                                                    class="form-control form-control-sm" step="0.01" min="0.01"
                                                    required>
                                            </div>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="observaciones" class="form-label">Observaciones</label>
                                    <input type="text" id="observaciones" name="observaciones"
                                        class="form-control form-control-sm" placeholder="Observaciones adicionales...">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            {{-- <div class="border border-info rounded p-3 mb-3"> --}}
                            {{-- <h6 class="text-info mb-3"><i class="bi bi-wallet2 me-2"></i>Distribución de Caja</h6> --}}
                            @include('planilla.partials.caja-distribution')
                            {{-- </div> --}}
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
