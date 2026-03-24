<!-- Modal de visualización de compra -->
<div class="modal fade" id="modalViewCompra" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalViewCompraLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content fs-7">
            <div class="modal-header">
                <h4 class="modal-title fs-5" id="modalTitle">Detalle de Cotizacion {{ $cotizacion->comprobante_tipo_codigo }} {{ $cotizacion->serie }} - {{ $cotizacion->correlativo }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Datos principales de la cotizacion -->
                    <div class="col-lg-7">
                        <div class="border border-primary rounded p-3">
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label class="form-label">Forma de Pago:</label>
                                    <p class="fw-bold">{{ $cotizacion->pago_forma_nombre }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha cotizacion:</label>
                                    <p class="fw-bold"> {{ \Carbon\Carbon::parse($cotizacion->fecha_cotizacion)->format('Y-m-d H:i:s') }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Comprobante:</label>
                                    <p class="fw-bold">{{ $cotizacion->comprobante_tipo_codigo }}</p>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Serie:</label>
                                    <p class="fw-bold">{{ $cotizacion->serie }}</p>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Correlativo:</label>
                                    <p class="fw-bold">{{ $cotizacion->correlativo }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del proveedor -->
                    <div class="col-lg-5">
                        <div class="border border-primary rounded p-3">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label class="form-label">Cliente:</label>
                                    <p class="fw-bold">{{ $cotizacion->cliente_nombre }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ID Cliente:</label>
                                    <p class="fw-bold">{{ $cotizacion->cliente_id }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de detalles -->
                    <div class="col-lg-12 mt-3">
                        <div class="border border-primary rounded p-3">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm table-app">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th>#</th>
                                            <th>Producto</th>
                                            <th>Unidad</th>
                                            <th>Empaque</th>
                                            <th>Cantidad</th>
                                            <th>Cantidad Kg.</th>
                                            <th>Precio Unitario</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($cotizacion->detalles as $index => $detalle)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>{{ $detalle->producto->nombre }}</td>
                                            <td>{{ $detalle->unidad_codigo ?? '' }}</td>
                                            <td class="text-end">{{ $detalle->producto_empaque ?? '' }}</td>
                                            <td class="text-end">{{ number_format($detalle->cantidad, 2) }}</td>
                                            <td class="text-end">{{ number_format($detalle->salida_kg, 2) }}</td>
                                            <td class="text-end">{{ number_format($detalle->precio_unitario, 4) }}</td>
                                            <td class="text-end">{{ number_format($detalle->total, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="7" class="text-end"><strong>OP. Gravada</strong></td>
                                            <td class="text-end">
                                                {{ number_format($cotizacion->op_gravada, 2) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="7" class="text-end"><strong>OP. Exonerada</strong></td>
                                            <td class="text-end">
                                                {{ number_format($cotizacion->op_exonerada, 2) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="7" class="text-end"><strong>OP. Inafecta</strong></td>
                                            <td class="text-end">
                                                {{ number_format($cotizacion->op_inafecta, 2) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="7" class="text-end"><strong>Impuesto</strong></td>
                                            <td class="text-end">
                                                {{ number_format($cotizacion->impuesto, 2) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="7" class="text-end"><strong>Total</strong></td>
                                            <td class="text-end">
                                                {{ number_format($cotizacion->total, 2) }}
                                            </td>

                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                Usuario: <span class="me-auto fw-bold">{{ $cotizacion->user_nombre ?? '' }}</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>