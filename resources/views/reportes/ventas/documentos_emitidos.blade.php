<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead class="table-light">
            <tr>
                <th>Opción</th>
                <th>Fecha</th>
                <th>Documento</th>
                <th>Serie</th>
                <th>Correlativo</th>
                <th class="text-end">Total</th>
                <th class="text-end">A cuenta</th>
                <th class="text-end">Ítems</th>
                <th>Forma Pago</th>
                <th>ID Cliente</th>
                <th>Razón Social</th>
            </tr>
        </thead>

        <tbody>
            @php
                $fechaActual = null;

                $subtotalDia = 0;
                $subtotalItemsDia = 0;
                $acuentaDia = 0;

                $totalGeneral = 0;
                $totalItemsGeneral = 0;
                $acuentaGeneral = 0;
            @endphp

            @forelse($reportes as $item)
                @php
                    $fechaCompra = \Carbon\Carbon::parse($item->fecha_venta)->format('Y-m-d');
                @endphp

                {{-- SUBTOTAL CUANDO CAMBIA LA FECHA --}}
                @if($fechaActual !== null && $fechaActual !== $fechaCompra)
                    <tr class="table-secondary fw-bold">
                        <td colspan="5" class="text-end">
                            Subtotal {{ $fechaActual }}
                        </td>
                        <td class="text-end">{{ number_format($subtotalDia, 2) }}</td>
                        <td class="text-end">{{ number_format($acuentaDia, 2) }}</td>
                        <td class="text-end">{{ $subtotalItemsDia }}</td>
                        <td colspan="3"></td>
                    </tr>

                    @php
                        $subtotalDia = 0;
                        $subtotalItemsDia = 0;
                        $acuentaDia = 0;
                    @endphp
                @endif

                {{-- FILA NORMAL --}}
                <tr>
                    <td>
                        <button class="btn btn-sm btn-info btn-view-tipo-comprobante"
                                data-id="{{ $item->id }}">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>

                    <td>{{ $fechaCompra }}</td>
                    <td>{{ $item->comprobante_tipo_codigo }}</td>
                    <td>{{ $item->serie }}</td>
                    <td>{{ $item->correlativo }}</td>
                    <td class="text-end">{{ number_format($item->total, 2) }}</td>
                    <td class="text-end">{{ number_format($item->acuenta, 2) }}</td>
                    <td class="text-end">{{ $item->items }}</td>
                    <td>{{ $item->pago_forma_nombre }}</td>
                    <td class="text-end">{{ $item->cliente_id }}</td>
                    <td>{{ $item->cliente_nombre }}</td>
                </tr>

                @php
                    $subtotalDia += $item->total;
                    $subtotalItemsDia += $item->items;
                    $acuentaDia += $item->acuenta;

                    $totalGeneral += $item->total;
                    $totalItemsGeneral += $item->items;
                    $acuentaGeneral += $item->acuenta;

                    $fechaActual = $fechaCompra;
                @endphp

                {{-- SUBTOTAL FINAL --}}
                @if($loop->last)
                    <tr class="table-secondary fw-bold">
                        <td colspan="5" class="text-end">
                            Subtotal {{ $fechaActual }}
                        </td>
                        <td class="text-end">{{ number_format($subtotalDia, 2) }}</td>
                        <td class="text-end">{{ number_format($acuentaDia, 2) }}</td>
                        <td class="text-end">{{ $subtotalItemsDia }}</td>
                        <td colspan="3"></td>
                    </tr>
                @endif

            @empty
                <tr>
                    <td colspan="11" class="text-center">
                        No se encontraron registros
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if($reportes->count())
        <tfoot>
            <tr class="table-dark fw-bold">
                <td colspan="5" class="text-end">TOTAL GENERAL</td>
                <td class="text-end">{{ number_format($totalGeneral, 2) }}</td>
                <td class="text-end">{{ number_format($acuentaGeneral, 2) }}</td>
                <td class="text-end">{{ $totalItemsGeneral }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>


<div id="modalContainerTipoDocumentos"></div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnFiltrar = document.getElementById('btnFiltrarDocumentos');
        const loader = document.getElementById('loadingOverlay');

        btnFiltrar.addEventListener('click', function() {

            const fechaInicio = document.getElementById('fecha_inicio_documentos').value;
            const fechaFin = document.getElementById('fecha_fin_documentos').value;
            const tipoDocumento = document.getElementById('tipo_documento').value;

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

            const url = new URL("{{ route('reportes.tipo_documentos_emitidos') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);
            url.searchParams.append('tipo_documento', tipoDocumento);

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

        const btnExportar = document.getElementById('btnExportarDocumentos');

        btnExportar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio_documentos').value;
            const fechaFin = document.getElementById('fecha_fin_documentos').value;
            const tipoDocumento = document.getElementById('tipo_documento').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas para exportar');
                return;
            }

            const url = new URL("{{ route('reportes.ventas_tipos_documentos.export') }}", window.location.origin);
            url.searchParams.append('fecha_inicio', fechaInicio);
            url.searchParams.append('fecha_fin', fechaFin);
            url.searchParams.append('tipo_documento', tipoDocumento);

            window.open(url.toString(), '_blank'); // abre en nueva pestaña y descarga
        });

        document.getElementById('btnPdfDocumentos').addEventListener('click', function (e) {
            e.preventDefault();

            const fechaInicio = document.getElementById('fecha_inicio_documentos').value;
            const fechaFin = document.getElementById('fecha_fin_documentos').value;
            const tipoDocumento = document.getElementById('tipo_documento').value;

            if (!fechaInicio || !fechaFin) {
                alert('Seleccione ambas fechas');
                return;
            }

            const url = `{{ route('reportes.ventas_tipos_documentos.imprimir') }}?tipo_documento=${tipoDocumento}&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            window.open(url, '_blank');
        });

        document.body.addEventListener('click', function(e) {
            if (e.target && (e.target.matches('.btn-view-tipo-comprobante') || e.target.closest('.btn-view-tipo-comprobante'))) {
                const button = e.target.closest('.btn-view-tipo-comprobante');
                const ventaId = button.getAttribute('data-id');
                if (!ventaId) return;

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
                            modalContainer.id = 'modalContainerTipoDocumentos';
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

            const inputInicio = document.getElementById('fecha_inicio_documentos');
            const inputFin = document.getElementById('fecha_fin_documentos');

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
