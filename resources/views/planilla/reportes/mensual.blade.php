@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Reporte Mensual de Planilla</h3>
                    <a href="{{ route('reportes.planilla') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h4>{{ $nombreMes }} {{ $anio }}</h4>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="bi bi-people"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Empleados</span>
                                    <span class="info-box-number">{{ $resumen['cantidad_empleados'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="bi bi-cash"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total a Pagar</span>
                                    <span class="info-box-number">S/ {{ number_format($resumen['total_pagar'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="bi bi-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Horas Extras</span>
                                    <span class="info-box-number">S/ {{ number_format($resumen['total_horas_extras'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="bi bi-check-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Pendientes</span>
                                    <span class="info-box-number">{{ $resumen['pendientes'] }} / {{ $resumen['cantidad_empleados'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Empleado</th>
                                    <th>Sueldo Base</th>
                                    <th>Horas Extras</th>
                                    <th>Total Pagar</th>
                                    <th>Estado</th>
                                    <th>Fecha Pago</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pagos as $pago)
                                <tr>
                                    <td>{{ $pago->id }}</td>
                                    <td>{{ $pago->empleado->nombre }}</td>
                                    <td>S/ {{ number_format($pago->sueldo_base, 2) }}</td>
                                    <td>S/ {{ number_format($pago->horas_extras, 2) }}</td>
                                    <td>S/ {{ number_format($pago->total_pagar, 2) }}</td>
                                    <td>
                                        @if($pago->estado === 'pagado')
                                            <span class="badge bg-primary">Pagado</span>
                                        @else
                                            <span class="badge bg-warning">Pendiente</span>
                                        @endif
                                    </td>
                                    <td>{{ $pago->fecha_pago ? $pago->fecha_pago->format('d/m/Y') : '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">No hay pagos registrados para este periodo</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2" class="text-end">TOTALES:</th>
                                    <th>S/ {{ number_format($pagos->sum('sueldo_base'), 2) }}</th>
                                    <th>S/ {{ number_format($pagos->sum('horas_extras'), 2) }}</th>
                                    <th>S/ {{ number_format($pagos->sum('total_pagar'), 2) }}</th>
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
@endsection
@push('scripts')
document.getElementById('mnuPlanilla').classList.add('menu-open');
document.getElementById('itemReportes').classList.add('active');
</script>
@endpush
