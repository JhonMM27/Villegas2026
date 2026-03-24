<!-- Modal de visualización -->
<div class="modal fade" id="modalViewFormulacion" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
     aria-labelledby="modalViewFormulacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content fs-7">
            <div class="modal-header">
                <h4 class="modal-title fs-5">
                    Formulación ID: {{ $formulacion->id }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="row">

                    <!-- DATOS PRINCIPALES -->
                    <div class="col-lg-12">
                        <div class="border border-primary rounded p-3">
                            <div class="row mb-2">

                                <div class="col-md-3">
                                    <label class="form-label">Fecha:</label>
                                    <p class="fw-bold">{{ \Carbon\Carbon::parse($formulacion->fecha)->format('Y-m-d') }}</p>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Cliente:</label>
                                    <p class="fw-bold">{{ $formulacion->cliente_id }} - {{ $formulacion->cliente_nombre }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Dirección:</label>
                                    <p class="fw-bold">{{ $formulacion->cliente->direccion }}</p>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Formulación:</label>
                                    <p class="fw-bold">{{ $formulacion->producto_nombre }}</p>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Empaque:</label>
                                    <p class="fw-bold">{{ $formulacion->producto_empaque }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Salida (Kg):</label>
                                    <p class="fw-bold">
                                        {{ $formulacion->salida_kg }}
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
                                            <th>Producto</th>
                                            <th>Empaque</th>
                                            <th>Línea</th>
                                            <th>Salida (KG)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                        $formulacionN = $formulacion->producto_nombre;
                                        @endphp
                                        @foreach($formulacion->detalles as $index => $detalle)
                                       <tr class="{{ trim($formulacionN) == trim($detalle->producto_nombre) ? 'table-secondary' : '' }}">
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>{{ $detalle->producto_nombre }}</td>
                                            <td class="text-center">{{ $detalle->producto_empaque }}</td>
                                            <td>{{ $detalle->producto_linea }}</td>
                                            <td class="text-end">{{ number_format($detalle->salida_kg, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Total</strong></td>
                                            <td class="text-end">
                                                {{ number_format($formulacion->detalles->sum('salida_kg'), 2) }}
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
                Usuario: <span class="me-auto fw-bold">{{ $formulacion->user_nombre }}</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>

        </div>
    </div>
</div>