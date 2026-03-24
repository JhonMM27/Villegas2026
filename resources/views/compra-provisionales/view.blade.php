<!-- Modal de visualización de provisional -->
<div class="modal fade" id="modalViewProvisional" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalViewProvisionalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content fs-7">
            <div class="modal-header">
                <h4 class="modal-title fs-5" id="modalTitle">
                    Detalle de Provisional Compra - Recibo {{ $provisional->numero_recibo }}
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
                                    <p class="fw-bold">{{ $provisional->user_nombre }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha:</label>
                                    <p class="fw-bold">{{ \Carbon\Carbon::parse($provisional->fecha_provisional)->format('Y-m-d H:i:s') }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tipo:</label>
                                    <p class="fw-bold">{{ $provisional->tipo }}</p>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Recibo:</label>
                                    <p class="fw-bold">{{ $provisional->numero_recibo }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Interno:</label>
                                    <p class="fw-bold">{{ $provisional->numero_interno }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Monto:</label>
                                    <p class="fw-bold">{{ number_format((float)$provisional->monto, 2) }}</p>
                                </div>

                                <div class="col-md-6">
                                    <div class="border border-danger rounded px-2 py-1 mt-2 mt-md-0">
                                        <span class="fw-bold">Principal: </span>{{ number_format((float)$provisional->importe_p, 2) }} <br>
                                        <span class="fw-bold">Depósito: </span>{{ number_format((float)$provisional->importe_d, 2) }} <br>
                                        <span class="fw-bold">Consorcio: </span>{{ number_format((float)$provisional->importe_c, 2) }} <br>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Datos del cliente -->
                    <div class="col-lg-5">
                        <div class="border border-primary rounded px-2 py-1 mt-2 mt-md-0">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label class="form-label">Proveedor:</label>
                                    <p class="fw-bold">{{ $provisional->proveedor_nombre }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ID Proveedor:</label>
                                    <p class="fw-bold">{{ $provisional->proveedor_id }}</p>
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
                                            <th>Documento</th>
                                            <th>Compra ID</th>
                                            <th class="text-end">Monto</th>
                                            <th>Comentario</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $totalAplicado = 0; @endphp

                                        @forelse($provisional->detalles as $index => $d)
                                            @php $totalAplicado += (float)$d->monto; @endphp
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td class="text-center">
                                                    {{ $d->comprobante_tipo_codigo }}-{{ $d->serie }}-{{ $d->correlativo }}
                                                </td>
                                                <td class="text-center">{{ $d->compra_id }}</td>
                                                <td class="text-end">{{ number_format((float)$d->monto, 2) }}</td>
                                                <td>{{ $d->comentario }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Sin detalles (adelanto / no aplicado)</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        @php
                                            $montoCab = (float)$provisional->monto;
                                            $saldoFavor = $montoCab - $totalAplicado;
                                        @endphp
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total aplicado</strong></td>
                                            <td class="text-end"><strong>{{ number_format($totalAplicado, 2) }}</strong></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Saldo a favor</strong></td>
                                            <td class="text-end">
                                                <strong>{{ number_format($saldoFavor, 2) }}</strong>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                Usuario: <span class="me-auto fw-bold">{{ $provisional->user_nombre ?? '' }}</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>

        </div>
    </div>
</div>