<div class="table-responsive">
    <table id="listadoTable"
           class="table table-hover table-bordered table-striped table-sm align-middle table-app">

        <thead class="table-light">
        <tr>
            <th>Fecha</th>
            <th>Documento</th>
            <th>Descripción</th>
            <th class="text-end">Cantidad</th>
            <th class="text-end">Kg</th>
            <th class="text-end">Precio</th>
            <th class="text-end">Importe</th>
        </tr>
        </thead>

        <tbody>
        @php
            $productoActual = null;

            $subCantidad = 0;
            $subKg = 0;
            $subImporte = 0;

            $totCantidad = 0;
            $totKg = 0;
            $totImporte = 0;
        @endphp

        @forelse($reportes as $r)

            @php
                $producto = $r->producto_nombre;
                $cantidad = (float)($r->cantidad_convertida ?? 0);
                $kg = (float)($r->kg_detalle ?? 0);
                $precio = (float)($r->precio_unitario ?? 0);
                $importe = (float)($r->total ?? 0);
            @endphp

            {{-- SUBTOTAL CUANDO CAMBIA EL PRODUCTO --}}
            @if($productoActual !== null && $productoActual !== $producto)
                <tr class="table-secondary fw-bold">
                    <td colspan="3" class="text-end">
                        Subtotal {{ $productoActual }}
                    </td>
                    <td class="text-end">{{ number_format($subCantidad, 2) }}</td>
                    <td class="text-end">{{ number_format($subKg, 2) }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format($subImporte, 2) }}</td>
                </tr>

                @php
                    $subCantidad = 0;
                    $subKg = 0;
                    $subImporte = 0;
                @endphp
            @endif

            {{-- FILA DE DATOS --}}
            <tr>
                <td>{{ \Carbon\Carbon::parse($r->fecha_venta)->format('d/m/Y') }}</td>
                <td>{{ $r->documento }}</td>
                <td>{{ $r->producto_nombre }}</td>
                <td class="text-end">{{ number_format($cantidad, 2) }}</td>
                <td class="text-end">{{ number_format($kg, 2) }}</td>
                <td class="text-end">{{ number_format($precio, 4) }}</td>
                <td class="text-end">{{ number_format($importe, 2) }}</td>
            </tr>

            @php
                $subCantidad += $cantidad;
                $subKg += $kg;
                $subImporte += $importe;

                $totCantidad += $cantidad;
                $totKg += $kg;
                $totImporte += $importe;

                $productoActual = $producto;
            @endphp

            {{-- SUBTOTAL FINAL --}}
            @if($loop->last)
                <tr class="table-secondary fw-bold">
                    <td colspan="3" class="text-end">
                        Subtotal {{ $productoActual }}
                    </td>
                    <td class="text-end">{{ number_format($subCantidad, 2) }}</td>
                    <td class="text-end">{{ number_format($subKg, 2) }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format($subImporte, 2) }}</td>
                </tr>
            @endif

        @empty
            <tr>
                <td colspan="7" class="text-center text-muted">
                    No se encontraron registros
                </td>
            </tr>
        @endforelse
        </tbody>

        {{-- TOTAL GENERAL --}}
        @if($reportes->count())
            <tfoot>
            <tr class="table-dark fw-bold">
                <td colspan="3" class="text-end">TOTAL GENERAL</td>
                <td class="text-end">{{ number_format($totCantidad, 2) }}</td>
                <td class="text-end">{{ number_format($totKg, 2) }}</td>
                <td></td>
                <td class="text-end">{{ number_format($totImporte, 2) }}</td>
            </tr>
            </tfoot>
        @endif

    </table>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrarProductoAgrupado');

        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio_productoAgrupado').value;
            const fechaFin = document.getElementById('fecha_fin_productoAgrupado').value;

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
            const url = new URL("{{ route('reportes.ventas_agrupadas_producto') }}", window.location.origin);
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

        const btnExportar = document.getElementById('btnExportarProductoAgrupado');

        /*
        btnExportar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio_productoAgrupado').value;
            const fechaFin = document.getElementById('fecha_fin_productoAgrupado').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas para exportar');
                return;
            }

            const url = new URL("{{ route('reportes.ventas_acumuladas_producto.export') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);

            window.open(url.toString(), '_blank'); // abre en nueva pestaña y descarga
        });
        */

        document.addEventListener('click', function (e) {
            if (e.target.id === 'btnPdfProductoAgrupado') {
                e.preventDefault();

                const fechaInicio = document.getElementById('fecha_inicio_productoAgrupado').value;
                const fechaFin = document.getElementById('fecha_fin_productoAgrupado').value;

                if (!fechaInicio || !fechaFin) {
                    alert('Seleccione ambas fechas');
                    return;
                }

                const url = `{{ route('reportes.ventas_agrupadas_producto.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

                window.open(url, '_blank');
            }
        });


        function setFechaActualInputs() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            const fechaActual = `${year}-${month}-${day}`;

            const inputInicio = document.getElementById('fecha_inicio_productoAgrupado');
            const inputFin = document.getElementById('fecha_fin_productoAgrupado');

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
