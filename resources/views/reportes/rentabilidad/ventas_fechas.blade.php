<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Opción</th>
                <th>Fecha</th>
                <th>Documento</th>
                <th>Serie</th>
                <th>Correlativo</th>

                <th>Producto</th>
                <th>Línea</th>

                <th class="text-end">Cantidad</th>
                <th class="text-end">Precio</th>
                <th class="text-end">Importe</th>

                <th class="text-end">Costo</th>
                <th class="text-end">Valor</th>
                <th class="text-end">Rentabilidad</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalImporte = 0.0;
                $totalCosto   = 0.0;
                $totalValor   = 0.0;

                // regla tuya: sum(ventas.rentabilidad)
                $totalRentabilidadVentas = 0.0;
            @endphp

            @forelse($reportes as $venta)
                @php
                    $fecha = \Carbon\Carbon::parse($venta->fecha_venta)->format('Y-m-d');

                    $vImporte = 0.0;
                    $vCosto   = 0.0;
                    $vValor   = 0.0;

                    $detalles = $venta->detalles ?? collect();
                    $countDetalles = $detalles->count();

                    $totalRentabilidadVentas += (float)($venta->rentabilidad ?? 0);
                @endphp

                @if($countDetalles === 0)
                    <tr>
                        <td>
                            <button class="btn btn-sm btn-info btn-view-venta" data-id="{{ $venta->id }}">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                        <td>{{ $fecha }}</td>
                        <td>{{ $venta->comprobante_tipo_codigo }}</td>
                        <td>{{ $venta->serie }}</td>
                        <td>{{ $venta->correlativo }}</td>
                        <td colspan="8" class="text-center text-muted">Venta sin detalles</td>
                    </tr>

                    <tr class="table-secondary fw-bold">
                        <td colspan="9" class="text-end">Subtotal venta</td>
                        <td class="text-end">{{ number_format(0, 2, '.', '') }}</td>
                        <td class="text-end">{{ number_format(0, 2, '.', '') }}</td>
                        <td class="text-end">{{ number_format(0, 2, '.', '') }}</td>
                        <td class="text-end">{{ number_format((float)($venta->rentabilidad ?? 0), 2, '.', '') }}</td>
                    </tr>
                @else
                    @foreach($detalles as $i => $d)
                        @php
                            // TODO viene de BD:
                            $cantidad = (float)($d->cantidad ?? 0);
                            $precio   = (float)($d->precio_unitario ?? 0);

                            $importe  = (float)($d->importe ?? 0);
                            $costo    = (float)($d->costo_unitario ?? 0);
                            $valor    = (float)($d->valor ?? 0);

                            // solo acumulas
                            $vImporte += $importe;
                            $vCosto   += $costo;
                            $vValor   += $valor;
                        @endphp

                        <tr>
                            <td>
                                <button class="btn btn-sm btn-info btn-view-venta" data-id="{{ $venta->id }}">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>

                            <td>{{ $fecha }}</td>
                            <td>{{ $venta->comprobante_tipo_codigo }}</td>
                            <td>{{ $venta->serie }}</td>
                            <td>{{ $venta->correlativo }}</td>

                            <td>{{ $d->producto_nombre }}</td>
                            <td>{{ $d->producto->linea->nombre  }}</td>

                            <td class="text-end">{{ number_format($cantidad, 2, '.', '') }}</td>
                            <td class="text-end">{{ number_format($precio, 4, '.', '') }}</td>
                            <td class="text-end">{{ number_format($importe, 2, '.', '') }}</td>

                            <td class="text-end">{{ number_format($costo, 4, '.', '') }}</td>
                            <td class="text-end">{{ number_format($valor, 4, '.', '') }}</td>
                            <td class="text-end">{{ number_format((float)($d->rentabilidad ?? 0), 4, '.', '') }}</td>
                        </tr>

                        @if($i === $countDetalles - 1)
                            @php
                                $totalImporte += $vImporte;
                                $totalCosto   += $vCosto;
                                $totalValor   += $vValor;
                            @endphp

                            <tr class="table-secondary fw-bold">
                                <td colspan="9" class="text-end">
                                    Subtotal venta ({{ $venta->comprobante_tipo_codigo }} {{ $venta->serie }}-{{ $venta->correlativo }})
                                </td>
                                <td class="text-end">{{ number_format($vImporte, 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format($vCosto, 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format($vValor, 2, '.', '') }}</td>
                                <td class="text-end">{{ number_format((float)($venta->rentabilidad ?? 0), 2, '.', '') }}</td>
                            </tr>
                        @endif
                    @endforeach
                @endif
            @empty
                <tr>
                    <td colspan="13" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse
        </tbody>

        @if(count($reportes) > 0)
            <tfoot>
                <tr class="table-dark fw-bold">
                    <td colspan="9" class="text-end">TOTAL GENERAL</td>
                    <td class="text-end">{{ number_format($totalImporte, 2, '.', '') }}</td>
                    <td class="text-end"></td>
                    <td class="text-end">{{ number_format($totalValor, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($totalRentabilidadVentas, 2, '.', '') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<div id="modalContainer"></div>
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

            const url = new URL("{{ route('reportes.rentabilidad_ventas_fechas') }}", window.location.origin);
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
                loader.classList.add('d-none'); // ✅ OCULTAR
            });
        });


        document.getElementById('btnPdf').addEventListener('click', function (e) {
            e.preventDefault();

            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            let url = `{{ route('reportes.rentabilidad_ventas_fechas.imprimir') }}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            window.open(url, '_blank');
        });

        document.body.addEventListener('click', function(e) {
            if (e.target && (e.target.matches('.btn-view-venta') || e.target.closest('.btn-view-venta'))) {
                const button = e.target.closest('.btn-view-venta');
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
                        let modalContainer = document.getElementById('modalContainer');
                        if (!modalContainer) {
                            modalContainer = document.createElement('div');
                            modalContainer.id = 'modalContainer';
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
