<!-- Modal de visualización -->
<div class="modal fade" id="modalViewnucleoPreparada" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
     aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content fs-7">
            <div class="modal-header">
                <h4 class="modal-title fs-5">
                    Nucleo Preparada ID: {{ $nucleoPreparada->id }}
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
                                    <p class="fw-bold">{{ \Carbon\Carbon::parse($nucleoPreparada->fecha)->format('Y-m-d') }}</p>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Núcleo:</label>
                                    <p class="fw-bold">{{ $nucleoPreparada->nucleo_nombre }}</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Unidad:</label>
                                    <p class="fw-bold">{{ $nucleoPreparada->unidad_nombre }}</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Empaque:</label>
                                    <p class="fw-bold">{{ $nucleoPreparada->producto_empaque }}</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Cant. Porcentaje:</label>
                                    <p class="fw-bold">{{ $nucleoPreparada->cantidad_porcentaje }}</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Total Kg:</label>
                                    <p class="fw-bold">{{ $nucleoPreparada->ingreso_kg }}</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Costo Unitario:</label>
                                    <p class="fw-bold">{{ $nucleoPreparada->costo_unitario }}</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Total Sacos:</label>
                                    <p class="fw-bold">{{ $nucleoPreparada->ingreso_saco }}</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Items:</label>
                                    <p class="fw-bold">{{ $nucleoPreparada->items }}</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Número interno:</label>
                                    <p class="fw-bold">{{ $nucleoPreparada->numero_interno }}</p>
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
                                            <th>Unidad</th>
                                            <th>Cant. Porc.</th>
                                            <th>Total Kg</th>
                                            <th>Total Soles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($nucleoPreparada->detalles as $index => $detalle)
                                       <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>{{ $detalle->producto_nombre }}</td>
                                            <td class="text-center">{{ $detalle->producto_empaque }}</td>
                                            <td>{{ $detalle->unidad_codigo }}</td>
                                            <td class="text-end">{{ number_format($detalle->cantidad_porcentaje, 4) }}</td>
                                            <td class="text-end">{{ number_format($detalle->salida_kg, 4) }}</td>
                                            <td class="text-end">{{ number_format($detalle->salida_soles, 4) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Kilos por saco</strong></td>
                                            <td class="text-end">
                                                {{ number_format($nucleoPreparada->producto_empaque, 2) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Costo por saco</strong></td>
                                            <td class="text-end">
                                                {{ number_format($nucleoPreparada->costo_unitario, 4) }}
                                            </td>
                                        </tr>
                                         <tr>
                                            <td colspan="6" class="text-end"><strong>Cantidad Sacos</strong></td>
                                            <td class="text-end">
                                                {{ number_format($nucleoPreparada->ingreso_saco, 4) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Costo total</strong></td>
                                            <td class="text-end">
                                                {{ number_format($nucleoPreparada->ingreso_soles, 4) }}
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
                Usuario: <span class="me-auto fw-bold">{{ $nucleoPreparada->user_nombre }}</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>

        </div>
    </div>
</div>