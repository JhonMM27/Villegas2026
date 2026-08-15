@extends('plantilla.app')
@section('contenido')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center">
                        <h3 class="card-title flex-grow-1">Reporte por Empleado (con Sueldo Real)</h3>
                        <a href="{{ route('reportes.planilla') }}" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h4>{{ $empleado->nombre }}</h4>
                                <p class="text-muted mb-1">DNI: {{ $empleado->dni ?? '--' }}</p>
                                <p class="text-muted mb-1">Teléfono: {{ $empleado->telefono ?? '--' }}</p>
                                <p class="text-muted mb-1">
                                    <span class="badge bg-secondary">Sueldo Base Actual: S/
                                        {{ number_format($empleado->sueldo_base, 2) }}</span>
                                    <span class="badge bg-primary">Sueldo Planilla Actual: S/
                                        {{ number_format($empleado->sueldo_planilla, 2) }}</span>
                                    <span class="badge bg-success ms-1">Sueldo Real Actual: S/
                                        {{ number_format($empleado->sueldo_real, 2) }}</span>
                                </p>
                                <p class="text-muted mb-1">Estado: <span
                                        class="badge bg-{{ $empleado->estado === 'activo' ? 'success' : 'secondary' }}">{{ ucfirst($empleado->estado) }}</span>
                                </p>
                                <p class="text-muted mb-0 small">
                                    <i class="bi bi-calendar-range me-1"></i>
                                    Período: {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} -
                                    {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body py-2">
                                        <h6 class="card-title mb-1">Sueldo Planilla del período</h6>
                                        <h4 class="mb-0">S/ {{ number_format($resumen['total_sueldo_planilla'], 2) }}
                                        </h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body py-2">
                                        <h6 class="card-title mb-1">Sueldo Real del período</h6>
                                        <h4 class="mb-0">S/ {{ number_format($resumen['total_sueldo_real'], 2) }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body py-2">
                                        <h6 class="card-title mb-1">Total Adelantos</h6>
                                        <h4 class="mb-0">S/ {{ number_format($resumen['total_adelantos'], 2) }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div
                                    class="card bg-{{ $resumen['total_pendiente'] > 0 ? 'warning' : 'secondary' }} text-white">
                                    <div class="card-body py-2">
                                        <h6 class="card-title mb-1">Total Pendiente</h6>
                                        <h4 class="mb-0">S/ {{ number_format($resumen['total_pendiente'], 2) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Detalle de Adelantos</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        @if ($adelantos->count() > 0)
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="width: 60px;">N° Int.</th>
                                                            <th style="width: 90px;">Fecha</th>
                                                            <th style="width: 100px;" class="text-end">Monto</th>
                                                            <th>Observaciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($adelantos as $a)
                                                            <tr>
                                                                <td>{{ $a->numero_interno }}</td>
                                                                <td>{{ \Carbon\Carbon::parse($a->fecha)->format('d/m/Y') }}
                                                                </td>
                                                                <td class="text-end">S/
                                                                    {{ number_format((float) $a->monto, 2) }}</td>
                                                                <td>{{ $a->observaciones ?? '-' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot class="table-light">
                                                        <tr>
                                                            <th colspan="2">TOTAL</th>
                                                            <th class="text-end">S/
                                                                {{ number_format($adelantos->sum('monto'), 2) }}</th>
                                                            <th></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        @else
                                            <div class="p-3 text-center text-muted">No hay adelantos registrados</div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Resumen de Pagos</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <tbody>
                                                    <tr>
                                                        <td>Cantidad de Períodos</td>
                                                        <td class="text-end fw-bold">{{ $pagos->count() }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Total Sueldo Base (Disponible)</td>
                                                        <td class="text-end fw-bold">S/
                                                            {{ number_format($resumen['total_sueldo_base'], 2) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Total Horas Extras</td>
                                                        <td class="text-end fw-bold">S/
                                                            {{ number_format($resumen['total_horas_extras'], 2) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Total Días Faltantes</td>
                                                        <td class="text-end fw-bold text-danger">
                                                            {{ number_format((float) $resumen['total_dias_faltas'], 2) }} -
                                                            S/
                                                            {{ number_format((float) $resumen['total_descuento_faltas'], 2) }}
                                                        </td>
                                                    </tr>
                                                    @if (($resumen['total_cts_planilla'] ?? 0) > 0)
                                                        <tr>
                                                            <td>CTS (Depósito)</td>
                                                            <td class="text-end fw-bold">S/
                                                                {{ number_format($resumen['total_cts_planilla'], 2) }}</td>
                                                        </tr>
                                                    @endif
                                                    @if (($resumen['total_cts_sueldo_real'] ?? 0) > 0)
                                                        <tr>
                                                            <td>CTS</td>
                                                            <td class="text-end fw-bold">S/
                                                                {{ number_format($resumen['total_cts_sueldo_real'], 2) }}
                                                            </td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <td>Total General a Pagar</td>
                                                        <td class="text-end fw-bold">S/
                                                            {{ number_format($resumen['total_general'], 2) }}</td>
                                                    </tr>
                                                    <tr class="table-success">
                                                        <td>Total Pagado</td>
                                                        <td class="text-end fw-bold">S/
                                                            {{ number_format($resumen['total_pagado'], 2) }}</td>
                                                    </tr>
                                                    <tr class="table-warning">
                                                        <td>Total Pendiente</td>
                                                        <td class="text-end fw-bold">S/
                                                            {{ number_format($resumen['total_pendiente'], 2) }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Pagos por Período</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Período</th>
                                                <th class="text-end">Sueldo Planilla</th>
                                                <th class="text-end">Sueldo Real</th>
                                                <th class="text-end">Sueldo Base</th>
                                                <th class="text-end">H. Extras</th>
                                                <th class="text-end">Días Faltas</th>
                                                <th class="text-end">Desc. Faltas</th>
                                                <th class="text-end">Total Pagar</th>
                                                <th class="text-end">Pagado</th>
                                                <th class="text-end">Pendiente</th>
                                                <th>Estado</th>
                                                <th>Fecha Pago</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($pagos as $pago)
                                                @php
                                                    $pagado = (float) $pago->total_pagar;
                                                    $pendiente =
                                                        $pago->estado === 'pendiente' ? (float) $pago->total_pagar : 0;
                                                @endphp
                                                <tr>
                                                    <td>{{ $pago->id }}</td>
                                                    <td>{{ str_pad($pago->mes, 2, '0', STR_PAD_LEFT) }}/{{ $pago->anio }}
                                                    </td>
                                                    @if($pago->sueldo_historico_disponible)
                                                        <td class="text-end">S/ {{ number_format($pago->sueldo_planilla_historico_reporte, 2) }}</td>
                                                        <td class="text-end">S/ {{ number_format($pago->sueldo_real_historico_reporte, 2) }}</td>
                                                        <td class="text-end">S/ {{ number_format($pago->sueldo_base_historico, 2) }}</td>
                                                    @else
                                                        <td colspan="3" class="text-center text-muted">Histórico no disponible</td>
                                                    @endif
                                                    <td class="text-end">S/
                                                        {{ number_format((float) $pago->horas_extras, 2) }}</td>
                                                    <td class="text-end">
                                                        {{ number_format((float) ($pago->dias_faltados ?? 0), 2) }}
                                                    </td>
                                                    <td class="text-end text-danger">S/
                                                        {{ number_format((float) ($pago->descuento_faltas ?? 0), 2) }}</td>
                                                    <td class="text-end">S/
                                                        {{ number_format((float) $pago->total_pagar, 2) }}</td>
                                                    <td class="text-end">
                                                        {{ $pago->estado === 'pagado' ? 'S/ ' . number_format((float) $pago->total_pagar, 2) : '-' }}
                                                    </td>
                                                    <td class="text-end">
                                                        {{ $pendiente > 0 ? 'S/ ' . number_format($pendiente, 2) : '-' }}
                                                    </td>
                                                    <td>
                                                        @if ($pago->estado === 'pagado')
                                                            <span class="badge bg-success">Pagado</span>
                                                        @else
                                                            <span class="badge bg-warning">Pendiente</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $pago->fecha_pago ? \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') : '-' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="13" class="text-center">No hay pagos registrados para
                                                        este empleado en el período seleccionado</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="2" class="text-end">TOTALES:</th>
                                                <th class="text-end">S/ {{ number_format($resumen['total_sueldo_planilla'], 2) }}</th>
                                                <th class="text-end">S/ {{ number_format($resumen['total_sueldo_real'], 2) }}</th>
                                                <th class="text-end">S/
                                                    {{ number_format($resumen['total_sueldo_base_historico'], 2) }}</th>
                                                <th class="text-end">S/
                                                    {{ number_format($resumen['total_horas_extras'], 2) }}</th>
                                                <th></th>
                                                <th class="text-end">S/
                                                    {{ number_format($pagos->sum('descuento_faltas'), 2) }}
                                                </th>
                                                <th class="text-end">S/ {{ number_format($resumen['total_general'], 2) }}
                                                </th>
                                                <th class="text-end">S/ {{ number_format($resumen['total_pagado'], 2) }}
                                                </th>
                                                <th class="text-end">S/
                                                    {{ number_format($resumen['total_pendiente'], 2) }}
                                                </th>
                                                <th colspan="2"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.getElementById('mnuPlanilla').classList.add('menu-open');
        document.getElementById('itemReportes').classList.add('active');
    </script>
@endpush
