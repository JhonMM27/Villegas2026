@extends('plantilla.app')
@push('estilos')

@endpush
@section('contenido')
<div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link active" href="#tab1" data-bs-toggle="tab">Compras acumuladas por producto</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab2" data-bs-toggle="tab">Compras por fecha</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab3" data-bs-toggle="tab">Compras por proveedor</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab4" data-bs-toggle="tab">Detalle de productos por fechas</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab5" data-bs-toggle="tab">Detalle de compras por proveedor</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab6" data-bs-toggle="tab">Detalle de compras por fecha</a></li>
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
                            <div class="d-flex flex-nowrap overflow-auto mb-2">
                                <input type="text" id="fecha_inicio" class="form-control form-control-sm me-2 date-picker" placeholder="Fecha inicio">
                                <input type="text" id="fecha_fin" class="form-control form-control-sm me-2 date-picker" placeholder="Fecha fin">
                                <button id="btnFiltrar" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <button id="btnExportar" class="btn btn-success btn-sm me-2">Exportar Excel</button>
                                <a href="#" id="btnPdf" target="_blank" class="btn btn-danger">PDF</a>
                            </div>

                            <div id="reporteTab1">
                                @include('reportes.compras.compras_acumuladas_producto', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null])
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab2">
                            <div class="d-flex flex-nowrap overflow-auto mb-2">
                                <input type="text" id="fecha_inicio_fechas" class="form-control form-control-sm me-2 date-picker" placeholder="Fecha inicio">
                                <input type="text" id="fecha_fin_fechas" class="form-control form-control-sm me-2 date-picker" placeholder="Fecha fin">
                                <button id="btnFiltrarFechas" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <button id="btnExportarFechas" class="btn btn-success btn-sm me-2">Exportar Excel</button>
                                <a href="#" id="btnPdfFechas" target="_blank" class="btn btn-danger">PDF</a>
                            </div>

                            <div id="reporteTab2">
                                @include('reportes.compras.compras_fecha', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null])
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab3">
                            <div class="d-flex flex-nowrap overflow-auto mb-2">
                                <input type="text" id="fecha_inicio_proveedor" class="form-control form-control-sm me-2 date-picker" placeholder="Fecha inicio">
                                <input type="text" id="fecha_fin_proveedor" class="form-control form-control-sm me-2 date-picker" placeholder="Fecha fin">
                                <button id="btnFiltrarProveedor" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <button id="btnExportarProveedor" class="btn btn-success btn-sm me-2">Exportar Excel</button>
                                <a href="#" id="btnPdfProveedor" target="_blank" class="btn btn-danger">PDF</a>
                            </div>

                            <div id="reporteTab3">
                                @include('reportes.compras.compras_proveedor', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null])
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab4">
                            <div class="d-flex flex-nowrap overflow-auto mb-2">
                                <input type="text" id="fecha_inicio_producto" class="form-control form-control-sm me-2 date-picker" placeholder="Fecha inicio">
                                <input type="text" id="fecha_fin_producto" class="form-control form-control-sm me-2 date-picker" placeholder="Fecha fin">
                                <button id="btnFiltrarProducto" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <button id="btnExportarProducto" class="btn btn-success btn-sm me-2">Exportar Excel</button>
                                <a href="#" id="btnPdfProducto" target="_blank" class="btn btn-danger">PDF</a>
                            </div>

                            <div id="reporteTab4">
                                @include('reportes.compras.compras_detalladas_producto', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null])
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab5">
                            <div class="d-flex flex-nowrap overflow-auto mb-2">
                                <input type="text" id="fecha_inicio_proveedordeta" class="form-control form-control-sm me-2 date-picker" placeholder="Fecha inicio">
                                <input type="text" id="fecha_fin_proveedordeta" class="form-control form-control-sm me-2 date-picker" placeholder="Fecha fin">
                                <button id="btnFiltrarProveedorDeta" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <button id="btnExportarProveedorDeta" class="btn btn-success btn-sm me-2">Exportar Excel</button>
                                <a href="#" id="btnPdfProveedorDeta" target="_blank" class="btn btn-danger">PDF</a>
                            </div>

                            <div id="reporteTab5">
                                @include('reportes.compras.compras_detalladas_proveedor', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null])
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab6">
                            <div class="d-flex flex-nowrap overflow-auto mb-2">
                                <input type="text" id="fecha_inicio_fechadeta" class="form-control form-control-sm me-2 date-picker" placeholder="Fecha inicio">
                                <input type="text" id="fecha_fin_fechadeta" class="form-control form-control-sm me-2 date-picker" placeholder="Fecha fin">
                                <button id="btnFiltrarFechaDeta" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <button id="btnExportarFechaDeta" class="btn btn-success btn-sm me-2">Exportar Excel</button>
                                <a href="#" id="btnPdfFechaDeta" target="_blank" class="btn btn-danger">PDF</a>
                            </div>

                            <div id="reporteTab6">
                                @include('reportes.compras.compras_detalladas_fecha', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null])
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
<script>
document.getElementById('mnuIngreso').classList.add('menu-open');
document.getElementById('itemReporteCompras').classList.add('active');
</script>
@endpush