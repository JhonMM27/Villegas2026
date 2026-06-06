@php
    $operacionesOrden = ['COMPRA', 'VENTA', 'PREPARADA', 'PREPARADA_NUCLEO', 'PRESTAMO'];
    $agrupado = $reportes->groupBy('operacion');

    $totalEntrada = 0;
    $totalSalida = 0;
@endphp

<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Operación</th>
                <th>ID</th>
                <th>Producto</th>
                <th class="text-end">Empaque</th>
                <th>Línea</th>
                <th>Unidad</th>
                <th>Documento</th>

                <th class="text-end">Entrada Und</th>
                <th class="text-end">Salida Und</th>
                <th class="text-end">Precio Unitario</th>

                <th class="text-end">Stock</th>

                <th>Referencia</th>
            </tr>
        </thead>

        <tbody>
            @foreach($operacionesOrden as $operacion)
                @if($agrupado->has($operacion))
                    @php
                        $grupo = $agrupado->get($operacion);
                        $subEntrada = 0;
                        $subSalida = 0;
                    @endphp

                    @foreach($grupo as $r)
                        @php
                            $entrada = (float)($r->entrada_und ?? 0);
                            $salida  = (float)($r->salida_und ?? 0);
                            $subEntrada += $entrada;
                            $subSalida  += $salida;
                            $totalEntrada += $entrada;
                            $totalSalida  += $salida;
                        @endphp

                        <tr>
                            <td>
                                @php
                                    $f = $r->fecha ?? null;
                                @endphp
                                {{ $f ? \Carbon\Carbon::parse($f)->format('d/m/Y H:i') : '-' }}
                            </td>

                            <td>{{ $r->operacion ?? '-' }}</td>
                            <td class="text-nowrap">{{ $r->id ?? '-' }}</td>

                            <td>{{ $r->producto ?? '-' }}</td>
                            <td class="text-end">{{ number_format((float)($r->empaque ?? 0), 2) }}</td>
                            <td>{{ $r->linea ?? '-' }}</td>
                            <td>{{ $r->unidad ?? '-' }}</td>

                            <td class="text-nowrap">{{ $r->documento ?? '-' }}</td>

                            <td class="text-end">{{ number_format($entrada, 4) }}</td>
                            <td class="text-end">{{ number_format($salida, 4) }}</td>
                            <td class="text-end">{{ number_format((float)($r->precio_unitario ?? 0), 4) }}</td>

                            <td class="text-end">{{ number_format((float)($r->stock_und ?? 0), 2) }}</td>

                            <td>{{ $r->referencia ?? '-' }}</td>
                        </tr>
                    @endforeach

                    <tr class="fw-bold border-top border-dark" style="background-color: #e9ecef;">
                        <td colspan="8">{{ $operacion }} - SUBTOTAL</td>
                        <td class="text-end">{{ number_format($subEntrada, 4) }}</td>
                        <td class="text-end">{{ number_format($subSalida, 4) }}</td>
                        <td colspan="3"></td>
                    </tr>
                @endif
            @endforeach

            @if($reportes->isEmpty())
                <tr>
                    <td colspan="13" class="text-center">No se encontraron registros</td>
                </tr>
            @endif
        </tbody>

        <tfoot>
            <tr class="fw-bold">
                <td colspan="8" class="text-end">TOTALES</td>

                <td class="text-end">
                    {{ number_format($totalEntrada, 4) }}
                </td>

                <td class="text-end">
                    {{ number_format($totalSalida, 4) }}
                </td>

                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<div id="modalContainerStock"></div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrarKardex');
        const loader = document.getElementById('loadingOverlay');
        const reporte = document.getElementById('reporteTab2');

        // ✅ helper: obtiene values seleccionados de un <select multiple>
        function getSelectedValues(selectId) {
            const el = document.getElementById(selectId);
            if (!el) return [];
            return Array.from(el.selectedOptions).map(o => o.value);
        }

        btnFiltrar.addEventListener('click', function () {
            const fechaInicio = document.getElementById('fecha_inicio_fechas')?.value;
            const fechaFin    = document.getElementById('fecha_fin_fechas')?.value;

            if (!fechaInicio || !fechaFin) {
            alert('Seleccione fecha inicio y fecha fin');
            return;
            }

            // ✅ Bootstrap puro (sin jQuery, sin select2)
            const productoIds = getSelectedValues('filtro_productos');      // ["1","2"]
            const operaciones = getSelectedValues('filtro_operacion');      // ["VENTA","COMPRA"]

            // ✅ obliga a seleccionar al menos 1 producto (tu controller también lo hace)
            if (productoIds.length === 0) {
            alert('Seleccione al menos un producto');
            return;
            }

            loader.classList.remove('d-none');
            reporte.innerHTML = `
            <div class="text-center text-muted py-5">Preparando reporte...</div>
            `;

            const url = new URL("{{ route('kardex.fechas') }}", window.location.origin);

            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);

            // ✅ OJO: el controlador espera producto_ids (no "productos")
            productoIds.forEach(id => url.searchParams.append('producto_ids[]', id));

            // ✅ operaciones[] tal cual espera el controller
            operaciones.forEach(op => url.searchParams.append('operaciones[]', op));

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => { reporte.innerHTML = html; })
            .catch(err => {
                console.error(err);
                alert('Ocurrió un error al cargar el reporte');
            })
            .finally(() => loader.classList.add('d-none'));
        });

        
        const btnExportar = document.getElementById('btnExportarKardex');

        btnExportar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio_fechas').value;
            const fechaFin = document.getElementById('fecha_fin_fechas').value;
            const productoIds = getSelectedValues('filtro_productos');      // ["1","2"]
            const operaciones = getSelectedValues('filtro_operacion');      // ["VENTA","COMPRA"]

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas para exportar');
                return;
            }

            const url = new URL("{{ route('kardex.fechas_productos.export') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);
            // ✅ OJO: el controlador espera producto_ids (no "productos")
            productoIds.forEach(id => url.searchParams.append('producto_ids[]', id));

            // ✅ operaciones[] tal cual espera el controller
            operaciones.forEach(op => url.searchParams.append('operaciones[]', op));


            window.open(url.toString(), '_blank'); // abre en nueva pestaña y descarga
        });
        
        document.getElementById('btnPdfKardex').addEventListener('click', function (e) {
            e.preventDefault();

            const fi = document.getElementById('fecha_inicio_fechas').value;
            const ff = document.getElementById('fecha_fin_fechas').value;
            const productoIds = getSelectedValues('filtro_productos');      // ["1","2"]
            const operaciones = getSelectedValues('filtro_operacion');      // ["VENTA","COMPRA"]

            // si tienes arrays producto_ids / operaciones, concaténalos igual:
            const url = new URL("{{ route('kardex.fechas_productos.imprimir') }}", window.location.origin);
            url.searchParams.set('fecha_inicio', fi);
            url.searchParams.set('fecha_fin', ff);  
            // ✅ OJO: el controlador espera producto_ids (no "productos")
            productoIds.forEach(id => url.searchParams.append('producto_ids[]', id));

            // ✅ operaciones[] tal cual espera el controller
            operaciones.forEach(op => url.searchParams.append('operaciones[]', op));

            window.open(url.toString(), '_blank');
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