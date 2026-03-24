<div class="table-responsive">

@forelse($reportes as $formulacion)

    @php
        $fecha = \Carbon\Carbon::parse($formulacion->fecha)->format('d/m/Y');
        $totalSalidaKg = 0;
    @endphp

    <!-- =================== CABECERA =================== -->
    <table class="table table-bordered table-sm table-striped mb-2 table-app">
        <thead class="table-dark">
            <tr class="table-secondary">
                <th colspan="4">
                    FORMULACIÓN Nº {{ $formulacion->id }} — {{ $fecha }} - Cliente: {{ $formulacion->cliente_nombre }}
                </th>
            </tr>
            <tr class="table-secondary">
                <th>Producto</th>
                <th>Empaque</th>
                <th class="text-end">Salida Kg</th>
                <th class="text-end">Items</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $formulacion->producto_nombre }}</td>
                <td>{{ $formulacion->producto_empaque }}</td>
                <td class="text-end">{{ number_format($formulacion->salida_kg, 2) }}</td>
                <td class="text-end">
                    {{ $formulacion->detalles_count ?? $formulacion->detalles->count() }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- =================== DETALLE =================== -->
    <table class="table table-hover table-bordered table-sm mb-3 table-app">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Producto</th>
                <th>Empaque</th>
                <th class="text-end">Salida Kg</th>
            </tr>
        </thead>

        <tbody>
        @foreach($formulacion->detalles as $detalle)
            @php
                $totalSalidaKg += (float) $detalle->salida_kg;
            @endphp

            <tr>
                <td>{{ $detalle->producto_id }}</td>
                <td>{{ $detalle->producto_nombre }}</td>
                <td>{{ $detalle->producto_empaque }}</td>
                <td class="text-end">{{ number_format($detalle->salida_kg, 2) }}</td>
            </tr>
        @endforeach
        </tbody>

        <tfoot class="table-dark fw-bold">
            <tr class="table-secondary">
                <td colspan="3" class="text-end">TOTALES</td>
                <td class="text-end">{{ number_format($totalSalidaKg, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <hr class="my-4">

@empty
    <div class="text-center text-muted py-4">
        No hay registros para el rango seleccionado.
    </div>
@endforelse

</div>


<div id="modalContainerRango"></div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrar');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {
            const numeroInicial = document.getElementById('numeroInicial').value;
            const numeroFinal = document.getElementById('numeroFinal').value;

            if (!numeroInicial || !numeroFinal) {
                alert('Ingrese ambos números');
                return;
            }

            loader.classList.remove('d-none');
            const reporte=document.getElementById('reporteTab1')
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            const url = new URL("{{ route('reportes.formulaciones_rango') }}", window.location.origin);
            url.searchParams.append('numeroInicial', numeroInicial);
            url.searchParams.append('numeroFinal', numeroFinal);

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
            const numeroInicial = document.getElementById('numeroInicial').value;
            const numeroFinal = document.getElementById('numeroFinal').value;

            if (!numeroInicial || !numeroFinal) {
                alert('Seleccione ambas fechas para exportar');
                return;
            }

            const url = new URL("{{ route('reportes.formulaciones_rango.export') }}", window.location.origin);
            url.searchParams.append('numeroInicial', numeroInicial);
            url.searchParams.append('numeroFinal', numeroFinal);

            window.open(url.toString(), '_blank'); // abre en nueva pestaña y descarga
        });

        document.getElementById('btnPdfRango').addEventListener('click', function (e) {
            e.preventDefault();

            const numeroInicial = document.getElementById('numeroInicial').value;
            const numeroFinal = document.getElementById('numeroFinal').value;

            if (!numeroInicial || !numeroFinal) {
                alert('Seleccione ambas fechas');
                return;
            }

            const url = `{{ route('reportes.formulaciones_rango.imprimir') }}?numeroInicial=${numeroInicial}&numeroFinal=${numeroFinal}`;

            window.open(url, '_blank');
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
