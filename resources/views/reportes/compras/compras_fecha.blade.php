<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Opción</th>
                <th>Fecha</th>
                <th>Documento</th>
                <th>Serie</th>
                <th>Correlativo</th>
                <th>Forma Pago</th>
                <th>Ítems</th>
                <th>Total</th>
                <th>ID Proveedor</th>
                <th>Razón Social</th>
            </tr>
        </thead>

        <tbody>

            @php
                $fechaActual = null;
                $subtotalDia = 0;
                $subtotalItemsDia = 0;

                $totalGeneral = 0;
                $totalItemsGeneral = 0;
            @endphp

            @forelse($reportes as $item)

                @php
                    $fechaCompra = \Carbon\Carbon::parse($item->fecha_compra)->format('Y-m-d');
                @endphp

                {{-- SI CAMBIA LA FECHA → SUBTOTAL --}}
                @if($fechaActual !== null && $fechaActual !== $fechaCompra)
                    <tr class="table-secondary fw-bold">
                        <td colspan="6" class="text-end">Subtotal {{ $fechaActual }}</td>
                        <td class="text-end">{{ $subtotalItemsDia }}</td>
                        <td class="text-end">{{ number_format($subtotalDia, 2, '.', '') }}</td>
                        <td colspan="2"></td>
                    </tr>

                    @php
                        $subtotalDia = 0;
                        $subtotalItemsDia = 0;
                    @endphp
                @endif

                {{-- FILA NORMAL --}}
                <tr>
                    <td>
                        <button class="btn btn-sm btn-info btn-view-compra" data-id="{{ $item->id }}">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>

                    <td>{{ $fechaCompra }}</td>
                    <td>{{ $item->comprobante_tipo_codigo }}</td>
                    <td>{{ $item->serie }}</td>
                    <td>{{ $item->correlativo }}</td>
                    <td>{{ $item->pago_forma_codigo }}</td>

                    <td class="text-end">{{ $item->total_items }}</td>
                    <td class="text-end">{{ number_format($item->total, 2, '.', '') }}</td>

                    <td class="text-end">{{ $item->proveedor_id }}</td>
                    <td>{{ $item->razon_social }}</td>
                </tr>

                @php
                    $subtotalDia += $item->total;
                    $subtotalItemsDia += $item->total_items;

                    $totalGeneral += $item->total;
                    $totalItemsGeneral += $item->total_items;

                    $fechaActual = $fechaCompra;
                @endphp

                {{-- SUBTOTAL ÚLTIMA FILA --}}
                @if($loop->last)
                    <tr class="table-secondary fw-bold">
                        <td colspan="6" class="text-end">Subtotal {{ $fechaActual }}</td>
                        <td class="text-end">{{ $subtotalItemsDia }}</td>
                        <td class="text-end">{{ number_format($subtotalDia, 2, '.', '') }}</td>
                        <td colspan="2"></td>
                    </tr>
                @endif

            @empty

                <tr>
                    <td colspan="10" class="text-center">No se encontraron registros</td>
                </tr>

            @endforelse
        </tbody>

        @if(count($reportes) > 0)
        <tfoot>
            <tr class="table-dark fw-bold">
                <td colspan="6" class="text-end">TOTAL GENERAL:</td>
                <td class="text-end">{{ $totalItemsGeneral }}</td>
                <td class="text-end">{{ number_format($totalGeneral, 2, '.', '') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
        @endif

    </table>
</div>
<div id="modalContainerFechas"></div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrarFechas');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {

            const fechaInicio = document.getElementById('fecha_inicio_fechas').value;
            const fechaFin = document.getElementById('fecha_fin_fechas').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            loader.classList.remove('d-none');
            const reporte=document.getElementById('reporteTab2')
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            const url = new URL("{{ route('reportes.compras_fecha') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);

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
                loader.classList.add('d-none'); // ✅ OCULTAR
            });
        });

        const btnExportar = document.getElementById('btnExportarFechas');

        btnExportar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio_fechas').value;
            const fechaFin = document.getElementById('fecha_fin_fechas').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas para exportar');
                return;
            }

            const url = new URL("{{ route('reportes.compras_fecha.export') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);

            window.open(url.toString(), '_blank'); // abre en nueva pestaña y descarga
        });

        document.getElementById('btnPdfFechas').addEventListener('click', function (e) {
            e.preventDefault();

            const fechaInicio = document.getElementById('fecha_inicio_fechas').value;
            const fechaFin = document.getElementById('fecha_fin_fechas').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            const url = `{{ route('reportes.compras_fecha.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            window.open(url, '_blank');
        });

        document.body.addEventListener('click', function(e) {
            if (e.target && (e.target.matches('.btn-view-compra') || e.target.closest('.btn-view-compra'))) {
                const button = e.target.closest('.btn-view-compra');
                const compraId = button.getAttribute('data-id');
                if (!compraId) return;

                const url = "{{ route('compras.ver', ':id') }}".replace(':id', compraId);
                //const url = `/compras/${compraId}/ver`;

                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('No se pudo cargar la compra');
                        return response.text();
                    })
                    .then(html => {
                        let modalContainer = document.getElementById('modalContainer');
                        if (!modalContainer) {
                            modalContainer = document.createElement('div');
                            modalContainer.id = 'modalContainerFechas';
                            document.body.appendChild(modalContainer);
                        }
                        modalContainer.innerHTML = html;

                        const modalEl = modalContainer.querySelector('.modal');
                        const modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Ocurrió un error al cargar el detalle.');
                    });
            }
        });

        function setFechaActualInputs() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            const fechaActual = `${year}-${month}-${day}`;

            const inputInicio = document.getElementById('fecha_inicio_fechas');
            const inputFin = document.getElementById('fecha_fin_fechas');

            if(inputInicio && !inputInicio.value) {
                inputInicio.value = fechaActual;
            }
            if(inputFin && !inputFin.value) {
                inputFin.value = fechaActual;
            }
        }

        setFechaActualInputs();

        
    });


</script>
@endpush
