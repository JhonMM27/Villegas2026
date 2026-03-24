<!-- Modal de visualización -->
<div class="modal fade" id="modalViewFormulacion" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
     aria-labelledby="modalViewFormulacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content fs-7">
            <div class="modal-header">
                <h4 class="modal-title fs-5">
                    Núcleo ID: {{ $nucleo->id }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="row">

                    <!-- DATOS PRINCIPALES -->
                    <div class="col-lg-12">
                        <div class="border border-primary rounded p-3">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">Núcleo:</label>
                                    <p class="fw-bold">{{ $nucleo->nombre }}</p>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Unidad:</label>
                                    <p class="fw-bold">{{ $nucleo->unidad_nombre }}</p>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Empaque:</label>
                                    <p class="fw-bold">{{ $nucleo->empaque }}</p>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Cantidad Porc.:</label>
                                    <p class="fw-bold">
                                        {{ $nucleo->cantidad_porcentaje }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TABLA DETALLE -->
                    <div class="col-lg-12 mt-3">
                        <div class="border border-primary rounded p-3">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm table-condensed table-app">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th>#</th>
                                            <th>Producto ID</th>
                                            <th>Producto</th>
                                            <th>Unidad Código</th>
                                            <th>Cantidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($nucleo->detalles as $index => $detalle)
                                       <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>{{ $detalle->producto_id}}</td>
                                            <td class="text-center">{{ $detalle->producto_nombre }}</td>
                                            <td>{{ $detalle->unidad_codigo }}</td>
                                            <td class="text-end">{{ $detalle->cantidad}}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Cantidad</strong></td>
                                            <td class="text-end">
                                                {{ $nucleo->cantidad_porcentaje }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div> <!-- row -->
            </div> <!-- modal-body -->

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>

        </div>
    </div>
</div>