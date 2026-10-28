@php
    $operacionesOrden = ['COMPRA', 'VENTA', 'VENTA_ENTREGA', 'PREPARADA', 'PREPARADA_NUCLEO', 'PRESTAMO'];
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
