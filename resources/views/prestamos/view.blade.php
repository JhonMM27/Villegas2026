<!-- Modal de visualización de compra -->
<div class="modal fade" id="modalViewCompra" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalViewCompraLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content fs-7">
            <div class="modal-header">
                <h4 class="modal-title fs-5" id="modalTitle">Detalle de {{ $prestamo->movimiento_tipo }} {{ $prestamo->serie }} - {{ $prestamo->correlativo }}</h4>
                @php
                    $tienePendiente = false;
                    if(isset($saldos) && is_array($saldos)){
                        foreach($saldos as $s) {
                            if(($s['pendiente'] ?? 0) > 0) {
                                $tienePendiente = true;
                                break;
                            }
                        }
                    }
                @endphp
                @if(($prestamo->movimiento_tipo=='PD' || $prestamo->movimiento_tipo=='PA') && $tienePendiente && $prestamo->estado !== 'anulada')
                &nbsp;
                <button type="button"
                    class="btn btn-warning btn-sm btn-registrar-devolucion"
                    data-id="{{ $prestamo->id }}" data-tipo="{{ $prestamo->movimiento_tipo }}">
                    <i class="bi bi-files"></i> Registrar devolución
                </button>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Datos principales de la compra -->
                    <div class="col-lg-4">
                        <div class="border border-primary rounded p-3">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">Movimiento</label>
                                    @php
                                        $tipos = [
                                            'PA' => 'Préstamo A',
                                            'PD' => 'Préstamo DE',
                                            'DA' => 'Devolución A',
                                            'DD' => 'Devolución DE',
                                        ];
                                    @endphp

                                    <p class="fw-bold">
                                        {{ $tipos[$prestamo->movimiento_tipo] ?? $prestamo->movimiento_tipo }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Fecha Hora:</label>
                                    <p class="fw-bold">{{ \Carbon\Carbon::parse($prestamo->fecha_prestamo)->format('Y-m-d H:i') }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Comprobante:</label>
                                    <p class="fw-bold">{{ $prestamo->comprobante_tipo_codigo }} {{ $prestamo->serie }} - {{ $prestamo->correlativo }}</p>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Referencia:</label>
                                    <p class="fw-bold">{{ $prestamo->prestamo_referencia_id }}</p>
                                    <p class="fw-bold">{{ $prestamo->prestamoReferencia?->comprobante_tipo_codigo }} {{ $prestamo->prestamoReferencia?->serie }} - {{ $prestamo->prestamoReferencia?->correlativo }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del proveedor -->
                    <div class="col-lg-4">
                        <div class="border border-primary rounded p-3">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label class="form-label">Origen:</label>
                                    <p class="fw-bold">{{ $prestamo->clienteOrigen->razon_social }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ID Cliente:</label>
                                    <p class="fw-bold">{{ $prestamo->clienteOrigen->id }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Documento:</label>
                                    <p class="fw-bold">{{ $prestamo->clienteOrigen->documento_numero ?? '' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="border border-primary rounded p-3">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label class="form-label">Destino:</label>
                                    <p class="fw-bold">{{ $prestamo->clienteDestino->razon_social }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ID Cliente:</label>
                                    <p class="fw-bold">{{ $prestamo->clienteDestino->id }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Documento:</label>
                                    <p class="fw-bold">{{ $prestamo->clienteDestino->documento_numero ?? '' }}</p>
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
                                            @if(!empty($saldos))
                                            <th>Devuelto (Kg)</th>
                                            <th>Pendiente (Kg)</th>
                                            @endif
                                            <th>Precio Unitario</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($prestamo->detalles as $index => $detalle)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>{{ $detalle->producto->nombre }}</td>
                                            <td>{{ $detalle->unidad_nombre ?? '' }}</td>
                                            <td class="text-end">{{ $detalle->producto_empaque ?? '' }}</td>
                                            <td class="text-end">{{ number_format($detalle->cantidad, 2) }}</td>
                                            <td class="text-end">{{ number_format($detalle->cantidad_kgm, 2) }}</td>
                                            @if(!empty($saldos))
                                            <td class="text-end text-success fw-bold">
                                                {{ number_format($saldos[$detalle->producto_id]['devuelto'] ?? 0, 2) }}
                                            </td>
                                            <td class="text-end text-danger fw-bold">
                                                {{ number_format($saldos[$detalle->producto_id]['pendiente'] ?? 0, 2) }}
                                            </td>
                                            @endif
                                            <td class="text-end">{{ number_format($detalle->valor_unitario, 4) }}</td>
                                            <td class="text-end">{{ number_format($detalle->total, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="{{ !empty($saldos) ? 9 : 7 }}" class="text-end"><strong>Total</strong></td>
                                            <td class="text-end">
                                                {{ number_format($prestamo->total, 2) }}
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
                Usuario: <span class="me-auto fw-bold">{{ $compra->user_nombre ?? '' }}</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>