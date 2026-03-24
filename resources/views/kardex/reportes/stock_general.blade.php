<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th class="text-end">Empaque</th>
                <th>Línea</th>
                <th class="text-end">Stock</th>
                <th class="text-end">Costo Unitario</th>
                <th class="text-end">Valor total</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalValor = 0;
                $totalStock = 0;

                $lineaActual = null;
                $subStock = 0;
                $subValor = 0;
            @endphp

            @forelse($reportes as $r)
                @php
                    $linea = $r->linea ?? 'SIN LÍNEA';
                    $stock = (float)($r->stock ?? 0);
                    $costo = (float)($r->costo_unitario ?? 0);
                    $valor = (float)($r->valor_total ?? ($stock * $costo));
                @endphp

                {{-- Si cambia la línea, imprimimos subtotal de la línea anterior --}}
                @if($lineaActual !== null && $linea !== $lineaActual)
                    <tr style="font-weight:bold; background:#f8f9fa;">
                        <td colspan="4" class="text-end">SUBTOTAL {{ $lineaActual }}:</td>
                        <td class="text-end">{{ number_format($subStock, 2) }}</td>
                        <td></td>
                        <td class="text-end">{{ number_format($subValor, 2) }}</td>
                    </tr>

                    @php
                        $subStock = 0;
                        $subValor = 0;
                    @endphp
                @endif

                @php
                    // actualizar línea actual
                    if ($lineaActual === null) $lineaActual = $linea;
                    if ($linea !== $lineaActual) $lineaActual = $linea;

                    // acumular subtotales y totales
                    $subStock += $stock;
                    $subValor += $valor;

                    $totalStock += $stock;
                    $totalValor += $valor;
                @endphp

                <tr>
                    <td>{{ $r->producto_id }}</td>
                    <td>{{ $r->producto }}</td>
                    <td class="text-end">{{ number_format((float)$r->empaque, 2) }}</td>
                    <td>{{ $linea }}</td>
                    <td class="text-end">{{ number_format($stock, 2) }}</td>
                    <td class="text-end">{{ number_format($costo, 4) }}</td>
                    <td class="text-end">{{ number_format($valor, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse

            {{-- Subtotal final (última línea) --}}
            @if(count($reportes) > 0)
                <tr style="font-weight:bold; background:#f8f9fa;">
                    <td colspan="4" class="text-end">SUBTOTAL {{ $lineaActual }}:</td>
                    <td class="text-end">{{ number_format($subStock, 2) }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format($subValor, 2) }}</td>
                </tr>
            @endif
        </tbody>

        @if(count($reportes) > 0)
        <tfoot>
            <tr style="font-weight:bold; border-top:2px solid #000;">
                <th colspan="4" class="text-end">TOTAL GENERAL:</th>
                <th class="text-end">{{ number_format($totalStock, 2) }}</th>
                <th></th>
                <th class="text-end">{{ number_format($totalValor, 2) }}</th>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<div id="modalContainerStock"></div>


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrar');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio')?.value;

            if (!fechaInicio) {
            alert('Seleccione fecha inicio y fecha fin');
            return;
            }


            loader.classList.remove('d-none');
            const reporte=document.getElementById('reporteTab1')
            reporte.innerHTML = `
                <div class="text-center text-muted py-5">
                    Preparando reporte...
                </div>
            `;

            const url = new URL("{{ route('kardex.stock_general') }}", window.location.origin);
            url.searchParams.append('fecha', fechaInicio);

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

        btnExportar.addEventListener('click', function () {

            const fecha = document.getElementById('fecha_inicio')?.value;

            if (!fecha) {
                alert('Seleccione una fecha para exportar');
                return;
            }

            const url = new URL(
                "{{ route('kardex.stock_general.export') }}", // ← tu ruta real
                window.location.origin
            );

            url.searchParams.set('fecha', fecha);

            window.open(url.toString(), '_blank'); // descarga Excel
        });
        
        document.getElementById('btnPdf').addEventListener('click', function (e) {
            e.preventDefault();

            const fecha = document.getElementById('fecha_inicio')?.value; // tu input es "fecha_inicio"
            if (!fecha) {
                alert('Seleccione una fecha');
                return;
            }

            const url = new URL("{{ route('kardex.stock_general.imprimir') }}", window.location.origin);
            url.searchParams.set('fecha', fecha);

            window.open(url.toString(), '_blank');
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