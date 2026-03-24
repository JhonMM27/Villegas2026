<!-- Modal de visualización de compra -->
<div class="modal fade" id="modalViewCompra" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalViewCompraLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content fs-7">
            <div class="modal-header">
                <h4 class="modal-title fs-5" id="modalTitle">Detalle de Venta {{ $venta->comprobante_tipo_codigo }}
                    {{ $venta->serie }} - {{ $venta->correlativo }}</h4>
                &nbsp;
                <button type="button" class="btn btn-warning btn-sm btn-duplicate-venta" data-id="{{ $venta->id }}">
                    <i class="bi bi-files"></i> Duplicar venta
                </button>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>

            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Datos principales de la venta -->
                    <div class="col-lg-7">
                        <div class="border border-primary rounded p-3">
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label class="form-label">Forma de Pago:</label>
                                    <p class="fw-bold">{{ $venta->pago_forma_nombre }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha venta:</label>
                                    <p class="fw-bold">
                                        {{ \Carbon\Carbon::parse($venta->fecha_venta)->format('Y-m-d H:i:s') }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha Vencimiento:</label>
                                    <p class="fw-bold">
                                        {{ \Carbon\Carbon::parse($venta->fecha_vencimiento)->format('Y-m-d') }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Comprobante:</label>
                                    <p class="fw-bold">{{ $venta->comprobante_tipo_codigo }}</p>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Serie:</label>
                                    <p class="fw-bold">{{ $venta->serie }}</p>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Correlativo:</label>
                                    <p class="fw-bold">{{ $venta->correlativo }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Doc. Pago Interno:</label>
                                    <p class="fw-bold">{{ $venta->docpagoi }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">A cuenta:</label>
                                    <p class="fw-bold">{{ $venta->acuenta }}</p>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Abonos:</label>
                                    <p class="fw-bold">{{ $venta->abonos }}</p>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Saldo:</label>
                                    <p class="fw-bold">{{ $venta->saldo }}</p>
                                </div>
                                <div class="col-md-4">
                                    <div class="border border-danger rounded px-2 py-1 mt-2 mt-md-0">
                                        <span class="fw-bold">Principal: </span>{{ $venta->importe_p }} <br>
                                        <span class="fw-bold">Depósito: </span>{{ $venta->importe_d }} <br>
                                        <span class="fw-bold">Consorcio: </span>{{ $venta->importe_c }} <br>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Datos del proveedor -->
                    <div class="col-lg-5">
                        <div class="border border-primary rounded px-2 py-1 mt-2 mt-md-0">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label class="form-label">Cliente:</label>
                                    <p class="fw-bold">{{ $venta->cliente_nombre }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ID Cliente:</label>
                                    <p class="fw-bold">{{ $venta->cliente_id }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Documento:</label>
                                    <p class="fw-bold">{{ $venta->cliente->documento_numero ?? '' }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Dirección:</label>
                                    <p class="fw-bold">{{ $venta->cliente->direccion ?? '' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de detalles -->
                    <div class="col-lg-12 mt-3">
                        <div class="border border-primary rounded p-3">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th>#</th>
                                            <th>Producto</th>
                                            <th>Unidad</th>
                                            <th>Empaque</th>
                                            <th>Cantidad</th>
                                            <th>Entregado</th>
                                            <th>Precio Unitario</th>
                                            <th>Subtotal</th>
                                            <th>Costo Unitario</th>
                                            <th>Rentabilidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($venta->detalles as $index => $detalle)
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td>{{ $detalle->producto->nombre }}</td>
                                                <td>{{ $detalle->unidad_codigo ?? '' }}</td>
                                                <td class="text-end">{{ $detalle->producto_empaque ?? '' }}</td>
                                                <td class="text-end">{{ number_format($detalle->cantidad, 2) }}</td>
                                                <td class="text-end">{{ number_format($detalle->entregado, 2) }}</td>
                                                <td class="text-end">{{ number_format($detalle->precio_unitario, 4) }}
                                                </td>
                                                <td class="text-end">{{ number_format($detalle->total, 2) }}</td>
                                                <td class="text-end">{{ number_format($detalle->costo_unitario, 4) }}

                                                <td class="text-end">{{ number_format($detalle->rentabilidad, 4) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="7" class="text-end"><strong>OP. Gravada</strong></td>
                                            <td class="text-end">
                                                {{ number_format($venta->op_gravada, 2) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="7" class="text-end"><strong>OP. Exonerada</strong></td>
                                            <td class="text-end">
                                                {{ number_format($venta->op_exonerada, 2) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="7" class="text-end"><strong>OP. Inafecta</strong></td>
                                            <td class="text-end">
                                                {{ number_format($venta->op_inafecta, 2) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="7" class="text-end"><strong>Impuesto</strong></td>
                                            <td class="text-end">
                                                {{ number_format($venta->impuesto, 2) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="7" class="text-end"><strong>Total</strong></td>
                                            <td class="text-end">
                                                {{ number_format($venta->total, 2) }}
                                            </td>

                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Tabla Provisional -->
                    @if ($venta->pagosProvisionales->isNotEmpty())
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
                                            @forelse($venta->pagosProvisionales as $index => $pago)
                                                <tr>
                                                    <td class="text-center">{{ $index + 1 }}</td>
                                                    <td class="text-center">
                                                        {{ optional($pago->ventaProvisional)->fecha_provisional
                                                            ? \Carbon\Carbon::parse($pago->ventaProvisional->fecha_provisional)->format('Y-m-d H:i:s')
                                                            : '' }}
                                                    </td>
                                                    <td class="text-center">
                                                        {{ $pago->ventaProvisional->numero_interno ?? '' }}
                                                    </td>
                                                    <td class="text-center">
                                                        {{ $pago->ventaProvisional->numero_recibo ?? '' }}
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
                                                <td colspan="4" class="text-end"><strong>Total Pagos
                                                        Provisionales</strong></td>
                                                <td class="text-end">
                                                    {{ number_format($venta->pagosProvisionales->sum('monto'), 2) }}
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($venta->entregas->isNotEmpty())
                        <div class="col-lg-6 mt-3">
                            <div class="border border-success rounded p-3">
                                <h6 class="fw-bold mb-2">Entregas</h6>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead class="table-light text-center">
                                            <tr>
                                                <th>#</th>
                                                <th>Fecha Entrega</th>
                                                <th>N° Recibo</th>
                                                <th>Usuario</th>
                                                <th>Comentario</th>
                                                <th>Productos</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($venta->entregas as $index => $entrega)
                                                <tr>
                                                    <td class="text-center">{{ $index + 1 }}</td>

                                                    <td class="text-center">
                                                        {{ \Carbon\Carbon::parse($entrega->fecha_entrega)->format('Y-m-d H:i:s') }}
                                                    </td>

                                                    <td class="text-center">
                                                        {{ $entrega->numero_recibo }}
                                                    </td>

                                                    <td>
                                                        {{ $entrega->user_nombre }}
                                                    </td>

                                                    <td>
                                                        {{ $entrega->comentario }}
                                                    </td>

                                                    <td>
                                                        @foreach ($entrega->detalles as $detalle)
                                                            {{ $detalle->producto_nombre }}
                                                            ({{ number_format($detalle->cantidad, 2) }})
                                                            <br>
                                                        @endforeach
                                                    </td>

                                                </tr>
                                            @endforeach
                                        </tbody>

                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                Usuario: <span class="me-auto fw-bold">{{ $venta->user_nombre ?? '' }}</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
