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
                        <li class="nav-item"><a class="nav-link active" href="#tab1" data-bs-toggle="tab">Estado de cuenta Préstamo A</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab2" data-bs-toggle="tab">Estado de cuenta Préstamo DE</a></li>
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
                            <div class="d-flex flex-nowrap mb-2" style="overflow: visible;">
                                <input type="text"
                                          id="cliente_destino_nombre"
                                          class="form-control form-control-sm me-2" placeholder="Cliente Destino"
                                          placeholder="Cliente Destino"
                                          autocomplete="off">
                                <input type="hidden" id="cliente_destino_id" name="cliente_destino_id">
                                <input type="date" id="fecha_inicio" class="form-control form-control-sm me-2" placeholder="Fecha inicio">
                                <input type="date" id="fecha_fin" class="form-control form-control-sm me-2" placeholder="Fecha fin">
                                <button id="btnFiltrar" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <a href="#" id="btnPdf" target="_blank" class="btn btn-danger">PDF</a>
                            </div>
                            <div id="reporteTab1">
                                @include('reportes.prestamos.prestamos_a', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null, 'cliente_destino_id' => null])
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab2">
                            <div class="d-flex flex-nowrap mb-2" style="overflow: visible;">
                                <input type="text"
                                          id="cliente_origen_nombre"
                                          class="form-control form-control-sm me-2" placeholder="Cliente origen"
                                          placeholder="Cliente Origen"
                                          autocomplete="off">
                                <input type="hidden" id="cliente_origen_id" name="cliente_origen_id">
                                <input type="date" id="fecha_inicio_origen" class="form-control form-control-sm me-2" placeholder="Fecha inicio">
                                <input type="date" id="fecha_fin_origen" class="form-control form-control-sm me-2" placeholder="Fecha fin">
                                <button id="btnFiltrarOrigen" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                <a href="#" id="btnPdfOrigen" target="_blank" class="btn btn-danger">PDF</a>
                            </div>

                            <div id="reporteTab2">
                                @include('reportes.prestamos.prestamos_de', ['reportes' => collect(), 'fechaInicio' => null, 'fechaFin' => null, 'cliente_origen_id' => null])
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
    document.addEventListener('DOMContentLoaded', function () {

        setupLiveSearchSelect({
            inputId: 'cliente_destino_nombre',
            hiddenId: 'cliente_destino_id',
            url: "{{ route('clientes.buscar') }}",
            template: item => item.documento_numero
                ? `${item.id} - ${item.razon_social} (${item.documento_numero})`
                : `${item.id} - ${item.razon_social}`
        });

        setupLiveSearchSelect({
            inputId: 'cliente_origen_nombre',
            hiddenId: 'cliente_origen_id',
            url: "{{ route('clientes.buscar') }}",
            template: item => item.documento_numero
                ? `${item.id} - ${item.razon_social} (${item.documento_numero})`
                : `${item.id} - ${item.razon_social}`
        });

    });

    function setupLiveSearchSelect({inputId, hiddenId, url, template = item => item.nombre || item.descripcion || '', getId = item => item.id || item.codigo || '', minLength = 3, delay = 300, onSelect}) {
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        if (!input || !hidden) return;
        let timeout = null;
        let activeIndex = -1;
        let suggestions = [];

        const clearSuggestions = () => {
            const oldList = input.parentNode.querySelector('ul.search-list');
            if (oldList) oldList.remove();
            activeIndex = -1;
            suggestions = [];
        };

        const renderSuggestions = (data) => {
            clearSuggestions();
            if (!data.length) return;
            const list = document.createElement('ul');
            list.className = 'list-group position-absolute w-100 search-list';
            list.style.zIndex = '1050';
            list.style.top = '100%';
            suggestions = data;
            data.forEach((item, index) => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action';
                li.textContent = template(item);
                li.dataset.index = index;
                li.onclick = () => {
                    input.value = template(item);
                    hidden.value = getId(item);
                    clearSuggestions();
                    if (onSelect) onSelect(item);
                };
                list.appendChild(li);
            });
            input.parentNode.style.position = 'relative';
            input.parentNode.appendChild(list);
        };

        const fetchSuggestions = (query) => {
            fetch(url + '?q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(renderSuggestions)
                .catch(console.error);
        };

        input.addEventListener('input', () => {
            const query = input.value.trim();
            if (query.length < minLength) {
                hidden.value = '';
                clearSuggestions();
                return;
            }
            clearTimeout(timeout);
            timeout = setTimeout(() => fetchSuggestions(query), delay);
        });

        input.addEventListener('keydown', (e) => {
            const list = input.parentNode.querySelector('ul.search-list');
            if (!list) return;
            const items = list.querySelectorAll('li');
            if (!items.length) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); activeIndex = (activeIndex + 1) % items.length; }
            else if (e.key === 'ArrowUp') { e.preventDefault(); activeIndex = (activeIndex - 1 + items.length) % items.length; }
            else if (e.key === 'Enter') {
                e.preventDefault();
                if (suggestions[activeIndex]) {
                    const item = suggestions[activeIndex];
                    input.value = template(item);
                    hidden.value = getId(item);
                    clearSuggestions();
                    if (onSelect) onSelect(item);
                }
            } else if (e.key === 'Escape') clearSuggestions();
            items.forEach((li,i) => li.classList.toggle('active', i === activeIndex));
        });

        input.addEventListener('blur', () => setTimeout(clearSuggestions, 200));
    }

    document.getElementById('mnuPrestamos').classList.add('menu-open');
    document.getElementById('itemReportePrestamos')?.classList.add('active');
</script>
@endpush