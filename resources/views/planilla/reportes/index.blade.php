@extends('plantilla.app')

@push('estilos')
<link href="{{ asset('css/select2.min.css') }}" rel="stylesheet">
<style>
.loading-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, .75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}
.report-container {
    position: relative;
    min-height: 120px;
}
</style>
@endpush

@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-pills" id="reportTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="tab1-link" data-bs-toggle="tab" href="#tab1" role="tab">
                                Reportes por Empleado
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="tab2-link" data-bs-toggle="tab" href="#tab2" role="tab">
                                Reportes Generales
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body report-container">
                    <div id="loadingOverlay" class="loading-overlay d-none">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status"></div>
                            <div class="mt-2 fw-bold">Cargando...</div>
                        </div>
                    </div>

                    <div class="tab-content">
                        {{-- TAB 1: Reportes por Empleado --}}
                        <div class="tab-pane fade show active" id="tab1" role="tabpanel">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-lg-4">
                                    <label class="form-label mb-0">Empleado</label>
                                    <select id="filtro_empleado" class="form-select form-select-sm" style="width: 100%;">
                                        <option value="">-- Seleccionar empleado --</option>
                                        @foreach($empleados as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->nombre }} - {{ $emp->dni }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-lg-3">
                                    <label class="form-label mb-0">Fecha Inicio</label>
                                    <input type="date" id="fecha_inicio" class="form-control form-control-sm" value="{{ date('Y-01-01') }}">
                                </div>
                                <div class="col-12 col-lg-3">
                                    <label class="form-label mb-0">Fecha Fin</label>
                                    <input type="date" id="fecha_fin" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-12 col-lg-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnHoyTab1">
                                        <i class="bi bi-calendar3"></i> Hoy
                                    </button>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-lg-4">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-primary btn-sm btn-report" 
                                            data-tab="1"
                                            data-url="{{ route('reportes.planilla.empleado_pdf') }}">
                                            <i class="bi bi-file-earmark-pdf"></i> Ver Reporte PDF
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm btn-report" 
                                            data-tab="1"
                                            data-skip-employee="true"
                                            data-url="{{ route('reportes.planilla.inasistencias') }}">
                                            <i class="bi bi-exclamation-triangle"></i> Inasistencias
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- TAB 2: Reportes Generales --}}
                        <div class="tab-pane fade" id="tab2" role="tabpanel">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-lg-2">
                                    <label class="form-label mb-0">Fecha Inicio</label>
                                    <input type="date" id="fecha_inicio_general" class="form-control form-control-sm" value="{{ date('Y-01-01') }}">
                                </div>
                                <div class="col-12 col-lg-2">
                                    <label class="form-label mb-0">Fecha Fin</label>
                                    <input type="date" id="fecha_fin_general" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-12 col-lg-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnHoyTab2">
                                        <i class="bi bi-calendar3"></i> Hoy
                                    </button>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-lg-3">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-info btn-sm btn-report" 
                                            data-tab="2"
                                            data-url="{{ route('reportes.planilla.adelantos') }}">
                                            <i class="bi bi-file-earmark-bar-graph"></i> Adelantos
                                        </button>
                                        <button type="button" class="btn btn-info btn-sm btn-report" 
                                            data-tab="2"
                                            data-url="{{ route('reportes.planilla.prestamos') }}">
                                            <i class="bi bi-file-earmark-bar-graph"></i> Préstamos
                                        </button>
                                        <button type="button" class="btn btn-info btn-sm btn-report" 
                                            data-tab="2"
                                            data-url="{{ route('reportes.planilla.pagos_pendientes') }}">
                                            <i class="bi bi-file-earmark-bar-graph"></i> Pagos Pendientes
                                        </button>
                                    </div>
                                </div>
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
<script src="{{ asset('js/select2.min.js') }}"></script>
<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        initSelect2();
        bindReportButtons();
        activateMenu();
    });

    function initSelect2() {
        $('#filtro_empleado').select2({
            width: '100%',
            placeholder: '-- Seleccionar empleado --',
            allowClear: true,
            language: {
                noResults: function() {
                    return "No se encontraron empleados";
                }
            }
        });
    }

    function getParams(tab, skipEmployeeValidation = false) {
        const params = new URLSearchParams();

        if (tab === 1) {
            const empleadoId = $('#filtro_empleado').val();
            if (!skipEmployeeValidation && !empleadoId) {
                showError('Seleccione un empleado');
                return null;
            }
            if (empleadoId) {
                params.append('empleado_id', empleadoId);
            }

            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;
            if (!fechaInicio || !fechaFin) {
                showError('Seleccione un rango de fechas');
                return null;
            }
            params.append('fecha_inicio', fechaInicio);
            params.append('fecha_fin', fechaFin);
        } else {
            const fechaInicio = document.getElementById('fecha_inicio_general').value;
            const fechaFin = document.getElementById('fecha_fin_general').value;
            if (!fechaInicio || !fechaFin) {
                showError('Seleccione un rango de fechas');
                return null;
            }
            params.append('fecha_inicio', fechaInicio);
            params.append('fecha_fin', fechaFin);
        }

        return params;
    }

    function bindReportButtons() {
        document.getElementById('btnHoyTab1').addEventListener('click', function() {
            const hoy = new Date().toISOString().split('T')[0];
            document.getElementById('fecha_inicio').value = hoy;
            document.getElementById('fecha_fin').value = hoy;
        });

        document.getElementById('btnHoyTab2').addEventListener('click', function() {
            const hoy = new Date().toISOString().split('T')[0];
            document.getElementById('fecha_inicio_general').value = hoy;
            document.getElementById('fecha_fin_general').value = hoy;
        });

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-report');
            if (!btn) return;

            e.preventDefault();
            const url = btn.dataset.url;
            const tab = parseInt(btn.dataset.tab, 10);
            const skipEmployeeValidation = btn.dataset.skipEmployee === 'true';

            const params = getParams(tab, skipEmployeeValidation);
            if (!params) return;

            const finalUrl = url + '?' + params.toString();
            window.open(finalUrl, '_blank');
        });
    }

    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }

    function activateMenu(){
        document.getElementById('mnuPlanilla')?.classList.add('menu-open');
        document.getElementById('itemReportes')?.classList.add('active');
    }
})();
</script>
@endpush