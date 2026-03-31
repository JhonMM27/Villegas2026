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
                        <li class="nav-item"><a class="nav-link" href="#tab7" data-bs-toggle="tab">Ventas por entregar Clientes</a></li>
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
                         <div class="tab-pane fade" id="tab7">
                             <div class="d-flex flex-nowrap align-items-center mb-2">
                                 <div class="me-2" style="min-width: 250px; position: relative; z-index: 1000;">
                                     <input type="text" id="cliente_nombre_entregar" class="form-control form-control-sm" placeholder="Buscar cliente..." autocomplete="off">
                                     <input type="hidden" id="cliente_id_entregar" value="">
                                     <ul id="cliente_suggestions_entregar" class="list-group position-absolute w-100" style="z-index: 1001; display:none; top:100%; left:0;"></ul>
                                 </div>
                                 <input type="date" id="fecha_inicio_entregar_cliente" class="form-control form-control-sm me-2">
                                 <input type="date" id="fecha_fin_entregar_cliente" class="form-control form-control-sm me-2">
                                 <div class="btn-group btn-group-sm me-2" role="group">
                                     <input type="radio" class="btn-check" name="filtro_entrega" id="filtro_todos" value="todos" checked>
                                     <label class="btn btn-outline-secondary btn-sm pt-1" for="filtro_todos">Todos</label>
                                     
                                     <input type="radio" class="btn-check" name="filtro_entrega" id="filtro_pendiente" value="pendiente">
                                     <label class="btn btn-outline-warning btn-sm pt-1" for="filtro_pendiente">Pendiente</label>
                                     
                                     <input type="radio" class="btn-check" name="filtro_entrega" id="filtro_entregado" value="entregado">
                                     <label class="btn btn-outline-success btn-sm pt-1" for="filtro_entregado">Entregado</label>
                                 </div>
                                 <button id="btnFiltrarEntregarCliente" class="btn btn-primary btn-sm me-2">Filtrar</button>
                                 <a href="#" id="btnPdfEntregarCliente" target="_blank" class="btn btn-danger btn-sm">PDF</a>
                             </div>

                             <div id="reporteTab7">
                                 @include('reportes.ventas.ventas_por_entregar_clientes', ['ventas' => collect(), 'entregasMap' => [], 'fechaInicio' => null, 'fechaFin' => null, 'tipo' => 'todos'])
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

// Tab 7: Ventas por Entregar Clientes
document.addEventListener('DOMContentLoaded', function() {
    const btnFiltrarTab7 = document.getElementById('btnFiltrarEntregarCliente');
    const btnPdfTab7 = document.getElementById('btnPdfEntregarCliente');
    const loader = document.getElementById('loadingOverlay');

    function setDefaultDatesTab7() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const fechaActual = `${year}-${month}-${day}`;

        const inputInicio = document.getElementById('fecha_inicio_entregar_cliente');
        const inputFin = document.getElementById('fecha_fin_entregar_cliente');

        if (inputInicio && !inputInicio.value) {
            inputInicio.value = fechaActual;
        }
        if (inputFin && !inputFin.value) {
            inputFin.value = fechaActual;
        }
    }
    setDefaultDatesTab7();

    // Live Search para cliente
    const clienteInput = document.getElementById('cliente_nombre_entregar');
    const clienteHidden = document.getElementById('cliente_id_entregar');
    const clienteSuggestions = document.getElementById('cliente_suggestions_entregar');
    let clienteTimeout = null;

    if (clienteInput) {
        clienteInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length < 1) {
                clienteSuggestions.style.display = 'none';
                clienteHidden.value = '';
                return;
            }
            clearTimeout(clienteTimeout);
            clienteTimeout = setTimeout(() => {
                fetch("{{ route('clientes.buscar') }}?q=" + encodeURIComponent(query))
                    .then(r => r.json())
                    .then(data => {
                        if (!data.length) {
                            clienteSuggestions.style.display = 'none';
                            return;
                        }
                        clienteSuggestions.innerHTML = '';
                        data.forEach(item => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item';
                            li.style.cursor = 'pointer';
                            li.textContent = item.documento_numero
                                ? `${item.id} - ${item.razon_social} (${item.documento_numero})`
                                : `${item.id} - ${item.razon_social}`;
                            li.style.backgroundColor = '#fff';
                            li.addEventListener('mouseenter', function() {
                                this.style.backgroundColor = '#f8f9fa';
                            });
                            li.addEventListener('mouseleave', function() {
                                this.style.backgroundColor = '#fff';
                            });
                            li.addEventListener('click', function() {
                                clienteInput.value = item.razon_social;
                                clienteHidden.value = item.id;
                                clienteSuggestions.style.display = 'none';
                            });
                            clienteSuggestions.appendChild(li);
                        });
                        clienteSuggestions.style.display = 'block';
                    });
            }, 150);
        });

        clienteInput.addEventListener('blur', function() {
            setTimeout(() => {
                clienteSuggestions.style.display = 'none';
            }, 200);
        });
    }

    if (btnFiltrarTab7) {
        btnFiltrarTab7.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio_entregar_cliente').value;
            const fechaFin = document.getElementById('fecha_fin_entregar_cliente').value;
            const clienteId = document.getElementById('cliente_id_entregar').value;
            const tipo = document.querySelector('input[name="filtro_entrega"]:checked')?.value || 'todos';

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            loader.classList.remove('d-none');
            const reporte = document.getElementById('reporteTab7');
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            const url = new URL("{{ route('reportes.ventas_por_entregar_clientes') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);
            if (clienteId) {
                url.searchParams.append('cliente_id', clienteId);
            }
            url.searchParams.append('tipo', tipo);

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
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
        });
    }

    if (btnPdfTab7) {
        btnPdfTab7.addEventListener('click', function(e) {
            e.preventDefault();

            const fechaInicio = document.getElementById('fecha_inicio_entregar_cliente').value;
            const fechaFin = document.getElementById('fecha_fin_entregar_cliente').value;
            const clienteId = document.getElementById('cliente_id_entregar').value;
            const tipo = document.querySelector('input[name="filtro_entrega"]:checked')?.value || 'todos';

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            let url = `{{ route('reportes.ventas_por_entregar_clientes.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            if (clienteId) {
                url += `&cliente_id=${clienteId}`;
            }
            url += `&tipo=${tipo}`;

            window.open(url, '_blank');
        });
    }
});
</script>
@endpush