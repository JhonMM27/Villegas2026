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
                        <li class="nav-item"><a class="nav-link active" href="#tab1" data-bs-toggle="tab">Relación de ventas realizadas</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab2" data-bs-toggle="tab">Relación de documentos emitidos</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab3" data-bs-toggle="tab">Ventas acumuladas por producto</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab4" data-bs-toggle="tab">Ventas agrupadas por producto</a></li>
                        <!--<li class="nav-item"><a class="nav-link" href="#tab4" data-bs-toggle="tab">Relación de ventas con pago a cuenta</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab5" data-bs-toggle="tab">Relación de ventas preparadas</a></li>-->
                        <li class="nav-item"><a class="nav-link" href="#tab6" data-bs-toggle="tab">Ventas por entregar</a></li>
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
                                <select name="vendedor_id" id="vendedor_id" class="form-select me-2 form-select-sm">
                                    <option value="">Todos</option>
                                    @foreach($vendedores as $vendedor)
                                        <option value="{{ $vendedor->id }}">{{ $vendedor->name }}</option>
                                    @endforeach
                                </select>
                                <input type="date" id="fecha_inicio" class="form-control form-control-sm me-2" placeholder="Fecha inicio">
                                <input type="date" id="fecha_fin" class="form-control form-control-sm me-2" placeholder="Fecha fin">
                                <button id="btnFiltrar" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <button id="btnExportar" class="btn btn-success btn-sm me-2">Exportar Excel</button>
                                <a href="#" id="btnPdf" target="_blank" class="btn btn-danger">PDF</a>
                            </div>

                            <div id="reporteTab1">
                                @include('reportes.ventas.ventas_emitidas', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null])
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab2">
                            <div class="d-flex flex-nowrap overflow-auto mb-2">
                                <select id="tipo_documento" class="form-select form-select-sm">
                                    <option value="">-- Todos --</option>
                                    <option value="01">Factura</option>
                                    <option value="03">Boleta</option>
                                    <option value="NP">Nota de Pedido</option>
                                </select>
                                <input type="date" id="fecha_inicio_documentos" class="form-control form-control-sm me-2" placeholder="Fecha inicio">
                                <input type="date" id="fecha_fin_documentos" class="form-control form-control-sm me-2" placeholder="Fecha fin">
                                <button id="btnFiltrarDocumentos" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <button id="btnExportarDocumentos" class="btn btn-success btn-sm me-2">Exportar Excel</button>
                                <a href="#" id="btnPdfDocumentos" target="_blank" class="btn btn-danger">PDF</a>
                            </div>

                            <div id="reporteTab2">
                                @include('reportes.ventas.documentos_emitidos', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null])
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab3">
                            <div class="d-flex flex-nowrap overflow-auto mb-2">
                                <input type="date" id="fecha_inicio_producto" class="form-control form-control-sm me-2" placeholder="Fecha inicio">
                                <input type="date" id="fecha_fin_producto" class="form-control form-control-sm me-2" placeholder="Fecha fin">
                                <button id="btnFiltrarProducto" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <button id="btnExportarProducto" class="btn btn-success btn-sm me-2">Exportar Excel</button>
                                <a href="#" id="btnPdfProducto" target="_blank" class="btn btn-danger">PDF</a>
                            </div>

                            <div id="reporteTab3">
                                 @include('reportes.ventas.ventas_acumuladas_producto', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null])
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab4">
                            <div class="d-flex flex-nowrap overflow-auto mb-2">
                                <input type="date" id="fecha_inicio_productoAgrupado" class="form-control form-control-sm me-2" placeholder="Fecha inicio">
                                <input type="date" id="fecha_fin_productoAgrupado" class="form-control form-control-sm me-2" placeholder="Fecha fin">
                                <button id="btnFiltrarProductoAgrupado" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <!--<button id="btnExportarProductoAgrupado" class="btn btn-success btn-sm me-2">Exportar Excel</button>-->
                                <a href="#" id="btnPdfProductoAgrupado" target="_blank" class="btn btn-danger">PDF</a>
                            </div>

                            <div id="reporteTab4">
                                 @include('reportes.ventas.ventas_agrupadas_producto', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null])
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab6">
                            <div class="d-flex flex-nowrap overflow-auto mb-2">
                                <select name="vendedor_id_entregar" id="vendedor_id_entregar" class="form-select me-2 form-select-sm">
                                    <option value="">Todos</option>
                                    @foreach($vendedores as $vendedor)
                                        <option value="{{ $vendedor->id }}">{{ $vendedor->name }}</option>
                                    @endforeach
                                </select>
                                <input type="date" id="fecha_inicio_entregar" class="form-control form-control-sm me-2" placeholder="Fecha inicio">
                                <input type="date" id="fecha_fin_entregar" class="form-control form-control-sm me-2" placeholder="Fecha fin">
                                <button id="btnFiltrarEntregar" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <!--<button id="btnExportarProductoAgrupado" class="btn btn-success btn-sm me-2">Exportar Excel</button>-->
                                <a href="#" id="btnPdfEntregar" target="_blank" class="btn btn-danger">PDF</a>
                            </div>

                            <div id="reporteTab6">
                                 @include('reportes.ventas.ventas_por_entregar', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null])
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
document.getElementById('mnuSalida').classList.add('menu-open');
document.getElementById('itemReporteVentas').classList.add('active');
</script>
@endpush