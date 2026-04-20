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
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Créditos por Cobrar</h5>
                    </div>
                </div>

                <div class="card-body report-container">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-12 col-lg-4">
                            <label class="form-label mb-0">Cliente(s)</label>
                            <select id="filtro_clientes" class="form-select form-select-sm select2" multiple>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-primary btn-sm" id="btnFiltrar">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" id="btnLimpiar">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </button>
                        </div>
                    </div>

                    <table id="tablaCreditos" class="table table-sm table-bordered table-hover w-100">
                        <thead class="table-light">
                            <tr>
                                <th style="width:80px;">Acción</th>
                                <th>Fecha</th>
                                <th>Documento</th>
                                <th>Cliente</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Abonos</th>
                                <th class="text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modalContainer"></div>
@endsection

@push('scripts')
<script src="{{ asset('js/select2.min.js') }}"></script>
<script>
$(function() {
    var tablaCreditos = null;
    var modalContainer = document.getElementById('modalContainer');

    function getClienteIds() {
        var ids = [];
        $('#filtro_clientes option:selected').each(function() {
            ids.push($(this).val());
        });
        return ids;
    }

    function reloadTable() {
        if (tablaCreditos) {
            tablaCreditos.ajax.reload(null, false);
        }
    }

    function initSelect2() {
        $('#filtro_clientes').select2({
            placeholder: 'Seleccione cliente(s)',
            allowClear: true,
            ajax: {
                url: '{{ route("clientes.buscar") }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(item) {
                            return {
                                id: item.id,
                                text: item.documento_numero
                                    ? item.id + ' - ' + item.razon_social + ' (' + item.documento_numero + ')'
                                    : item.id + ' - ' + item.razon_social
                            };
                        })
                    };
                },
                cache: true
            }
        });
    }

    function initDataTable() {
        tablaCreditos = $('#tablaCreditos').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("cuenta.corriente.cliente.creditos_por_cobrar_data") }}',
                type: 'GET',
                data: function(d) {
                    var clienteIds = getClienteIds();
                    if (clienteIds.length > 0) {
                        d.cliente_ids = clienteIds;
                    }
                }
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' },
                { data: 'fecha_venta', name: 'fecha_venta' },
                { data: 'documento', name: 'documento', orderable: false },
                { data: 'cliente_nombre', name: 'cliente_nombre' },
                { data: 'total', name: 'total', className: 'text-right' },
                { data: 'abonos', name: 'abonos', className: 'text-right' },
                { data: 'saldo', name: 'saldo', className: 'text-right' }
            ],
            pageLength: 25,
            language: {
                url: '{{ asset("js/jquery.dataTables.es.json") }}'
            }
        });
    }

    initSelect2();
    initDataTable();

    $('#btnFiltrar').on('click', function() {
        reloadTable();
    });

    $('#btnLimpiar').on('click', function() {
        $('#filtro_clientes').val(null).trigger('change');
        reloadTable();
    });

    document.body.addEventListener('click', function(e) {
        if (e.target && (e.target.matches('.btn-view-venta') || e.target.closest('.btn-view-venta'))) {
            const button = e.target.closest('.btn-view-venta');
            const ventaId = button.getAttribute('data-id');
            if (!ventaId) return;

            const url = "{{ route('ventas.ver', ':id') }}".replace(':id', ventaId);

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('No se pudo cargar la venta');
                    return response.text();
                })
                .then(html => {
                    modalContainer.innerHTML = html;
                    const modalEl = modalContainer.querySelector('.modal');
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                })
                .catch(err => {
                    console.error(err);
                    alert('Error al cargar el detalle de la venta');
                });
        }
    });
});
</script>
@endpush
