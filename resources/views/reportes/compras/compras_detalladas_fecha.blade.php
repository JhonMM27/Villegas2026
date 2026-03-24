{{-- reportes/compras/compras_detalladas_proveedor.blade.php --}}
<div class="table-responsive">

    <style>
        .subtotal-dia {
            background-color: #ffe5e5 !important;
            font-weight: bold;
            color: red;
            border-top: 2px solid red !important;
        }
    </style>

    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Documento</th>
                <th>Serie</th>
                <th>Correlativo</th>
                <th>Producto</th>
                <th class="text-end">Ing Kg</th>
                <th class="text-end">Cantidad</th>
                <th class="text-end">Costo Unitario</th>
                <th class="text-end">Importe</th>
                <th>Proveedor</th>
            </tr>
        </thead>

        <tbody>
            @php
                $fechaActual = null;

                $subIng = 0;
                $subCant = 0;
                $subImporte = 0;
                $subCount = 0;

                $totalIng = 0;
                $totalCant = 0;
                $totalImporte = 0;
                $totalCount = 0;

                // Orden por fecha (REPORTE REAL)
                $items = $reportes->sortBy('fecha_compra');
            @endphp

            @foreach($items as $item)

                {{-- Cuando cambia la fecha -> mostramos subtotales --}}
                @if($fechaActual && $fechaActual != $item->fecha_compra)
                    <tr class="subtotal-dia">
                        <td class="text-center">{{ $subCount }}</td>
                        <td colspan="4"></td>
                        <td class="text-end">{{ number_format($subIng, 2) }}</td>
                        <td class="text-end">{{ number_format($subCant, 2) }}</td>
                        <td></td>
                        <td class="text-end">{{ number_format($subImporte, 2) }}</td>
                        <td></td>
                    </tr>

                    <tr><td colspan="8" class="linea-roja"></td></tr>

                    @php
                        $subIng = 0;
                        $subCant = 0;
                        $subImporte = 0;
                        $subCount = 0;
                    @endphp
                @endif

                @php $fechaActual = $item->fecha_compra; @endphp

                {{-- Fila normal --}}
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->fecha_compra)->format('d/m/Y') }}</td>
                    <td>{{ $item->comprobante_tipo_codigo }}</td>
                    <td>{{ $item->serie }}</td>
                    <td>{{ $item->correlativo }}</td>
                    <td>{{ $item->producto_nombre }}</td>
                    <td class="text-end">{{ number_format($item->ing_kg, 2) }}</td>
                    <td class="text-end">{{ number_format($item->cantidad, 2) }}</td>
                    <td class="text-end">{{ number_format($item->p_lista, 4) }}</td>
                    <td class="text-end">{{ number_format($item->importe, 2) }}</td>
                    <td>{{ $item->proveedor_nombre }}</td>
                </tr>

                {{-- Acumuladores --}}
                @php
                    $subIng += $item->ing_kg;
                    $subCant += $item->cantidad;
                    $subImporte += $item->importe;
                    $subCount++;

                    $totalIng += $item->ing_kg;
                    $totalCant += $item->cantidad;
                    $totalImporte += $item->importe;
                    $totalCount++;
                @endphp

                {{-- Última fila -> imprimir subtotal final --}}
                @if($loop->last)
                    <tr class="subtotal-dia">
                        <td class="text-center">{{ $subCount }}</td>
                        <td colspan="4"></td>
                        <td class="text-end">{{ number_format($subIng, 2) }}</td>
                        <td class="text-end">{{ number_format($subCant, 2) }}</td>
                        <td></td>
                        <td class="text-end">{{ number_format($subImporte, 2) }}</td>
                        <td></td>
                    </tr>
                @endif

            @endforeach
        </tbody>

        {{-- TOTAL GENERAL --}}
        @if($items->count() > 0)
            <tfoot>
                <tr class="table-dark fw-bold">
                    <td colspan="5" class="text-end">TOTAL GENERAL ({{ $totalCount }} ítems):</td>
                    <td class="text-end">{{ number_format($totalIng, 2) }}</td>
                    <td class="text-end">{{ number_format($totalCant, 2) }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format($totalImporte, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif

    </table>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrarFechaDeta');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio_fechadeta').value;
            const fechaFin = document.getElementById('fecha_fin_fechadeta').value;

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

            // Construir la URL con query params
            const url = new URL("{{ route('reportes.compras_detalladas_fecha') }}", window.location.origin);
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

        const btnExportar = document.getElementById('btnExportarFechaDeta');

        btnExportar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio_fechadeta').value;
            const fechaFin = document.getElementById('fecha_fin_fechadeta').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas para exportar');
                return;
            }

            const url = new URL("{{ route('reportes.compras_detalladas_fecha.export') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);

            window.open(url.toString(), '_blank'); // abre en nueva pestaña y descarga
        });

        document.getElementById('btnPdfFechaDeta').addEventListener('click', function (e) {
            e.preventDefault();

            const fechaInicio = document.getElementById('fecha_inicio_fechadeta').value;
            const fechaFin = document.getElementById('fecha_fin_fechadeta').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            const url = `{{ route('reportes.compras_detalladas_fecha.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            window.open(url, '_blank');
        });

        function setFechaActualInputs() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            const fechaActual = `${year}-${month}-${day}`;

            const inputInicio = document.getElementById('fecha_inicio_fechadeta');
            const inputFin = document.getElementById('fecha_fin_fechadeta');

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
