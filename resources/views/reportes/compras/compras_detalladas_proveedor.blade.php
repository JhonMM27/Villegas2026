{{-- reportes/compras/compras_detalladas_proveedor.blade.php --}}
<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Proveedor</th>
                <th>Fecha</th>
                <th>Documento</th>
                <th>Serie</th>
                <th>Correlativo</th>
                <th>Producto Id</th>
                <th>Producto</th>
                <th>Ing Kg</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Importe</th>
            </tr>
        </thead>
        <tbody>
            @php
                $proveedorActual = null;
                $subtotalIngKg = 0;
                $subtotalCantidad = 0;
                $subtotalImporte = 0;
                $contadorCompras = 0;

                $totalIngKg = 0;
                $totalCantidad = 0;
                $totalImporte = 0;
                $totalCompras = 0;

                $reportesOrdenados = $reportes->sortBy('proveedor_nombre');
            @endphp

            @forelse($reportesOrdenados as $item)
                {{-- Subtotal por proveedor --}}
                @if($proveedorActual !== null && $proveedorActual !== $item->proveedor_nombre)
                    <tr class="table-danger fw-bold text-danger">
                        <td class="text-end">{{ $contadorCompras }}</td>
                        <td colspan="6"></td>
                        <td class="text-end">{{ number_format($subtotalIngKg, 2) }}</td>
                        <td class="text-end">{{ number_format($subtotalCantidad, 2) }}</td>
                        <td></td>
                        <td class="text-end">{{ number_format($subtotalImporte, 2) }}</td>
                    </tr>
                    @php
                        $subtotalIngKg = 0;
                        $subtotalCantidad = 0;
                        $subtotalImporte = 0;
                        $contadorCompras = 0;
                    @endphp
                @endif

                <tr>
                    <td>{{ $item->proveedor_nombre }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->fecha_compra)->format('d/m/Y') }}</td>
                    <td>{{ $item->comprobante_tipo_codigo }}</td>
                    <td>{{ $item->serie}}</td>
                    <td>{{ $item->correlativo}}</td>
                    <td>{{ $item->producto_id }}</td>
                    <td>{{ $item->producto_nombre }}</td>
                    <td class="text-end">{{ number_format($item->ing_kg, 2) }}</td>
                    <td class="text-end">{{ number_format($item->cantidad, 2) }}</td>
                    <td class="text-end">{{ number_format($item->p_lista, 4) }}</td>
                    <td class="text-end">{{ number_format($item->importe, 2) }}</td>
                </tr>

                @php
                    $subtotalIngKg += $item->ing_kg;
                    $subtotalCantidad += $item->cantidad;
                    $subtotalImporte += $item->importe;
                    $contadorCompras++;

                    $totalIngKg += $item->ing_kg;
                    $totalCantidad += $item->cantidad;
                    $totalImporte += $item->importe;
                    $totalCompras++;

                    $proveedorActual = $item->proveedor_nombre;
                @endphp

                {{-- Subtotal última fila --}}
                @if($loop->last)
                    <tr class="table-danger fw-bold text-danger">
                        <td class="text-end">{{ $contadorCompras }}</td>
                        <td colspan="6"></td>
                        <td class="text-end">{{ number_format($subtotalIngKg, 2) }}</td>
                        <td class="text-end">{{ number_format($subtotalCantidad, 2) }}</td>
                        <td></td>
                        <td class="text-end">{{ number_format($subtotalImporte, 2) }}</td>
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
                <td colspan="7" class="text-end">TOTAL GENERAL ({{ $totalCompras }} compras):</td>
                <td class="text-end">{{ number_format($totalIngKg, 2) }}</td>
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
        const btnFiltrar = document.getElementById('btnFiltrarProveedorDeta');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio_proveedordeta').value;
            const fechaFin = document.getElementById('fecha_fin_proveedordeta').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            loader.classList.remove('d-none');
            const reporte=document.getElementById('reporteTab5')
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            // Construir la URL con query params
            const url = new URL("{{ route('reportes.compras_detalladas_proveedor') }}", window.location.origin);
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

        const btnExportar = document.getElementById('btnExportarProveedorDeta');

        btnExportar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio_proveedordeta').value;
            const fechaFin = document.getElementById('fecha_fin_proveedordeta').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas para exportar');
                return;
            }

            const url = new URL("{{ route('reportes.compras_detalladas_proveedor.export') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);

            window.open(url.toString(), '_blank'); // abre en nueva pestaña y descarga
        });

        document.getElementById('btnPdfProveedorDeta').addEventListener('click', function (e) {
            e.preventDefault();

            const fechaInicio = document.getElementById('fecha_inicio_proveedordeta').value;
            const fechaFin = document.getElementById('fecha_fin_proveedordeta').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            const url = `{{ route('reportes.compras_detalladas_proveedor.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            window.open(url, '_blank');
        });

        function setFechaActualInputs() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            const fechaActual = `${year}-${month}-${day}`;

            const inputInicio = document.getElementById('fecha_inicio_proveedordeta');
            const inputFin = document.getElementById('fecha_fin_proveedordeta');

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
