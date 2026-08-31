@extends('plantilla.app')

@push('estilos')
<style>
    .audit-metric {
        height: 100%;
        padding: .75rem 1rem;
        border: 1px solid var(--bs-border-color);
        border-radius: .375rem;
        background: var(--bs-tertiary-bg);
    }
    .audit-metric-icon {
        display: grid;
        place-items: center;
        width: 2.4rem;
        height: 2.4rem;
        border-radius: .375rem;
        font-size: 1rem;
    }
    .audit-action-badge { display: inline-flex; padding: .25rem .55rem; border-radius: 2rem; font-size: .7rem; font-weight: 800; text-transform: uppercase; }
    .audit-badge-cancel { color: #991b1b; background: #fee2e2; }
    .audit-badge-edit { color: #854d0e; background: #fef3c7; }
</style>
@endpush

@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title flex-grow-1">Auditoría del sistema</h3>
                    <span class="badge text-bg-secondary"><i class="bi bi-lock-fill me-1"></i>Registros protegidos</span>
                </div>
                <div class="card-body">
                    <p class="text-body-secondary small mb-3">Historial inmutable de anulaciones y rectificaciones realizadas por los usuarios.</p>

                    <div class="row g-2 mb-3">
                        @foreach ([
                            ['Total de eventos', $metricas['total'], 'bi-database-fill', '#e6f6fb', '#0785a5'],
                            ['Eventos de hoy', $metricas['hoy'], 'bi-calendar-check-fill', '#eaf8ef', '#269555'],
                            ['Anulaciones de hoy', $metricas['anulaciones_hoy'], 'bi-x-octagon-fill', '#fff0f0', '#c24141'],
                            ['Rectificaciones de hoy', $metricas['rectificaciones_hoy'], 'bi-pencil-square', '#fff5e5', '#d97706'],
                        ] as [$titulo, $valor, $icono, $fondo, $color])
                            <div class="col-12 col-sm-6 col-xl-3">
                                <div class="audit-metric d-flex align-items-center gap-2">
                                    <div class="audit-metric-icon" style="background: {{ $fondo }}; color: {{ $color }}"><i class="bi {{ $icono }}"></i></div>
                                    <div><div class="text-body-secondary small">{{ $titulo }}</div><div class="fs-5 fw-bold">{{ number_format($valor) }}</div></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <form id="auditoriaFiltros" class="row g-2 align-items-end mb-3">
                        <div class="col-12 col-lg-3">
                            <label class="form-label mb-0">Buscar</label>
                            <input type="search" class="form-control form-control-sm" id="auditBuscar" placeholder="Usuario, motivo o documento">
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label mb-0">Usuario</label>
                            <select class="form-select form-select-sm" id="auditUsuario"><option value="">Todos</option>@foreach($usuarios as $usuario)<option value="{{ $usuario->name }}">{{ $usuario->name }}</option>@endforeach</select>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label mb-0">Acción</label>
                            <select class="form-select form-select-sm" id="auditAccion"><option value="">Todas</option><option value="anulacion">Anulación</option><option value="rectificacion">Rectificación</option></select>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <label class="form-label mb-0">Módulo</label>
                            <select class="form-select form-select-sm" id="auditModulo"><option value="">Todos</option>@foreach($modulos as $codigo => $nombre)<option value="{{ $codigo }}">{{ $nombre }}</option>@endforeach</select>
                        </div>
                        <div class="w-100"></div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label mb-0">Desde</label>
                            <input type="text" class="form-control form-control-sm date-picker" id="auditDesde" placeholder="Fecha inicio" autocomplete="off">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label mb-0">Hasta</label>
                            <input type="text" class="form-control form-control-sm date-picker" id="auditHasta" placeholder="Fecha fin" autocomplete="off">
                        </div>
                        <div class="col-6 col-lg-2 d-grid">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                        </div>
                        <div class="col-6 col-lg-2 d-grid">
                            <button type="button" id="auditHoy" class="btn btn-outline-secondary btn-sm"><i class="bi bi-calendar3"></i> Hoy</button>
                        </div>
                        <div class="col-6 col-lg-2 d-grid">
                            <button type="button" id="auditLimpiar" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Limpiar</button>
                        </div>
                    </form>

                    <hr class="my-3">

                    <div class="table-responsive">
                        <table id="auditoriaTable" class="table table-striped table-hover table-sm align-middle w-100">
                            <thead><tr><th>Fecha</th><th>Hora</th><th>Usuario</th><th>Acción</th><th>Módulo</th><th>Registro afectado</th><th>Cambio realizado</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer clearfix"></div>
            </div>
        </div>
    </div>
</div>

@include('components.rectificacion-historial-modal')
@endsection

@push('scripts')
<script>window.auditoriaConfig = @json(['dataUrl' => route('auditoria.data'), 'showUrl' => url('/auditoria'), 'pdfUrl' => url('/auditoria')]);</script>
<script src="{{ asset('js/auditoria.js') }}"></script>
<script>document.getElementById('itemAuditoria')?.classList.add('active');</script>
@endpush
