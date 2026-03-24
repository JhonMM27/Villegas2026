<div class="table-responsive">
    <table id="listadoTable" class="table table-hover table-bordered table-striped table-sm align-middle table-app">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th class="text-end">Empaque</th>
                <th>Producto</th>
                <th>Línea</th>
                <th class="text-end">Kg</th>
                <th class="text-end">Cantidad</th>
                <th class="text-end">Precio</th>
                <th class="text-end">Importe</th>
            </tr>
        </thead>

        <tbody>
            @php
                // Control de línea
                $lineaActual = null;

                // Subtotales
                $subtotalKg = 0;
                $subtotalCantidad = 0;
                $subtotalImporte = 0;

                // Totales generales
                $totalKg = 0;
                $totalCantidad = 0;
                $totalImporte = 0;
            @endphp

            @forelse($reportes as $item)

                {{-- SUBTOTAL CUANDO CAMBIA LA LÍNEA --}}
                @if($lineaActual !== null && $lineaActual !== $item->linea)
                    <tr class="table-secondary fw-bold">
                        <td colspan="4" class="text-end">
                            Subtotal {{ $lineaActual }}
                        </td>
                        <td class="text-end">{{ number_format($subtotalKg, 2) }}</td>
                        <td class="text-end">{{ number_format($subtotalCantidad, 2) }}</td>
                        <td></td>
                        <td class="text-end">{{ number_format($subtotalImporte, 2) }}</td>
                    </tr>

                    @php
                        // Reiniciar subtotales
                        $subtotalKg = 0;
                        $subtotalCantidad = 0;
                        $subtotalImporte = 0;
                    @endphp
                @endif

                {{-- FILA DE DATOS --}}
                <tr>
                    <td>{{ $item->producto_id }}</td>
                    <td class="text-end">{{ number_format($item->producto_empaque, 2) }}</td>
                    <td>{{ $item->producto_nombre }}</td>
                    <td>{{ $item->linea }}</td>
                    <td class="text-end">{{ number_format($item->kg_total, 2) }}</td>
                    <td class="text-end">{{ number_format($item->cantidad_total, 2) }}</td>
                    <td class="text-end">{{ number_format($item->precio_unitario, 4) }}</td>
                    <td class="text-end">{{ number_format($item->importe_total, 2) }}</td>
                </tr>

                @php
                    // Acumular subtotales
                    $subtotalKg += $item->kg_total;
                    $subtotalCantidad += $item->cantidad_total;
                    $subtotalImporte += $item->importe_total;

                    // Acumular totales generales
                    $totalKg += $item->kg_total;
                    $totalCantidad += $item->cantidad_total;
                    $totalImporte += $item->importe_total;

                    // Actualizar línea
                    $lineaActual = $item->linea;
                @endphp

                {{-- SUBTOTAL FINAL --}}
                @if($loop->last)
                    <tr class="table-secondary fw-bold">
                        <td colspan="4" class="text-end">
                            Subtotal {{ $lineaActual }}
                        </td>
                        <td class="text-end">{{ number_format($subtotalKg, 2) }}</td>
                        <td class="text-end">{{ number_format($subtotalCantidad, 2) }}</td>
                        <td></td>
                        <td class="text-end">{{ number_format($subtotalImporte, 2) }}</td>
                    </tr>
                @endif

            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">
                        No se encontraron registros
                    </td>
                </tr>
            @endforelse
        </tbody>

        {{-- TOTAL GENERAL --}}
        @if($reportes->count())
        <tfoot>
            <tr class="table-dark fw-bold">
                <td colspan="4" class="text-end">TOTAL GENERAL</td>
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
            const reporte=document.getElementById('reporteTab3')
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;
            // Construir la URL con query params
            const url = new URL("{{ route('reportes.ventas_acumuladas_producto') }}", window.location.origin);
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

            const url = new URL("{{ route('reportes.ventas_acumuladas_producto.export') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);

            window.open(url.toString(), '_blank'); // abre en nueva pestaña y descarga
        });

        document.addEventListener('click', function (e) {
            if (e.target.id === 'btnPdfProducto') {
                e.preventDefault();

                const fechaInicio = document.getElementById('fecha_inicio_producto').value;
                const fechaFin = document.getElementById('fecha_fin_producto').value;

                if (!fechaInicio || !fechaFin) {
                    alert('Seleccione ambas fechas');
                    return;
                }

                const url = `{{ route('reportes.ventas_acumuladas_producto.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

                window.open(url, '_blank');
            }
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
