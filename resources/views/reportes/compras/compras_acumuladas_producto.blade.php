<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>ID</th>
                <th>Empaque</th>
                <th>Producto</th>
                <th>Línea</th>
                <th>Kg</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Importe</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalKg = 0;
                $totalCantidad = 0;
                $totalImporte = 0;
                $lineaActual = null;
                $subtotalKg = 0;
                $subtotalCantidad = 0;
                $subtotalImporte = 0;
                $reportesAgrupados = $reportes->sortBy('linea');
            @endphp

            @forelse($reportesAgrupados as $item)
                {{-- Si cambia la línea, mostrar subtotal de la línea anterior --}}
                @if($lineaActual !== null && $lineaActual !== $item->linea)
                <tr class="table-secondary fw-bold">
                    <td colspan="4" class="text-end">Subtotal {{ $lineaActual ?? 'Sin línea' }}:</td>
                    <td class="text-end">{{ number_format($subtotalKg, 2) }}</td>
                    <td class="text-end">{{ number_format($subtotalCantidad, 2) }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format($subtotalImporte, 2) }}</td>
                </tr>
                @php
                    $subtotalKg = 0;
                    $subtotalCantidad = 0;
                    $subtotalImporte = 0;
                @endphp
                @endif

                <tr>
                    <td>{{ $item->producto_id }}</td>
                    <td class="text-end">{{ $item->producto_empaque }}</td>
                    <td>{{ $item->producto_nombre }}</td>
                    <td>{{ $item->linea ?? '-' }}</td>                
                    <td class="text-end">{{ $item->kg_total }}</td>
                    <td class="text-end">{{ $item->cantidad_total }}</td>
                    <td class="text-end">{{ number_format($item->costo_unitario, 4) }}</td>
                    <td class="text-end">{{ number_format($item->importe_total, 2) }}</td>
                </tr>

                @php
                    $subtotalKg += $item->kg_total;
                    $subtotalCantidad += $item->cantidad_total;
                    $subtotalImporte += $item->importe_total;
                    $totalKg += $item->kg_total;
                    $totalCantidad += $item->cantidad_total;
                    $totalImporte += $item->importe_total;
                    $lineaActual = $item->linea;
                @endphp

                {{-- Subtotal de la última línea --}}
                @if($loop->last)
                <tr class="table-secondary fw-bold">
                    <td colspan="4" class="text-end">Subtotal {{ $lineaActual ?? 'Sin línea' }}:</td>
                    <td class="text-end">{{ number_format($subtotalKg, 2) }}</td>
                    <td class="text-end">{{ number_format($subtotalCantidad, 2) }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format($subtotalImporte, 2) }}</td>
                </tr>
                @endif

            @empty
            <tr>
                <td colspan="8" class="text-center">No se encontraron registros</td>
            </tr>
            @endforelse
        </tbody>
        @if($reportes->count() > 0)
        <tfoot>
            <tr class="table-dark fw-bold">
                <td colspan="4" class="text-end">TOTAL GENERAL:</td>
                <td class="text-end">{{ number_format($totalKg, 2) }}</td>
                <td class="text-end">{{ number_format($totalCantidad, 2) }}</td>
                <td></td>
                <td class="text-end">{{ number_format($totalImporte, 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrar');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;

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

            // Construir la URL con query params
            const url = new URL("{{ route('reportes.compras_acumuladas_producto') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);

            // Llamada AJAX con fetch
            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // Reemplazar el contenido del div con id "reporteTab1"
                reporte.innerHTML = html;
            })
            .catch(error => {
                console.error('Error al cargar el reporte:', error);
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

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas para exportar');
                return;
            }

            const url = new URL("{{ route('reportes.compras_acumuladas_producto.export') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);

            window.open(url.toString(), '_blank'); // abre en nueva pestaña y descarga
        });

        document.getElementById('btnPdf').addEventListener('click', function (e) {
            e.preventDefault();

            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            const url = `{{ route('reportes.compras_acumuladas_producto.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            window.open(url, '_blank');
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
