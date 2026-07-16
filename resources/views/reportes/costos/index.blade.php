@extends('plantilla.app')
@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link active" href="#tab1" data-bs-toggle="tab">Reportes Generales</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab2" data-bs-toggle="tab">Reporte Detallado</a></li>
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
                                    <input type="text" id="fecha_inicio" class="form-control form-control-sm date-picker">
                                </div>
                                <div class="col-12 col-sm-4 col-lg-2">
                                    <label class="form-label mb-0">Fecha Fin</label>
                                    <input type="text" id="fecha_fin" class="form-control form-control-sm date-picker">
                                </div>
                                <div class="col-12 col-sm-4 col-lg-2">
                                    <label class="form-label mb-0">Categoría</label>
                                    <select id="categoria" class="form-select form-select-sm">
                                        <option value="Todos">Todos</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 col-lg-2">
                                    <label class="form-label mb-0">Tipo</label>
                                    <select id="tipo" class="form-select form-select-sm">
                                        <option value="Todos">Todos</option>
                                    </select>
                                </div>
                                <div class="col-12 col-lg-auto">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button id="btnFiltrar1" class="btn btn-primary btn-sm">Filtrar</button>
                                        <button id="btnExportar1" class="btn btn-success btn-sm">Exportar Excel</button>
                                        <a href="#" id="btnPdf1" target="_blank" class="btn btn-danger btn-sm">PDF</a>
                                    </div>
                                </div>
                            </div>

                            <div id="reporteTab1">
                                @include('reportes.costos.general', ['reportes' => collect()])
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab2">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-12 col-sm-4 col-lg-2">
                                    <label class="form-label mb-0">Fecha Inicio</label>
                                    <input type="text" id="fecha_inicio_2" class="form-control form-control-sm date-picker">
                                </div>
                                <div class="col-12 col-sm-4 col-lg-2">
                                    <label class="form-label mb-0">Fecha Fin</label>
                                    <input type="text" id="fecha_fin_2" class="form-control form-control-sm date-picker">
                                </div>
                                <div class="col-12 col-sm-4 col-lg-2">
                                    <label class="form-label mb-0">Categoría</label>
                                    <select id="categoria_2" class="form-select form-select-sm">
                                        <option value="Todos">Todos</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-4 col-lg-2">
                                    <label class="form-label mb-0">Tipo</label>
                                    <select id="tipo_2" class="form-select form-select-sm">
                                        <option value="Todos">Todos</option>
                                    </select>
                                </div>
                                <div class="col-12 col-lg-auto">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button id="btnFiltrar2" class="btn btn-primary btn-sm">Filtrar</button>
                                        <button id="btnExportar2" class="btn btn-success btn-sm">Exportar Excel</button>
                                        <a href="#" id="btnPdf2" target="_blank" class="btn btn-danger btn-sm">PDF</a>
                                    </div>
                                </div>
                            </div>

                            <div id="reporteTab2">
                                @include('reportes.costos.detallado', ['reportes' => collect()])
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    cargarCategorias();

    async function cargarCategorias() {
        try {
            const response = await fetch('{{ route('costos.select.categorias') }}');
            const categorias = await response.json();

            ['categoria', 'categoria_2'].forEach(id => {
                const select = document.getElementById(id);
                if (!select) return;
                select.innerHTML = '<option value="Todos">Todos</option>';
                categorias.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.nombre;
                    select.appendChild(option);
                });
            });
        } catch (error) {
            console.error('Error cargando categorías:', error);
        }
    }

    async function cargarTiposPorCategoria(categoriaId, targetSelectId) {
        const select = document.getElementById(targetSelectId);
        if (!select) return;

        if (categoriaId === 'Todos' || categoriaId === '') {
            select.innerHTML = '<option value="Todos">Todos</option>';
            return;
        }

        try {
            const response = await fetch(`{{ route('costos.tipos.select') }}?categoria_id=${categoriaId}`);
            const tipos = await response.json();
            select.innerHTML = '<option value="Todos">Todos</option>';
            tipos.forEach(tipo => {
                const option = document.createElement('option');
                option.value = tipo.id;
                option.textContent = tipo.nombre;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Error cargando tipos:', error);
        }
    }

    document.getElementById('categoria')?.addEventListener('change', function() {
        cargarTiposPorCategoria(this.value, 'tipo');
    });

    document.getElementById('categoria_2')?.addEventListener('change', function() {
        cargarTiposPorCategoria(this.value, 'tipo_2');
    });

    function setFechaActualInputs() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const fechaActual = `${year}-${month}-${day}`;

        ['fecha_inicio', 'fecha_fin', 'fecha_inicio_2', 'fecha_fin_2'].forEach(id => {
            const input = document.getElementById(id);
            if (input && !input.value) input.value = fechaActual;
        });
    }
    setFechaActualInputs();

    const loader = document.getElementById('loadingOverlay');

    function filtrarReporte(tabIndex) {
        const prefix = tabIndex === 1 ? '' : '_2';
        const fechaInicio = document.getElementById(`fecha_inicio${prefix}`)?.value;
        const fechaFin = document.getElementById(`fecha_fin${prefix}`)?.value;
        const categoria = document.getElementById(`categoria${prefix}`)?.value;
        const tipo = document.getElementById(`tipo${prefix}`)?.value;

        if (!fechaInicio || !fechaFin) {
            alert('Seleccione fecha inicio y fecha fin');
            return;
        }

        loader.classList.remove('d-none');
        const reporte = document.getElementById(`reporteTab${tabIndex}`);
        reporte.innerHTML = '<div class="text-center text-muted py-5">Preparando reporte...</div>';

        let url = tabIndex === 1
            ? "{{ route('reportes.costos.general') }}"
            : "{{ route('reportes.costos.detallado') }}";

        url = new URL(url, window.location.origin);
        url.searchParams.append('fecha_inicio', fechaInicio);
        url.searchParams.append('fecha_fin', fechaFin);
        if (categoria && categoria !== 'Todos') url.searchParams.append('categoria_id', categoria);
        if (tipo && tipo !== 'Todos') url.searchParams.append('costo_tipo_id', tipo);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                reporte.innerHTML = html;
            })
            .catch(error => {
                console.error('Error AJAX:', error);
                alert('Ocurrió un error al cargar el reporte');
            })
            .finally(() => {
                loader.classList.add('d-none');
            });
    }

    function exportarReporte(tabIndex) {
        const prefix = tabIndex === 1 ? '' : '_2';
        const fechaInicio = document.getElementById(`fecha_inicio${prefix}`)?.value;
        const fechaFin = document.getElementById(`fecha_fin${prefix}`)?.value;
        const categoria = document.getElementById(`categoria${prefix}`)?.value;
        const tipo = document.getElementById(`tipo${prefix}`)?.value;

        if (!fechaInicio || !fechaFin) {
            alert('Seleccione fecha inicio y fecha fin');
            return;
        }

        let url = tabIndex === 1
            ? "{{ route('reportes.costos.general.export') }}"
            : "{{ route('reportes.costos.detallado.export') }}";

        url = new URL(url, window.location.origin);
        url.searchParams.append('fecha_inicio', fechaInicio);
        url.searchParams.append('fecha_fin', fechaFin);
        if (categoria && categoria !== 'Todos') url.searchParams.append('categoria_id', categoria);
        if (tipo && tipo !== 'Todos') url.searchParams.append('costo_tipo_id', tipo);

        window.open(url.toString(), '_blank');
    }

    function imprimirReporte(tabIndex) {
        const prefix = tabIndex === 1 ? '' : '_2';
        const fechaInicio = document.getElementById(`fecha_inicio${prefix}`)?.value;
        const fechaFin = document.getElementById(`fecha_fin${prefix}`)?.value;
        const categoria = document.getElementById(`categoria${prefix}`)?.value;
        const tipo = document.getElementById(`tipo${prefix}`)?.value;

        if (!fechaInicio || !fechaFin) {
            alert('Seleccione fecha inicio y fecha fin');
            return;
        }

        let url = tabIndex === 1
            ? "{{ route('reportes.costos.general.imprimir') }}"
            : "{{ route('reportes.costos.detallado.imprimir') }}";

        url = new URL(url, window.location.origin);
        url.searchParams.append('fecha_inicio', fechaInicio);
        url.searchParams.append('fecha_fin', fechaFin);
        if (categoria && categoria !== 'Todos') url.searchParams.append('categoria_id', categoria);
        if (tipo && tipo !== 'Todos') url.searchParams.append('costo_tipo_id', tipo);

        window.open(url.toString(), '_blank');
    }

    document.getElementById('btnFiltrar1')?.addEventListener('click', () => filtrarReporte(1));
    document.getElementById('btnFiltrar2')?.addEventListener('click', () => filtrarReporte(2));
    document.getElementById('btnExportar1')?.addEventListener('click', (e) => { e.preventDefault(); exportarReporte(1); });
    document.getElementById('btnExportar2')?.addEventListener('click', (e) => { e.preventDefault(); exportarReporte(2); });
    document.getElementById('btnPdf1')?.addEventListener('click', (e) => { e.preventDefault(); imprimirReporte(1); });
    document.getElementById('btnPdf2')?.addEventListener('click', (e) => { e.preventDefault(); imprimirReporte(2); });
});

document.getElementById('mnuCaja')?.classList.add('menu-open');
document.getElementById('itemReporteCostos')?.classList.add('active');
</script>
@endpush
