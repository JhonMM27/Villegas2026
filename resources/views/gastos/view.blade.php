<!-- Modal de visualización de gasto -->
<div class="modal fade" id="modalViewGasto" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modalViewGastoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content fs-7">
            <div class="modal-header">
                <h4 class="modal-title fs-5" id="modalViewGastoLabel">
                    Detalle de Gasto {{ $gasto->numero_recibo ?? '' }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="row">
                    <!-- Datos principales -->
                    <div class="col-lg-7">
                        <div class="border border-primary rounded p-3">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">Fecha gasto:</label>
                                    <p class="fw-bold">
                                        {{ $gasto->fecha_gasto ? \Carbon\Carbon::parse($gasto->fecha_gasto)->format('Y-m-d H:i:s') : '' }}
                                    </p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">N° Recibo:</label>
                                    <p class="fw-bold">{{ $gasto->numero_recibo ?? '' }}</p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">N° Interno:</label>
                                    <p class="fw-bold">{{ $gasto->numero_interno ?? '' }}</p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Categoría:</label>
                                    <p class="fw-bold">{{ $gasto->categoriaGasto?->nombre ?? '-' }}</p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Tipo:</label>
                                    <p class="fw-bold">{{ $gasto->gastoTipo?->nombre ?? '-' }}</p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Responsable:</label>
                                    <p class="fw-bold">{{ $gasto->responsable ?? '-' }}</p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">DNI:</label>
                                    <p class="fw-bold">{{ $gasto->responsable_dni ?? '-' }}</p>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Descripción:</label>
                                    <p class="fw-bold">{{ $gasto->descripcion ?? '' }}</p>
                                </div>

                                @if($gasto->planilla_mes && $gasto->planilla_anio)
                                <div class="col-md-6">
                                    <label class="form-label">Origen Planilla:</label>
                                    <p class="fw-bold text-success">{{ \Carbon\Carbon::createFromDate($gasto->planilla_anio, $gasto->planilla_mes, 1)->format('F') }}/{{ $gasto->planilla_anio }}</p>
                                </div>
                                @endif

                                <div class="col-md-6">
                                    <label class="form-label">Monto total:</label>
                                    <p class="fw-bold">{{ number_format((float)($gasto->monto ?? 0), 2) }}</p>
                                </div>

                                <div class="col-md-6">
                                    <div class="border border-danger rounded px-2 py-1 mt-2 mt-md-0">
                                        <span class="fw-bold">Principal: </span>{{ number_format((float)($gasto->importe_p ?? 0), 2) }} <br>
                                        <span class="fw-bold">Depósito: </span>{{ number_format((float)($gasto->importe_d ?? 0), 2) }} <br>
                                        <span class="fw-bold">Consorcio: </span>{{ number_format((float)($gasto->importe_c ?? 0), 2) }} <br>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Usuario -->
                    <div class="col-lg-5">
                        <div class="border border-primary rounded p-3">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label class="form-label">Usuario:</label>
                                    <p class="fw-bold">{{ $gasto->user_nombre ?? '' }}</p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">ID Usuario:</label>
                                    <p class="fw-bold">{{ $gasto->user_id ?? '' }}</p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Documento:</label>
                                    <p class="fw-bold">—</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- row -->
            </div> <!-- modal-body -->

            <div class="modal-footer">
                Usuario: <span class="me-auto fw-bold">{{ $gasto->user_nombre ?? '' }}</span>
                <a href="{{ route('gastos.imprimir', $gasto->id) }}" target="_blank" class="btn btn-secondary">
                    <i class="bi bi-printer"></i> Imprimir
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>