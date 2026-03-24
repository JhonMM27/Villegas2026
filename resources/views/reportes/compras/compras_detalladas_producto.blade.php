<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Producto Id</th>
                <th>Producto</th>
                <th>Fecha</th>
                <th>Kg</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Total</th>
                <th>Documento</th>
                <th>Serie</th>
                <th>Correlativo</th>
                <th>Proveedor</th>
            </tr>
        </thead>
        <tbody>
            @php
                $productoActual = null;
                $subtotalKg = 0;
                $subtotalCantidad = 0;
                $subtotalTotal = 0;
                $contadorCompras = 0;

                $totalKg = 0;
                $totalCantidad = 0;
                $totalGeneral = 0;
                $totalCompras = 0;

                $reportesOrdenados = $reportes->sortBy('producto_nombre');
            @endphp

            @forelse($reportesOrdenados as $item)
                {{-- Subtotal por producto --}}
                @if($productoActual !== null && $productoActual !== $item->producto_nombre)
                    <tr class="table-secondary fw-bold">
                        <td colspan="3" class="text-end">{{ $contadorCompras }}</td>
                        <td class="text-end">{{ number_format($subtotalKg, 2) }}</td>
                        <td class="text-end">{{ number_format($subtotalCantidad, 2) }}</td>
                        <td></td>
                        <td class="text-end">{{ number_format($subtotalTotal, 2) }}</td>
                        <td colspan="4"></td>
                    </tr>
                    @php
                        $subtotalKg = 0;
                        $subtotalCantidad = 0;
                        $subtotalTotal = 0;
                        $contadorCompras = 0;
                    @endphp
                @endif

                <tr>
                    <td>{{ $item->producto_id }}</td>
                    <td>{{ $item->producto_nombre }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->fecha_compra)->format('d/m/Y') }}</td>
                    <td class="text-end">{{ number_format($item->kg_total, 2) }}</td>
                    <td class="text-end">{{ number_format($item->cantidad, 2) }}</td>
                    <td class="text-end">{{ number_format($item->precio, 4) }}</td>
                    <td class="text-end">{{ number_format($item->total, 2) }}</td>
                    <td>{{ $item->comprobante_tipo_codigo }}</td>
                    <td>{{ $item->serie }}</td>
                    <td>{{ $item->correlativo }}</td>
                    <td>{{ $item->proveedor_nombre ?? '-' }}</td>
                </tr>

                @php
                    $subtotalKg += $item->kg_total;
                    $subtotalCantidad += $item->cantidad;
                    $subtotalTotal += $item->total;
                    $contadorCompras++;

                    $totalKg += $item->kg_total;
                    $totalCantidad += $item->cantidad;
                    $totalGeneral += $item->total;
                    $totalCompras++;

                    $productoActual = $item->producto_nombre;
                @endphp

                {{-- Subtotal última fila --}}
                @if($loop->last)
                    <tr class="table-secondary fw-bold">
                        <td colspan="3" class="text-end">Subtotal {{ $productoActual }} ({{ $contadorCompras }} compras):</td>
                        <td class="text-end">{{ number_format($subtotalKg, 2) }}</td>
                        <td class="text-end">{{ number_format($subtotalCantidad, 2) }}</td>
                        <td></td>
                        <td class="text-end">{{ number_format($subtotalTotal, 2) }}</td>
                        <td colspan="4"></td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="11" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse
        </tbody>
        @if($reportes->count() > 0)
        <tfoot>
            <tr class="table-dark fw-bold">
                <td colspan="3" class="text-end">TOTAL GENERAL ({{ $totalCompras }} compras):</td>
                <td class="text-end">{{ number_format($totalKg, 2) }}</td>
                <td class="text-end">{{ number_format($totalCantidad, 2) }}</td>
                <td></td>
                <td class="text-end">{{ number_format($totalGeneral, 2) }}</td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrarProducto');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio_producto').value;
            const fechaFin = document.getElementById('fecha_fin_producto').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            loader.classList.remove('d-none');
            const reporte=document.getElementById('reporteTab4')
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            // Construir la URL con query params
            const url = new URL("{{ route('reportes.compras_detalladas_producto') }}", window.location.origin);
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

        const btnExportar = document.getElementById('btnExportarProducto');

        btnExportar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio_producto').value;
            const fechaFin = document.getElementById('fecha_fin_producto').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas para exportar');
                return;
            }

            const url = new URL("{{ route('reportes.compras_detalladas_producto.export') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);

            window.open(url.toString(), '_blank'); // abre en nueva pestaña y descarga
        });

        document.getElementById('btnPdfProducto').addEventListener('click', function (e) {
            e.preventDefault();

            const fechaInicio = document.getElementById('fecha_inicio_producto').value;
            const fechaFin = document.getElementById('fecha_fin_producto').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            const url = `{{ route('reportes.compras_detalladas_producto.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            window.open(url, '_blank');
        });

        function setFechaActualInputs() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            const fechaActual = `${year}-${month}-${day}`;

            const inputInicio = document.getElementById('fecha_inicio_producto');
            const inputFin = document.getElementById('fecha_fin_producto');

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
