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
                            <a class="nav-link active" id="tab1-link" data-bs-toggle="tab" href="#tab1" role="tab"
                                aria-controls="tab1" aria-selected="true">
                                Reportes Proveedor
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="tab2-link" data-bs-toggle="tab" href="#tab2" role="tab"
                                aria-controls="tab2" aria-selected="false">
                                Reportes General
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
                        {{-- TAB 1 --}}
                        <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="tab1-link">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-lg-4">
                                    <label class="form-label mb-0">Proveedor</label>
                                    <select id="filtro_proveedores" class="form-select form-select-sm" multiple>
                                        @foreach($proveedores as $p)
                                        <option value="{{ $p->id }}">{{ $p->razon_social }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-lg-4">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-info btn-sm btn-report"
                                                data-tab="1"
                                                data-target="#reporteTab1"
                                                data-proveedor="si" data-fecha="range" data-dia="no"
                                                data-url="{{ route('cuenta.corriente.proveedor.estado_cuenta_simplificado_pdf') }}">
                                            Estado de Cuenta
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="1"
                                            data-target="#reporteTab1" data-proveedor="si" data-fecha="none" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.proveedor.creditos_pagar_proveedor_todos_pdf') }}">
                                            Créditos por Pagar (Todos)
                                        </button>                                        
                                    </div>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-sm-6 col-lg-2">
                                    <label class="form-label mb-0">Desde</label>
                                    <input type="date" id="fecha_inicio_tab1" class="form-control form-control-sm">
                                </div>

                                <div class="col-12 col-sm-6 col-lg-2">
                                    <label class="form-label mb-0">Hasta</label>
                                    <input type="date" id="fecha_fin_tab1" class="form-control form-control-sm">
                                </div>

                                <div class="col-12 col-lg-4">
                                    <div class="d-flex flex-wrap gap-2">
                                        {{-- Cada botón llama a una ruta (HTML parcial) --}}
                                        
                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="1"
                                            data-target="#reporteTab1" data-proveedor="si" data-fecha="range" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.proveedor.creditos_pagar_proveedor_fechas_pdf') }}">
                                            Créditos por pagar (Fechas)
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="1"
                                            data-target="#reporteTab1" data-proveedor="si" data-fecha="range" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.proveedor.creditos_pagar_detalles_pdf') }}">
                                            Detalle de créditos por pagar
                                        </button>
                                        {{-- 
                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="1"
                                            data-target="#reporteTab1" data-proveedor="si" data-fecha="range" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.proveedor.compras_producto_proveedor_pdf') }}">
                                            Compras Acumuladas por producto
                                        </button>
                                        --}}
                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="1"
                                            data-target="#reporteTab1" data-proveedor="si" data-fecha="range" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.proveedor.compras_producto_proveedor_pdf') }}">
                                            Compras Agrupadas por producto
                                        </button>                                        
                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="1"
                                            data-target="#reporteTab1" data-proveedor="si" data-fecha="range" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.proveedor.compras_general_fechas_pdf') }}">
                                            Compras a proveedor
                                        </button>

                                    </div>
                                </div>
                            </div>
                            <div id="reporteTab1"></div>
                        </div>

                        {{-- TAB 2 --}}
                        <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="tab2-link">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-sm-6 col-lg-2">

                                </div>
                                <div class="col-12 col-lg-8">
                                    <div class="d-flex flex-wrap gap-2">
                                    </div>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-sm-6 col-lg-2">
                                    <label class="form-label mb-0">Fecha</label>
                                    <input type="date" id="fecha_inicio_tab2" class="form-control form-control-sm">
                                </div>
                                <div class="col-12 col-sm-6 col-lg-2">
                                    <label class="form-label mb-0">Fecha Hasta</label>
                                    <input type="date" id="fecha_fin_tab2" class="form-control form-control-sm">
                                </div>

                                <div class="col-12 col-lg-8">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="2"
                                            data-target="#reporteTab2" data-proveedor="no" data-fecha="range" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.proveedor.creditos_pagar_todos_pdf') }}">
                                            Relación de créditos por pagar
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="2"
                                            data-target="#reporteTab2" data-proveedor="no" data-fecha="range" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.proveedor.saldo_fechas_solicitada_pdf') }}">
                                            Créditos por pagar
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="2"
                                            data-target="#reporteTab2" data-proveedor="no" data-fecha="range" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.proveedor.creditos_pagar_detalles_todos_pdf') }}">
                                            Detalle de Créditos por pagar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-sm-6 col-lg-2">
                                    <label class="form-label mb-0">Días Antigüedad</label>
                                    <input type="number" id="dias_tab2" class="form-control form-control-sm" value="30">
                                </div>
                                <div class="col-12 col-lg-8">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="2"
                                            data-target="#reporteTab2" data-proveedor="no" data-fecha="none" data-dia="si"
                                            data-url="{{ route('cuenta.corriente.proveedor.creditos_pagar_dias_pdf') }}">
                                            Créditos > X días
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="2"
                                            data-target="#reporteTab2" data-proveedor="no" data-fecha="none" data-dia="si"
                                            data-url="{{ route('cuenta.corriente.proveedor.creditos_pagar_dias_agrupado_proveedors_pdf') }}">
                                             Créditos > X días (Agrupado)
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="2"
                                            data-target="#reporteTab2" data-proveedor="no" data-fecha="none" data-dia="si"
                                            data-url="{{ route('cuenta.corriente.proveedor.saldos_acumuladosdias_proveedor_pdf') }}">
                                             Saldos Acumulados > X días
                                        </button>
                                    </div>
                                </div>
                             </div>

                            <div id="reporteTab2"></div>
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
        setDefaultDates();
        bindReportButtons();
        activateMenu();
    });

    function initSelect2() {
        $('#filtro_proveedores').select2({
            width: '100%',
            placeholder: 'Buscar proveedor...',
            closeOnSelect: false
        });
    }

    function setDefaultDates() {
        const today = new Date().toISOString().slice(0, 10);
        ['fecha_inicio_tab1', 'fecha_fin_tab1',
            'fecha_inicio_tab2', 'fecha_fin_tab2',
            'fecha_tab2'
        ].forEach(id => {
            const el = document.getElementById(id);
            if (el && !el.value) el.value = today;
        });
    }

    function getParams(tab, requireProveedor, fechaMode, requireDia) {

        const params = new URLSearchParams();

        if (requireProveedor) {
            const proveedores = $('#filtro_proveedores').val() || [];
            if (proveedores.length === 0) {
                showError('Seleccione un proveedor');
                return null;
            }
            proveedores.forEach(id => params.append('proveedor_ids[]', id));
        }

        // DÍAS (solo si el botón lo requiere)
        if (requireDia) {
        const dias = (document.getElementById('dias_tab2')?.value || '').trim();

        if (!dias) {
            showError('Ingrese los días');
            return null;
        }

        const n = Number(dias);
        if (!Number.isInteger(n) || n <= 0) {
            showError('Días inválidos (debe ser un entero > 0)');
            return null;
        }

        // envías como "dias" (ajusta el nombre si tu backend espera otro)
        params.append('dias', String(n));
        }

        fechaMode = fechaMode.toLowerCase();

        if (fechaMode === 'none') return params;

        if (fechaMode === 'one') {
            const fecha = document.getElementById('fecha_tab2')?.value;
            if (!fecha) {
                showError('Seleccione la fecha');
                return null;
            }
            params.append('fecha', fecha);
            return params;
        }

        if (fechaMode === 'range') {
            const fi = document.getElementById(tab === 1 ? 'fecha_inicio_tab1' : 'fecha_inicio_tab2')?.value;
            const ff = document.getElementById(tab === 1 ? 'fecha_fin_tab1' : 'fecha_fin_tab2')?.value;

            if (!fi || !ff) {
                showError('Seleccione fecha inicio y fin');
                return null;
            }
            if (ff < fi) {
                showError('Fecha fin inválida');
                return null;
            }

            params.append('fecha_inicio', fi);
            params.append('fecha_fin', ff);
            return params;
        }

        return params;
    }

    function bindReportButtons() {
        document.addEventListener('click', function(e) {

            const btn = e.target.closest('.btn-report');
            if (!btn) return;

            e.preventDefault();

            const url = btn.dataset.url;
            const tab = parseInt(btn.dataset.tab, 10);
            const requireProveedor = btn.dataset.proveedor === 'si';
            const fechaMode = btn.dataset.fecha || 'none';
            const requireDia = btn.dataset.dia === 'si';

            const params = getParams(tab, requireProveedor, fechaMode, requireDia);
            if (!params) return;

            const finalUrl = params.toString() ?
                url + '?' + params.toString() :
                url;

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

        document.getElementById('mnuCuentasCorriente')
            ?.classList.add('menu-open');

        document.getElementById('itemCuentaCorrienteProveedor')
            ?.classList.add('active');
    }
})();
</script>
@endpush