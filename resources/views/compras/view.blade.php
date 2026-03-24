<!-- Modal de visualización de compra -->
<div class="modal fade" id="modalViewCompra" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalViewCompraLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content fs-7">
            <div class="modal-header">
                <h4 class="modal-title fs-5" id="modalTitle">Detalle de Compra {{ $compra->comprobante_tipo_codigo }} {{ $compra->serie }} - {{ $compra->correlativo }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Datos principales de la compra -->
                    <div class="col-lg-7">
                        <div class="border border-primary rounded p-3">
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label class="form-label">Forma de Pago:</label>
                                    <p class="fw-bold">{{ $compra->pago_forma_nombre }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha venta:</label>
                                    <p class="fw-bold"> {{ \Carbon\Carbon::parse($compra->fecha_compra)->format('Y-m-d H:i:s') }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha Vencimiento:</label>
                                    <p class="fw-bold">{{ \Carbon\Carbon::parse($compra->fecha_vencimiento)->format('Y-m-d')}}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Comprobante:</label>
                                    <p class="fw-bold">{{ $compra->comprobante_tipo_codigo }}</p>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Serie:</label>
                                    <p class="fw-bold">{{ $compra->serie }}</p>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Correlativo:</label>
                                    <p class="fw-bold">{{ $compra->correlativo }}</p>
                                </div>                               
                                <div class="col-md-4">
                                    <label class="form-label">Doc. Pago Interno:</label>
                                    <p class="fw-bold">{{ $compra->docpagoi }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">A cuenta:</label>
                                    <p class="fw-bold">{{ $compra->acuenta}}</p>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Abonos:</label>
                                    <p class="fw-bold">{{ $compra->abonos }}</p>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Saldo:</label>
                                    <p class="fw-bold">{{ $compra->saldo }}</p>
                                </div>
                                <div class="col-md-4">
                                    <div class="border border-danger rounded px-2 py-1 mt-2 mt-md-0">
                                        <span class="fw-bold">Principal: </span>{{ $compra->importe_p }} <br>
                                        <span class="fw-bold">Depósito: </span>{{ $compra->importe_d }} <br>
                                        <span class="fw-bold">Consorcio: </span>{{ $compra->importe_c }} <br>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>

                    <!-- Datos del proveedor -->
                    <div class="col-lg-5">
                        <div class="border border-primary rounded p-3">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label class="form-label">Proveedor:</label>
                                    <p class="fw-bold">{{ $compra->proveedor->razon_social }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ID Proveedor:</label>
                                    <p class="fw-bold">{{ $compra->proveedor->id }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Documento:</label>
                                    <p class="fw-bold">{{ $compra->proveedor->documento_numero ?? '' }}</p>
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
                                            <th>Precio Unitario Servicio</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($compra->detalles as $index => $detalle)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>{{ $detalle->producto->nombre }}</td>
                                            <td>{{ $detalle->unidad_codigo ?? '' }}</td>
                                            <td class="text-end">{{ $detalle->producto_empaque ?? '' }}</td>
                                            <td class="text-end">{{ number_format($detalle->cantidad, 2) }}</td>
                                            <td class="text-end">{{ number_format($detalle->cantidad_kgm, 2) }}</td>
                                            <td class="text-end">{{ number_format($detalle->costo_unitario, 4) }}</td>
                                            <td class="text-end">{{ number_format($detalle->costo_unitario_servicio, 2) }}</td>
                                            <td class="text-end">{{ number_format($detalle->total, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="8" class="text-end"><strong>OP. Gravada</strong></td>
                                            <td class="text-end">
                                                {{ number_format($compra->op_gravada, 2) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="8" class="text-end"><strong>OP. Exonerada</strong></td>
                                            <td class="text-end">
                                                {{ number_format($compra->op_exonerada, 2) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="8" class="text-end"><strong>OP. Inafecta</strong></td>
                                            <td class="text-end">
                                                {{ number_format($compra->op_inafecta, 2) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="8" class="text-end"><strong>Impuesto</strong></td>
                                            <td class="text-end">
                                                {{ number_format($compra->impuesto, 2) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="8" class="text-end"><strong>Total</strong></td>
                                            <td class="text-end">
                                                {{ number_format($compra->total, 2) }}
                                            </td>

                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    @if($compra->pagosProvisionales->isNotEmpty())
                    <div class="col-lg-6 mt-3">
                        <div class="border border-warning rounded p-3">
                            <h6 class="fw-bold mb-2">Pagos Provisionales</h6>

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th>#</th>
                                            <th>Fecha Provisional</th>
                                            <th>N° Interno</th>
                                            <th>N° Recibo</th>
                                            <th>Monto</th>
                                            <th>Comentario</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($compra->pagosProvisionales as $index => $pago)
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td class="text-center">
                                                    {{ optional($pago->compraProvisional)->fecha_provisional
                                                        ? \Carbon\Carbon::parse($pago->compraProvisional->fecha_provisional)->format('Y-m-d H:i:s')
                                                        : '' }}
                                                </td>
                                                <td class="text-center">
                                                    {{ $pago->compraProvisional->numero_interno ?? '' }}
                                                </td>
                                                <td class="text-center">
                                                    {{ $pago->compraProvisional->numero_recibo ?? '' }}
                                                </td>
                                                <td class="text-end">{{ number_format($pago->monto, 2) }}</td>
                                                <td>{{ $pago->comentario }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted">
                                                    No existen pagos provisionales
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Total Pagos Provisionales</strong></td>
                                            <td class="text-end">{{ number_format($compra->pagosProvisionales->sum('monto'), 2) }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                Usuario: <span class="me-auto fw-bold">{{ $compra->user_nombre ?? '' }}</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>