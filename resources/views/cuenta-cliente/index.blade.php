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
                                Reportes Cliente
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
                                    <label class="form-label mb-0">Cliente</label>
                                    <select id="filtro_clientes" class="form-select form-select-sm" multiple>
                                        @foreach($clientes as $c)
                                        <option value="{{ $c->id }}">{{ $c->razon_social }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-lg-4">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="1"
                                            data-target="#reporteTab1" data-cliente="si" data-fecha="none" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.cliente.creditos_cobrar_cliente_todos_pdf') }}">
                                            Créditos por cobrar (Todos)
                                        </button>

                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="1"
                                            data-target="#reporteTab1" data-cliente="si" data-fecha="none" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.cliente.saldos_pdf') }}">
                                            Saldos días de antigüedad
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
                                        
                                        <button type="button" class="btn btn-secondary btn-sm btn-report"
                                                data-tab="1"
                                                data-target="#reporteTab1"
                                                data-cliente="si" data-fecha="range" data-dia="no"
                                                data-url="{{ route('cuenta.corriente.cliente.estado_cuenta_pdf') }}">
                                            Estado de cuenta
                                        </button>

                                        <button type="button" class="btn btn-info btn-sm btn-report"
                                                data-tab="1"
                                                data-target="#reporteTab1"
                                                data-cliente="si" data-fecha="range" data-dia="no"
                                                data-url="{{ route('cuenta.corriente.cliente.estado_cuenta_simplificado_pdf') }}">
                                            Estado de cuenta Simplificado
                                        </button>
                                        

                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="1"
                                            data-target="#reporteTab1" data-cliente="si" data-fecha="range" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.cliente.ventas_producto_cliente_pdf') }}">
                                            Ventas Agrupadas por producto
                                        </button>

                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="1"
                                            data-target="#reporteTab1" data-cliente="si" data-fecha="range" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.cliente.ventas_detalle_pdf') }}">
                                            Detalle de ventas
                                        </button>

                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="1"
                                            data-target="#reporteTab1" data-cliente="si" data-fecha="range" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.cliente.creditos_cobrar_detalles_pdf') }}">
                                            Detalle de créditos por cobrar
                                        </button>

                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="1"
                                            data-target="#reporteTab1" data-cliente="si" data-fecha="range" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.cliente.creditos_cobrar_cliente_fechas_pdf') }}">
                                            Créditos por cobrar (Fechas)
                                        </button>

                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="1"
                                            data-target="#reporteTab1" data-cliente="si" data-fecha="range" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.cliente.ventas_general_fechas_pdf') }}">
                                            Ventas
                                        </button>

                                    </div>
                                </div>
                            </div>
                            <div id="reporteTab1"></div>
                        </div>

                        {{-- TAB 2 --}}
                        <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="tab2-link">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-sm-6 col-lg-4">

                                </div>
                                <div class="col-12 col-lg-8">
                                    <div class="d-flex flex-wrap gap-2">                                      

                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="2"
                                            data-target="#reporteTab2" data-cliente="no" data-fecha="none" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.cliente.resumen_creditos_cobrar_pdf') }}">
                                            Cuadro de análisis crédito
                                        </button>                                        
                                    </div>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-sm-6 col-lg-4">
                                        <label class="form-label mb-0">Días</label>
                                        <input type="text" id="dias_tab2" value="30" class="form-control form-control-sm">
                                </div>
                                <div class="col-12 col-lg-4">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="2"
                                            data-target="#reporteTab2" data-cliente="no" data-fecha="none" data-dia="si"
                                            data-url="{{ route('cuenta.corriente.cliente.creditos_cobrar_dias_pdf') }}">
                                            Saldo por cobrar días de antigüedad
                                        </button>

                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="2"
                                            data-target="#reporteTab2" data-cliente="no" data-fecha="none" data-dia="si"
                                            data-url="{{ route('cuenta.corriente.cliente.creditos_cobrar_dias_agrupado_clientes_pdf') }}">
                                            Saldos agrupados por cliente
                                        </button>

                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="2"
                                            data-target="#reporteTab2" data-cliente="no" data-fecha="none" data-dia="si"
                                            data-url="{{ route('cuenta.corriente.cliente.saldos_acumuladosdias_cliente_pdf') }}">
                                            Saldos acumulados por cliente
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <label class="form-label mb-0">Fecha</label>
                                    <input type="date" id="fecha_tab2" class="form-control form-control-sm">
                                </div>

                                <div class="col-12 col-lg-4">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-secondary btn-sm btn-report" data-tab="2"
                                            data-target="#reporteTab2" data-cliente="no" data-fecha="one" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.saldo_fecha_solicitada_pdf') }}">
                                            Saldo fecha solicitada
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-sm-6 col-lg-2">
                                    <label class="form-label mb-0">Fecha Desde</label>
                                    <input type="date" id="fecha_inicio_tab2" class="form-control form-control-sm">
                                </div>

                                <div class="col-12 col-sm-6 col-lg-2">
                                    <label class="form-label mb-0">Fecha Hasta</label>
                                    <input type="date" id="fecha_fin_tab2" class="form-control form-control-sm">
                                </div>

                                <div class="col-12 col-lg-8">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-secondary btn-sm btn-report"
                                            data-tab="2" data-target="#reporteTab2" data-cliente="no"
                                            data-fecha="range" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.cliente.creditos_cobrar_fechas_pdf') }}">
                                            Relación de créditos por cobrar
                                        </button>

                                        <button type="button" class="btn btn-secondary btn-sm btn-report"
                                            data-tab="2" data-target="#reporteTab2" data-cliente="no"
                                            data-fecha="range" data-dia="no"
                                            data-url="{{ route('cuenta.corriente.cliente.saldos_acumuladosfechas_cliente_pdf') }}">
                                            Saldos acumulados por cliente
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
        $('#filtro_clientes').select2({
            width: '100%',
            placeholder: 'Buscar cliente...',
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

    function getParams(tab, requireCliente, fechaMode, requireDia) {

        const params = new URLSearchParams();

        if (requireCliente) {
            const clientes = $('#filtro_clientes').val() || [];
            if (clientes.length === 0) {
                showError('Seleccione un cliente');
                return null;
            }
            clientes.forEach(id => params.append('cliente_ids[]', id));
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
            const requireCliente = btn.dataset.cliente === 'si';
            const fechaMode = btn.dataset.fecha || 'none';
            const requireDia = btn.dataset.dia === 'si';

            const params = getParams(tab, requireCliente, fechaMode, requireDia);
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

        document.getElementById('itemCuentaCorrienteCliente')
            ?.classList.add('active');
    }

})();
</script>
@endpush