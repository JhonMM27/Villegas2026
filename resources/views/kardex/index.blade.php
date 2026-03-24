@extends('plantilla.app')
@push('estilos')
<link href="{{ asset('css/select2.min.css') }}" rel="stylesheet">
@endpush
@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link active" href="#tab1" data-bs-toggle="tab">Stock General - Fecha</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab2" data-bs-toggle="tab">Kardex</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab3" data-bs-toggle="tab">Movimientos</a></li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div id="loadingOverlay" class="loading-overlay d-none">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status"></div>
                                <div class="mt-2 fw-bold">Cargando...</div>
                            </div>
                        </div>
                        <div class="tab-pane fade show active" id="tab1">
                            <div class="row g-2 align-items-end mb-2">
                                <!-- Desde -->
                                <div class="col-12 col-sm-6 col-lg-2">
                                    <label class="form-label mb-0">Hasta</label>
                                    <input type="date" id="fecha_inicio" class="form-control form-control-sm">
                                </div>
                                <!-- Botones -->
                                <div class="col-12 col-lg-auto">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button id="btnFiltrar" class="btn btn-primary btn-sm">Filtrar</button>
                                        <button id="btnExportar" class="btn btn-success btn-sm">Exportar Excel</button>
                                        <a href="#" id="btnPdf" target="_blank" class="btn btn-danger btn-sm">PDF</a>
                                    </div>
                                </div>
                            </div>

                            <div id="reporteTab1">
                                @include('kardex.reportes.stock_general', ['reportes' => collect()])
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab2">
                            <div class="row g-2 align-items-end mb-2">

                                <!-- Desde -->
                                <div class="col-12 col-sm-6 col-lg-2">
                                    <label class="form-label mb-0">Desde</label>
                                    <input type="date" id="fecha_inicio_fechas" class="form-control form-control-sm">
                                </div>

                                <!-- Hasta -->
                                <div class="col-12 col-sm-6 col-lg-2">
                                    <label class="form-label mb-0">Hasta</label>
                                    <input type="date" id="fecha_fin_fechas" class="form-control form-control-sm">
                                </div>

                                <!-- Producto -->
                                <div class="col-12 col-lg-4">
                                    <label class="form-label mb-0">Producto</label>
                                    <select id="filtro_productos" class="form-select form-select-sm" multiple>
                                        @foreach($productos as $p)
                                            <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Operación -->
                                <div class="col-12 col-lg-3">
                                    <label class="form-label mb-0">Operación</label>
                                    <select id="filtro_operacion" class="form-select form-select-sm" multiple>
                                        <option value="COMPRA">COMPRA</option>
                                        <option value="VENTA">VENTA</option>
                                        <option value="PREPARADA">PREPARADA</option>
                                        <option value="PREPARADA_NUCLEO">PREPARADA NUCLEO</option>
                                        <option value="PRESTAMO">PRESTAMO</option>
                                    </select>
                                </div>

                                <!-- Botones -->
                                <!-- 👉 En md ocupa toda la fila, en lg se pone a la derecha -->
                                <div class="col-12 col-lg-1">
                                    <div class="d-flex gap-2 flex-wrap justify-content-start justify-content-lg-end">
                                        <button id="btnFiltrarKardex" class="btn btn-primary btn-sm">
                                            <i class="bi bi-funnel"></i> Filtrar
                                        </button>

                                        <button id="btnExportarKardex" class="btn btn-success btn-sm">
                                            <i class="bi bi-file-earmark-excel"></i>
                                        </button>

                                        <a href="#" id="btnPdfKardex" target="_blank" class="btn btn-danger btn-sm">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    </div>
                                </div>

                            </div>

                            <div id="reporteTab2">
                                @include('kardex.reportes.fechas_productos', [
                                    'reportes' => collect(),
                                    'fechaInicio' => null,
                                    'fechaFin' => null,
                                    'productos' => [],
                                    'lineas' => [],
                                    'operaciones' => [],
                                ])
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab3">
                            <div class="row g-2 align-items-end mb-2">
                                <!-- Desde -->
                                <div class="col-12 col-sm-6 col-lg-2">
                                    <label class="form-label mb-0">Desde</label>
                                    <input type="date" id="mov_fecha_inicio" class="form-control form-control-sm">
                                </div>
                                <!-- Hasta -->
                                <div class="col-12 col-sm-6 col-lg-2">
                                    <label class="form-label mb-0">Hasta</label>
                                    <input type="date" id="mov_fecha_fin" class="form-control form-control-sm">
                                </div>
                                <!-- Producto -->
                                <div class="col-12 col-lg-4">
                                    <label class="form-label mb-0">Producto</label>
                                    <select id="mov_filtro_productos" class="form-select form-select-sm" multiple>
                                        @foreach($productos as $p)
                                            <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <!-- Tipo -->
                                <div class="col-12 col-lg-3">
                                    <label class="form-label mb-0">Tipo de Movimiento</label>
                                    <select id="mov_filtro_tipos" class="form-select form-select-sm" multiple>
                                        <option value="COMPRA">COMPRA</option>
                                        <option value="VENTA">VENTA</option>
                                        <option value="PREPARADA_SALIDA">PREPARADA SALIDA</option>
                                        <option value="PREPARADA_INGRESO">PREPARADA INGRESO</option>
                                        <option value="PRESTAMO_SALIDA">PRESTAMO SALIDA</option>
                                        <option value="PRESTAMO_INGRESO">PRESTAMO INGRESO</option>
                                        <option value="ANULACION_COMPRA">ANULACIÓN COMPRA</option>
                                        <option value="ANULACION_VENTA">ANULACIÓN VENTA</option>
                                        <option value="ANULACION_PREPARADA">ANULACIÓN PREPARADA</option>
                                        <option value="ANULACION_PRESTAMO">ANULACIÓN PRÉSTAMO</option>
                                    </select>
                                </div>
                                <!-- Botones -->
                                <div class="col-12 col-lg-1">
                                    <div class="d-flex gap-2 flex-wrap justify-content-start justify-content-lg-end">
                                        <button id="btnFiltrarMov" class="btn btn-primary btn-sm" title="Filtrar">
                                            <i class="bi bi-funnel"></i>
                                        </button>
                                        <button id="btnExportarMov" class="btn btn-success btn-sm" title="Exportar Excel">
                                            <i class="bi bi-file-earmark-excel"></i>
                                        </button>
                                        <a href="#" id="btnPdfMov" target="_blank" class="btn btn-danger btn-sm" title="Imprimir PDF">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div id="reporteTab3">
                                <div class="alert alert-info">Seleccione los filtros y haga clic en Filtrar para ver los movimientos.</div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>

            <!-- /.card -->
        </div>
        <!-- /.col -->
    </div>
    <!--end::Row-->
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/select2.min.js') }}"></script>
<script>
$(function () {

    function formatOption (state) {
        if (!state.id) return state.text;

        const isSelected = $(state.element).prop('selected');

        return $(`
            <span>
                <input type="checkbox" ${isSelected ? 'checked' : ''} style="margin-right:6px;">
                ${state.text}
            </span>
        `);
    }

    $('#filtro_productos, #filtro_operacion, #mov_filtro_productos, #mov_filtro_tipos').select2({
        width: '100%',
        placeholder: 'Buscar...',
        closeOnSelect: false,
        templateResult: formatOption,
        templateSelection: formatOption
    });

    // Filtros Stock General
    $('#btnFiltrar').on('click', function() {
        const fecha = $('#fecha_inicio').val();
        if(!fecha) return alert('Seleccione una fecha');
        $('#loadingOverlay').removeClass('d-none');
        $.get('{{ route("kardex.stock_general") }}', { fecha: fecha }, function(html) {
            $('#reporteTab1').html(html);
        }).always(() => $('#loadingOverlay').addClass('d-none'));
    });

    $('#btnExportar').on('click', function() {
        const fecha = $('#fecha_inicio').val();
        if(!fecha) return alert('Seleccione una fecha');
        window.location.href = '{{ route("kardex.stock_general.export") }}?fecha=' + fecha;
    });

    $('#btnPdf').on('click', function() {
        const fecha = $('#fecha_inicio').val();
        if(!fecha) return alert('Seleccione una fecha');
        $(this).attr('href', '{{ route("kardex.stock_general.imprimir") }}?fecha=' + fecha);
    });

    // Filtros Kardex
    $('#btnFiltrarKardex').on('click', function() {
        const params = {
            fecha_inicio: $('#fecha_inicio_fechas').val(),
            fecha_fin: $('#fecha_fin_fechas').val(),
            producto_ids: $('#filtro_productos').val(),
            operaciones: $('#filtro_operacion').val()
        };
        if(!params.fecha_inicio || !params.fecha_fin) return alert('Seleccione rango de fechas');
        $('#loadingOverlay').removeClass('d-none');
        $.get('{{ route("kardex.fechas") }}', params, function(html) {
            $('#reporteTab2').html(html);
        }).always(() => $('#loadingOverlay').addClass('d-none'));
    });

    $('#btnExportarKardex').on('click', function() {
        const params = $.param({
            fecha_inicio: $('#fecha_inicio_fechas').val(),
            fecha_fin: $('#fecha_fin_fechas').val(),
            producto_ids: $('#filtro_productos').val(),
            operaciones: $('#filtro_operacion').val()
        });
        window.location.href = '{{ route("kardex.fechas_productos.export") }}?' + params;
    });

    $('#btnPdfKardex').on('click', function() {
        const params = $.param({
            fecha_inicio: $('#fecha_inicio_fechas').val(),
            fecha_fin: $('#fecha_fin_fechas').val(),
            producto_ids: $('#filtro_productos').val(),
            operaciones: $('#filtro_operacion').val()
        });
        $(this).attr('href', '{{ route("kardex.fechas_productos.imprimir") }}?' + params);
    });

    // Filtros Movimientos
    $('#btnFiltrarMov').on('click', function() {
        const params = {
            fecha_inicio: $('#mov_fecha_inicio').val(),
            fecha_fin: $('#mov_fecha_fin').val(),
            producto_ids: $('#mov_filtro_productos').val(),
            tipos: $('#mov_filtro_tipos').val()
        };
        if(!params.fecha_inicio || !params.fecha_fin) return alert('Seleccione rango de fechas');
        $('#loadingOverlay').removeClass('d-none');
        $.get('{{ route("kardex.reporteMovimientos") }}', params, function(html) {
            $('#reporteTab3').html(html);
        }).always(() => $('#loadingOverlay').addClass('d-none'));
    });

    $('#btnExportarMov').on('click', function() {
        const params = $.param({
            fecha_inicio: $('#mov_fecha_inicio').val(),
            fecha_fin: $('#mov_fecha_fin').val(),
            producto_ids: $('#mov_filtro_productos').val(),
            tipos: $('#mov_filtro_tipos').val()
        });
        window.location.href = '{{ route("kardex.exportarMovimientosExcel") }}?' + params;
    });

    $('#btnPdfMov').on('click', function() {
        const params = $.param({
            fecha_inicio: $('#mov_fecha_inicio').val(),
            fecha_fin: $('#mov_fecha_fin').val(),
            producto_ids: $('#mov_filtro_productos').val(),
            tipos: $('#mov_filtro_tipos').val()
        });
        $(this).attr('href', '{{ route("kardex.imprimirMovimientosPdf") }}?' + params);
    });

});
</script>
<script>
    // Set default dates
    const now = new Date().toISOString().slice(0, 10);
    $('#mov_fecha_inicio, #mov_fecha_fin, #fecha_inicio, #fecha_inicio_fechas, #fecha_fin_fechas').val(now);

    document.getElementById('mnuKardex').classList.add('menu-open');
document.getElementById('itemReporteKardex').classList.add('active');
</script>
@endpush