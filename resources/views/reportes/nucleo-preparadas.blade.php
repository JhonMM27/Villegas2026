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
                        <li class="nav-item"><a class="nav-link active" href="#tab1" data-bs-toggle="tab">Núcleo Preparadas acumuladas (fechas)</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab2" data-bs-toggle="tab">Núcleo Preparadas por producto (fechas)</a></li>
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
                                <input type="date" id="fecha_inicio" class="form-control form-control-sm me-2" placeholder="Número inicio">
                                <input type="date" id="fecha_fin" class="form-control form-control-sm me-2" placeholder="Número fin">
                                <button id="btnFiltrar" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <a href="#" id="btnPdfAcumuladas" target="_blank" class="btn btn-danger">PDF</a>
                            </div>
                            <div id="reporteTab1">
                                @include('reportes.nucleo-preparadas.nucleo_preparadas_acumuladas', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null])
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab2">
                            <div class="d-flex flex-nowrap overflow-auto mb-2">
                                <input type="date" id="fecha_inicio_fechas" class="form-control form-control-sm me-2" placeholder="Fecha inicio">
                                <input type="date" id="fecha_fin_fechas" class="form-control form-control-sm me-2" placeholder="Fecha fin">
                                <button id="btnFiltrarFechas" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <a href="#" id="btnPdfFechas" target="_blank" class="btn btn-danger">PDF</a>
                            </div>

                            <div id="reporteTab2">
                                @include('reportes.nucleo-preparadas.nucleo_preparadas_fechas', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null])
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
document.getElementById('mnuNucleo').classList.add('menu-open');
document.getElementById('itemReporteNucleoPreparadas').classList.add('active');
</script>
@endpush