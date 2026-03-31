<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped table-sm table-app">
        <thead>
            <tr>
                <th>Fecha Venta</th>
                <th>Documento</th>
                <th>Cliente</th>
                <th class="text-end">Cant. Total</th>
                <th class="text-end">Entregado</th>
                <th class="text-end">Pendiente</th>
                <th>Detalle de Entregas</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalGeneralCantidad = 0;
                $totalGeneralEntregado = 0;
                $totalGeneralPendiente = 0;
            @endphp

            @forelse($ventas as $item)
                @php
                    $fechaVenta = \Carbon\Carbon::parse($item->fecha_venta)->format('d/m/Y');
                    $entregas = $entregasMap[$item->id] ?? [];
                    $totalGeneralCantidad += (float)$item->cantidad_total;
                    $totalGeneralEntregado += (float)$item->entregado_total;
                    $totalGeneralPendiente += (float)$item->pendiente_total;
                @endphp

                <tr>
                    <td class="nowrap">{{ $fechaVenta }}</td>
                    <td class="nowrap">{{ $item->documento }}</td>
                    <td>{{ $item->cliente_nombre }}</td>
                    <td class="text-end">{{ number_format((float)$item->cantidad_total, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format((float)$item->entregado_total, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format((float)$item->pendiente_total, 2, '.', '') }}</td>
                    <td style="min-width: 300px;">
                        @if(count($entregas) > 0)
                            <button class="btn btn-sm btn-outline-secondary mb-1" type="button" data-bs-toggle="collapse" data-bs-target="#entregas-{{ $item->id }}">
                                <i class="bi bi-chevron-down"></i> {{ count($entregas) }} entrega(s)
                            </button>
                            <div class="collapse" id="entregas-{{ $item->id }}">
                                <ul class="list-unstyled mb-0 ps-2" style="font-size: 0.85rem;">
                                    @foreach($entregas as $e)
                                        <li class="text-success">
                                            <i class="bi bi-check-circle"></i>
                                            {{ \Carbon\Carbon::parse($e->fecha_entrega)->format('d/m/Y') }}
                                            - {{ number_format((float)$e->cantidad, 2, '.', '') }}
                                            ({{ $e->producto_nombre }})
                                            <span class="text-muted">#{{ $e->numero_recibo }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <span class="text-muted">Sin entregas adicionales</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No se encontraron registros</td>
                </tr>
            @endforelse
        </tbody>

        @if(count($ventas) > 0)
        <tfoot>
            <tr class="table-dark fw-bold">
                <td colspan="3" class="text-end">TOTAL GENERAL:</td>
                <td class="text-end">{{ number_format($totalGeneralCantidad, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($totalGeneralEntregado, 2, '.', '') }}</td>
                <td class="text-end">{{ number_format($totalGeneralPendiente, 2, '.', '') }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
