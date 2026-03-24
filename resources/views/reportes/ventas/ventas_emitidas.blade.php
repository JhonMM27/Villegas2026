<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Opción</th>
                <th>Fecha</th>
                <th>Documento</th>
                <th>Serie</th>
                <th>Correlativo</th>
                <th>Total</th>
                <th>A cuenta</th>
                <th>Ítems</th>
                <th>Forma Pago</th>
                <th>ID Cliente</th>
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
                    $fechaCompra = \Carbon\Carbon::parse($item->fecha_venta)->format('Y-m-d');
                @endphp

                {{-- SI CAMBIA LA FECHA → SUBTOTAL --}}
                @if($fechaActual !== null && $fechaActual !== $fechaCompra)
                    <tr class="table-secondary fw-bold">
                        <td colspan="5" class="text-end">Subtotal {{ $fechaActual }}</td>
                        <td class="text-end">{{ number_format($subtotalDia, 2, '.', '') }}</td>
                        <td colspan="5"></td>
                    </tr>

                    @php
                        $subtotalDia = 0;
                        $subtotalItemsDia = 0;
                    @endphp
                @endif

                {{-- FILA NORMAL --}}
                <tr>
                    <td>
                        <button class="btn btn-sm btn-info btn-view-venta" data-id="{{ $item->id }}">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>

                    <td>{{ $fechaCompra }}</td>
                    <td>{{ $item->comprobante_tipo_codigo }}</td>
                    <td>{{ $item->serie }}</td>
                    <td>{{ $item->correlativo }}</td>
                    <td class="text-end">{{ number_format($item->total, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($item->acuenta, 2, '.', '') }}</td>
                    <td class="text-end">{{ $item->items }}</td>
                    <td>{{ $item->pago_forma_nombre }}</td>
                    <td class="text-end">{{ $item->cliente_id }}</td>
                    <td>{{ $item->cliente_nombre }}</td>
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
                        <td colspan="4" class="text-end">Subtotal {{ $fechaActual }}</td>
                        <td class="text-end">{{ $subtotalItemsDia }}</td>
                        <td class="text-end">{{ number_format($subtotalDia, 2, '.', '') }}</td>
                        <td colspan="5"></td>
                    </tr>
                @endif

            @empty

                <tr>
                    <td colspan="11" class="text-center">No se encontraron registros</td>
                </tr>

            @endforelse
        </tbody>

        @if(count($reportes) > 0)
        <tfoot>
            <tr class="table-dark fw-bold">
                <td colspan="4" class="text-end">TOTAL GENERAL:</td>
                <td class="text-end">{{ $totalItemsGeneral }}</td>
                <td class="text-end">{{ number_format($totalGeneral, 2, '.', '') }}</td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
        @endif

    </table>
</div>

<div id="modalContainerDocumentos"></div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrar');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {

            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;
            const vendedorId = document.getElementById('vendedor_id').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            loader.classList.remove('d-none');
            const reporte=document.getElementById('reporteTab1')
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            const url = new URL("{{ route('reportes.ventas_emitidas') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);
            if (vendedorId) { // ✅ si viene vacío es "Todos"
                url.searchParams.append('vendedor_id', vendedorId);
            }

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

        const btnExportar = document.getElementById('btnExportar');

        btnExportar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;
            const vendedorId = document.getElementById('vendedor_id').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas para exportar');
                return;
            }

            const url = new URL("{{ route('reportes.ventas_emitidas.export') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);
            if (vendedorId) { // ✅ si viene vacío es "Todos"
                url.searchParams.append('vendedor_id', vendedorId);
            }

            window.open(url.toString(), '_blank'); // abre en nueva pestaña y descarga
        });

        document.getElementById('btnPdf').addEventListener('click', function (e) {
            e.preventDefault();

            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;
            const vendedorId = document.getElementById('vendedor_id').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            let url = `{{ route('reportes.ventas_emitidas.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            if (vendedorId) {
                url += `&vendedor_id=${vendedorId}`;
            }

            window.open(url, '_blank');
        });

        document.body.addEventListener('click', function(e) {
            if (e.target && (e.target.matches('.btn-view-venta') || e.target.closest('.btn-view-venta'))) {
                const button = e.target.closest('.btn-view-venta');
                const ventaId = button.getAttribute('data-id');
                if (!ventaId) return;

                //const url = `/ventas/${ventaId}/ver`;
                const url = "{{ route('ventas.ver', ':id') }}".replace(':id', ventaId);

                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('No se pudo cargar la venta');
                        return response.text();
                    })
                    .then(html => {
                        let modalContainer = document.getElementById('modalContainer');
                        if (!modalContainer) {
                            modalContainer = document.createElement('div');
                            modalContainer.id = 'modalContainerDocumentos';
                            document.body.appendChild(modalContainer);
                        }
                        modalContainer.innerHTML = html;

                        const modalEl = modalContainer.querySelector('.modal');
                        const modal = new bootstrap.Modal(modalEl);
                        modalContainer.querySelector('.btn-duplicate-venta')?.remove();
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

            const inputInicio = document.getElementById('fecha_inicio');
            const inputFin = document.getElementById('fecha_fin');

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
