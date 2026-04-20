@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link active" href="#tab1" data-bs-toggle="tab">Resumen de Gastos</a></li>
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
                                <div class="col-12 col-sm-4 col-lg-2">
                                    <label class="form-label mb-0">Fecha Inicio</label>
                                    <input type="date" id="fecha_inicio" class="form-control form-control-sm">
                                </div>
                                <div class="col-12 col-sm-4 col-lg-2">
                                    <label class="form-label mb-0">Fecha Fin</label>
                                    <input type="date" id="fecha_fin" class="form-control form-control-sm">
                                </div>
                                <div class="col-12 col-sm-4 col-lg-2">
                                    <label class="form-label mb-0">Tipo</label>
                                    <select id="tipo" class="form-select form-select-sm">
                                        <option value="Todos">Todos</option>
                                        <option value="Combustible">Combustible</option>
                                        <option value="Luz">Luz</option>
                                        <option value="Reparaciones">Reparaciones</option>
                                        <option value="Fletes">Fletes</option>
                                        <option value="Administrativo">Administrativo</option>
                                        <option value="Otros">Otros</option>
                                    </select>
                                </div>
                                <div class="col-12 col-lg-auto">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button id="btnFiltrar" class="btn btn-primary btn-sm">Filtrar</button>
                                        <button id="btnExportar" class="btn btn-success btn-sm">Exportar Excel</button>
                                        <a href="#" id="btnPdf" target="_blank" class="btn btn-danger btn-sm">PDF</a>
                                    </div>
                                </div>
                            </div>

                            <div id="reporteTab1">
                                @include('reportes.gastos.resumen', ['reportes' => collect()])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
