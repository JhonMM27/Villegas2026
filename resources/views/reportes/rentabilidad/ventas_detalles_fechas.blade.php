<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Documento</th>

                <th>Producto</th>
                <th>Línea</th>
                <th>U.M.</th>

                <th class="text-end">Cantidad</th>
                <th class="text-end">Precio</th>
                <th class="text-end">Importe</th>

                <th class="text-end">Costo</th>
                <th class="text-end">Valor</th>
                <th class="text-end">Rentab</th>
            </tr>
        </thead>

        <tbody>
            @php
                // =========================
                // APLANAR TODO A FILAS
                // =========================
                $rows = collect();

                foreach ($reportes as $venta) {
                    $fecha = \Carbon\Carbon::parse($venta->fecha_venta)->format('Y-m-d');

                    $documento = trim(
                        ($venta->comprobante_tipo_codigo ? $venta->comprobante_tipo_codigo.' ' : '') .
                        ($venta->serie ?? '') . '-' . ($venta->correlativo ?? '')
                    );

                    foreach (($venta->detalles ?? collect()) as $d) {
                        $cantidad = (float)($d->cantidad ?? 0);
                        $precio   = (float)($d->precio_unitario ?? 0);

                        $rows->push((object)[
                            'fecha'      => $fecha,
                            'documento'  => $documento,

                            'producto'   => (string)($d->producto_nombre ?? ''),
                            'linea'      => (string)($d->producto->linea->nombre  ?? ''),
                            'um'         => (string)($d->unidad_nombre ?? ($d->unidad_medida ?? '')),

                            'cantidad'   => $cantidad,
                            'precio'     => $precio,
                            'importe'    => $cantidad * $precio,

                            'costo'      => (float)($d->costo_unitario ?? 0),
                            'valor'      => (float)($d->costo_total ?? 0),
                            'rentab'     => (float)($d->rentabilidad ?? 0),
                        ]);
                    }
                }

                // =========================
                // AGRUPAR POR PRODUCTO Y ORDENAR A-Z
                // =========================
                $grupos = $rows->groupBy('producto')
                               ->sortBy(function($items) {
                                   return mb_strtoupper($items->first()->producto, 'UTF-8');
                               });

                // Totales generales
                $gImporte = 0.0;
                $gCosto   = 0.0;
                $gValor   = 0.0;
                $gRentDet = 0.0;
                $gRentVentas = collect($reportes)->sum(fn($v) => (float)($v->rentabilidad ?? 0));
            @endphp

            @forelse($grupos as $productoId => $items)
                @php
                    $p = $items->first();

                    $sumCantidad = $items->sum('cantidad');
                    $sumImporte  = $items->sum('importe');
                    $sumCosto    = $items->sum(fn($x) => $x->cantidad * $x->costo);
                    $sumValor    = $items->sum('valor');
                    $sumRentDet  = $items->sum('rentab');

                    $gImporte += $sumImporte;
                    $gCosto   += $sumCosto;
                    $gValor   += $sumValor;
                    $gRentDet += $sumRentDet;
                @endphp

                {{-- DETALLE DE CADA PRODUCTO --}}
                @foreach($items as $r)
                    <tr>
                        <td>{{ $r->fecha }}</td>
                        <td>{{ $r->documento }}</td>

                        <td>{{ $r->producto }}</td>
                        <td>{{ $r->linea }}</td>
                        <td>{{ $r->um }}</td>

                        <td class="text-end">{{ number_format($r->cantidad, 2, '.', '') }}</td>
                        <td class="text-end">{{ number_format($r->precio, 4, '.', '') }}</td>
                        <td class="text-end">{{ number_format($r->importe, 2, '.', '') }}</td>

                        <td class="text-end">{{ number_format($r->costo, 4, '.', '') }}</td>
                        <td class="text-end">{{ number_format($r->valor, 4, '.', '') }}</td>
                        <td class="text-end">{{ number_format($r->rentab, 4, '.', '') }}</td>
                    </tr>
                @endforeach

                {{-- TOTAL PRODUCTO --}}
                <tr class="table-secondary fw-bold">
                    <td colspan="5" class="text-end">TOTAL {{ $p->producto }}</td>
                    <td class="text-end">{{ number_format($sumCantidad, 2, '.', '') }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format($sumImporte, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($sumCosto, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($sumValor, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($sumRentDet, 2, '.', '') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse
        </tbody>

        @if($grupos->count() > 0)
            <tfoot>
                <tr class="table-dark fw-bold">
                    <td colspan="7" class="text-end">TOTAL GENERAL</td>
                    <td class="text-end">{{ number_format($gImporte, 2, '.', '') }}</td>
                    <td class="text-end"></td>
                    <td class="text-end">{{ number_format($gValor, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($gRentVentas, 2, '.', '') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrarDetalles');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {

            const fechaInicio = document.getElementById('fecha_inicio_detalles').value;
            const fechaFin = document.getElementById('fecha_fin_detalles').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            loader.classList.remove('d-none');
            const reporte=document.getElementById('reporteTab2')
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            const url = new URL("{{ route('reportes.rentabilidad_ventas_detalles_fechas') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);

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
                loader.classList.add('d-none');
            });
        });


        document.getElementById('btnPdfDetalles').addEventListener('click', function (e) {
            e.preventDefault();

            const fechaInicio = document.getElementById('fecha_inicio_detalles').value;
            const fechaFin = document.getElementById('fecha_fin_detalles').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            let url = `{{ route('reportes.rentabilidad_ventas_detalles_fechas.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            window.open(url, '_blank');
        });


        function setFechaActualInputs() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            const fechaActual = `${year}-${month}-${day}`;

            const inputInicio = document.getElementById('fecha_inicio_detalles');
            const inputFin = document.getElementById('fecha_fin_detalles');

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