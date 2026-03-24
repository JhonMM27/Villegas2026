<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Opción</th>
                <th>Fecha</th>
                <th>Documento</th>
                <th>Usuario</th>
                <th>Cliente</th>
                <th class="text-end">Total</th>
                <th>Productos por entregar</th>
            </tr>
        </thead>

        <tbody>
            @php
                $fechaActual = null;
                $subtotalDia = 0;
                $totalGeneral = 0;
            @endphp

            @forelse($reportes as $item)

                @php
                    $fechaVenta = \Carbon\Carbon::parse($item->fecha_venta)->format('Y-m-d');
                @endphp

                {{-- SI CAMBIA LA FECHA → SUBTOTAL DEL DÍA --}}
                @if($fechaActual !== null && $fechaActual !== $fechaVenta)
                    <tr class="table-secondary fw-bold">
                        <td colspan="5" class="text-end">Subtotal {{ $fechaActual }}</td>
                        <td class="text-end">{{ number_format($subtotalDia, 2, '.', '') }}</td>
                        <td></td>
                    </tr>

                    @php
                        $subtotalDia = 0;
                    @endphp
                @endif

                {{-- FILA NORMAL --}}
                <tr>
                    <td class="text-center" style="width:70px">
                        <button class="btn btn-sm btn-info btn-view-venta-entregar" data-id="{{ $item->id }}" title="Ver venta">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>

                    <td class="nowrap">{{ $fechaVenta }}</td>
                    <td class="nowrap">{{ $item->documento }}</td>
                    <td>{{ $item->user_nombre }}</td>
                    <td>{{ $item->cliente_nombre }}</td>
                    <td class="text-end">{{ number_format((float)$item->monto, 2, '.', '') }}</td>

                    {{-- Productos por entregar: viene como "Arroz (2.00) | Azúcar (1.00)" --}}
                    <td style="min-width: 380px;">
                        {{ $item->productos_por_entregar }}
                    </td>
                </tr>

                @php
                    $subtotalDia += (float)$item->monto;
                    $totalGeneral += (float)$item->monto;
                    $fechaActual = $fechaVenta;
                @endphp

                {{-- SUBTOTAL ÚLTIMO DÍA --}}
                @if($loop->last)
                    <tr class="table-secondary fw-bold">
                        <td colspan="5" class="text-end">Subtotal {{ $fechaActual }}</td>
                        <td class="text-end">{{ number_format($subtotalDia, 2, '.', '') }}</td>
                        <td></td>
                    </tr>
                @endif

            @empty
                <tr>
                    <td colspan="7" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse
        </tbody>

        @if(count($reportes) > 0)
        <tfoot>
            <tr class="table-dark fw-bold">
                <td colspan="5" class="text-end">TOTAL GENERAL:</td>
                <td class="text-end">{{ number_format($totalGeneral, 2, '.', '') }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<div id="modalContainerPorEntregar"></div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrarEntregar');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {

            const fechaInicio = document.getElementById('fecha_inicio_entregar').value;
            const fechaFin = document.getElementById('fecha_fin_entregar').value;
            const vendedorId = document.getElementById('vendedor_id_entregar').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            loader.classList.remove('d-none');
            const reporte=document.getElementById('reporteTab6')
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            const url = new URL("{{ route('reportes.ventas_por_entregar') }}", window.location.origin);
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


        document.getElementById('btnPdfEntregar').addEventListener('click', function (e) {
            e.preventDefault();

            const fechaInicio = document.getElementById('fecha_inicio_entregar').value;
            const fechaFin = document.getElementById('fecha_fin_entregar').value;
            const vendedorId = document.getElementById('vendedor_id_entregar').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            let url = `{{ route('reportes.ventas_por_entregar.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            if (vendedorId) {
                url += `&vendedor_id=${vendedorId}`;
            }

            window.open(url, '_blank');
        });

        document.body.addEventListener('click', function(e) {
            if (e.target && (e.target.matches('.btn-view-venta-entregar') || e.target.closest('.btn-view-venta-entregar'))) {
                const button = e.target.closest('.btn-view-venta-entregar');
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
                        let modalContainer = document.getElementById('modalContainerPorEntregar');
                        if (!modalContainer) {
                            modalContainer = document.createElement('div');
                            modalContainer.id = 'modalContainerPorEntregar';
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

            const inputInicio = document.getElementById('fecha_inicio_entregar');
            const inputFin = document.getElementById('fecha_fin_entregar');

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
