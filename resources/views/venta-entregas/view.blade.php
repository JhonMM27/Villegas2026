<!-- Modal de visualización de entrega -->
<div class="modal fade" id="modalViewEntrega" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
     aria-labelledby="modalViewEntregaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content fs-7">

            <div class="modal-header">
                <h4 class="modal-title fs-5" id="modalViewEntregaLabel">
                    Detalle de Entrega - Recibo {{ $entrega->numero_recibo }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="row">

                    <!-- Datos principales -->
                    <div class="col-lg-7">
                        <div class="border border-primary rounded p-3">
                            <div class="row mb-2">

                                <div class="col-md-4">
                                    <label class="form-label">Usuario:</label>
                                    <p class="fw-bold">{{ $entrega->user_nombre }}</p>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Fecha entrega:</label>
                                    <p class="fw-bold">
                                        {{ \Carbon\Carbon::parse($entrega->fecha_entrega)->format('Y-m-d H:i:s') }}
                                    </p>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Estado:</label>
                                    <p class="fw-bold">{{ $entrega->estado }}</p>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Recibo:</label>
                                    <p class="fw-bold">{{ $entrega->numero_recibo }}</p>
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label">Comentario:</label>
                                    <p class="fw-bold mb-0">{{ $entrega->comentario ?? '-' }}</p>
                                </div>

                                <div class="col-md-12 mt-2">
                                    @php
                                        $v = $entrega->venta;
                                        $doc = $v ? trim(($v->comprobante_tipo_codigo ?? '').' '.($v->serie ?? '').'-'.($v->correlativo ?? '')) : '';
                                    @endphp
                                    <div class="border border-danger rounded px-2 py-1">
                                        <span class="fw-bold">Venta:</span>
                                        {{ $doc !== '' ? $doc : ('ID '.$entrega->venta_id) }}
                                        @if($v && $v->fecha_venta)
                                            <span class="muted"> ({{ \Carbon\Carbon::parse($v->fecha_venta)->format('Y-m-d') }})</span>
                                        @endif
                                        <br>
                                        @if($v)
                                            <span class="fw-bold">Total venta:</span> {{ number_format((float)$v->total, 2) }}
                                        @endif
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Datos del cliente (desde la venta) -->
                    <div class="col-lg-5">
                        <div class="border border-primary rounded px-2 py-1 mt-2 mt-md-0">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label class="form-label">Cliente:</label>
                                    <p class="fw-bold">
                                        {{ $entrega->venta->cliente_nombre ?? '-' }}
                                    </p>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">ID Cliente:</label>
                                    <p class="fw-bold">
                                        {{ $entrega->venta->cliente_id ?? '-' }}
                                    </p>
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label">Documento:</label>
                                    <p class="fw-bold">
                                        {{ $entrega->venta->cliente_documento ?? '-' }}
                                    </p>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Dirección:</label>
                                    <p class="fw-bold mb-0">
                                        {{ $entrega->venta->cliente_direccion ?? '-' }}
                                    </p>
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
                                            <th class="text-center">Empaque</th>
                                            <th class="text-end">Cantidad</th>
                                            <th class="text-end">Salida kg</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalCantidad = 0;
                                            $totalKg = 0;
                                        @endphp

                                        @forelse($entrega->detalles as $index => $d)
                                            @php
                                                $totalCantidad += (float)$d->cantidad;
                                                $totalKg += (float)$d->salida_kg;
                                            @endphp
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td>{{ $d->producto_nombre }}</td>
                                                <td class="text-center">{{ $d->producto_empaque }}</td>
                                                <td class="text-end">{{ number_format((float)$d->cantidad, 2) }}</td>
                                                <td class="text-end">{{ number_format((float)$d->salida_kg, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Sin productos entregados</td>
                                            </tr>
                                        @endforelse
                                    </tbody>

                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total</strong></td>
                                            <td class="text-end"><strong>{{ number_format($totalCantidad, 2) }}</strong></td>
                                            <td class="text-end"><strong>{{ number_format($totalKg, 2) }}</strong></td>
                                        </tr>
                                    </tfoot>

                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                Usuario: <span class="me-auto fw-bold">{{ $entrega->user_nombre ?? '' }}</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>

        </div>
    </div>
</div>