<!-- Modal de visualización -->
<div class="modal fade" id="modalViewpreparada" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
     aria-labelledby="modalViewpreparadaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content fs-7">
            <div class="modal-header">
                <h4 class="modal-title fs-5">
                    Preparada ID: {{ $preparada->id }}
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
                                    <p class="fw-bold">{{ \Carbon\Carbon::parse($preparada->fecha)->format('Y-m-d H:i:s')}}</p>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Cliente:</label>
                                    <p class="fw-bold">{{ $preparada->cliente_id }} - {{ $preparada->cliente_nombre }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Dirección:</label>
                                    <p class="fw-bold">{{ $preparada->cliente->direccion }}</p>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Preparada:</label>
                                    <p class="fw-bold">{{ $preparada->producto_nombre }}</p>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Empaque:</label>
                                    <p class="fw-bold">{{ $preparada->producto_empaque }} (Costo: {{ $preparada->costo_unitario }})</p>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Total (Sacos / KG / Soles):</label>
                                    <p class="fw-bold">
                                        {{ $preparada->ingreso_saco }} / 
                                        {{ $preparada->ingreso_kg }} / 
                                        {{ $preparada->ingreso_soles }}
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
                                            <th>Total (KG)</th>
                                            <th>Precio Unitario</th>
                                            <th>Total Soles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                        $preparadaN = $preparada->producto_nombre;
                                        @endphp
                                        @foreach($preparada->detalles as $index => $detalle)
                                       <tr class="{{ trim($preparadaN) == trim($detalle->producto_nombre) ? 'table-secondary' : '' }}">
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>{{ $detalle->producto_nombre }}</td>
                                            <td class="text-center">{{ $detalle->producto_empaque }}</td>
                                            <td>{{ $detalle->producto->linea->nombre }}</td>
                                            <td class="text-end">{{$detalle->salida_kg}}</td>
                                            <td class="text-end">{{$detalle->precio_unitario}}</td>
                                            <td class="text-end">{{$detalle->salida_soles}}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Kilos por saco</strong></td>
                                            <td class="text-end">
                                                {{ number_format($preparada->producto_empaque, 2) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Costo por saco</strong></td>
                                            <td class="text-end">
                                                {{ number_format($preparada->costo_unitario, 4) }}
                                            </td>
                                        </tr>
                                         <tr>
                                            <td colspan="6" class="text-end"><strong>Cantidad Sacos</strong></td>
                                            <td class="text-end">
                                                {{ number_format($preparada->ingreso_saco, 4) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Costo total</strong></td>
                                            <td class="text-end">
                                                {{ number_format($preparada->ingreso_soles, 4) }}
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
                Usuario: <span class="me-auto fw-bold">{{ $preparada->user_nombre }}</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>

        </div>
    </div>
</div>