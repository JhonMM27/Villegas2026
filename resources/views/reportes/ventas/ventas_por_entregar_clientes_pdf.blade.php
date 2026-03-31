{{-- resources/views/reportes/ventas/ventas_por_entregar_clientes_pdf.blade.php --}}
<!DOCTYPE html>
<html lang="es">
@php
    $fi = $fechaInicio instanceof \Carbon\Carbon ? $fechaInicio : \Carbon\Carbon::parse($fechaInicio);
    $ff = $fechaFin instanceof \Carbon\Carbon ? $fechaFin : \Carbon\Carbon::parse($fechaFin);
    $tipoLabel = $tipo ?? 'todos';
    if ($tipoLabel === 'pendiente') {
        $tipoLabel = 'Pendiente';
    } elseif ($tipoLabel === 'entregado') {
        $tipoLabel = 'Entregado';
    } else {
        $tipoLabel = 'Todos';
    }

    $titulo = "Ventas por Entregar Clientes - {$tipoLabel} (".$fi->format('d/m/Y')." al ".$ff->format('d/m/Y').")";
@endphp

@include('pdf.styles', ['title' => $titulo])

<body>

<div class="page-header">
    <table>
        <tr>
            <td>CONSORCIOS VILLEGAS EIRL</td>
            <td class="text-right">
                <span class="page-number"></span>&nbsp;&nbsp;{{ now()->format('d/m/Y H:i:s') }}
            </td>
        </tr>
        <tr>
            <td>
                Ventas por Entregar Clientes - {{ $tipoLabel }} ({{ $fi->format('d/m/Y') }} al {{ $ff->format('d/m/Y') }})
            </td>
            <td class="text-right">
                <small>Usuario: {{ auth()->user()->name }}</small>
            </td>
        </tr>
    </table>
    <div class="header-separator"></div>
</div>

@php
    $tCantidad = 0.0;
    $tEntregado = 0.0;
    $tPendiente = 0.0;
    $tVentas = 0;
@endphp

<table class="reporte">
    <thead>
        <tr>
            <th style="width:60px;">Fecha</th>
            <th style="width:80px;">Documento</th>
            <th>Cliente</th>
            <th class="text-right" style="width:60px;">Cant.Total</th>
            <th class="text-right" style="width:60px;">Entregado</th>
            <th class="text-right" style="width:60px;">Pendiente</th>
            <th style="width:200px;">Detalle de Entregas</th>
        </tr>
    </thead>

    <tbody>
        @forelse($ventas as $r)
            @php
                $tCantidad += (float)($r->cantidad_total ?? 0);
                $tEntregado += (float)($r->entregado_total ?? 0);
                $tPendiente += (float)($r->pendiente_total ?? 0);
                $tVentas++;
                $fecha = !empty($r->fecha_venta) ? \Carbon\Carbon::parse($r->fecha_venta)->format('d/m/Y') : '';
                $entregas = $entregasMap[$r->id] ?? [];
                $entregasHtml = '';
                foreach ($entregas as $e) {
                    $entregasHtml .= \Carbon\Carbon::parse($e->fecha_entrega)->format('d/m/Y').': '.number_format((float)$e->cantidad, 2, '.', '').' '.$e->producto_nombre."\n";
                }
            @endphp

            <tr>
                <td>{{ $fecha }}</td>
                <td>{{ $r->documento }}</td>
                <td>{{ $r->cliente_nombre }}</td>
                <td class="text-right">{{ number_format((float)($r->cantidad_total ?? 0), 2, '.', '') }}</td>
                <td class="text-right">{{ number_format((float)($r->entregado_total ?? 0), 2, '.', '') }}</td>
                <td class="text-right">{{ number_format((float)($r->pendiente_total ?? 0), 2, '.', '') }}</td>
                <td style="font-size: 10px; white-space: pre-line;">{{ trim($entregasHtml) ?: 'Sin entregas' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-right">No se encontraron registros.</td>
            </tr>
        @endforelse
    </tbody>

    @if($ventas && $ventas->count() > 0)
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-right"><strong>TOTAL GENERAL</strong></td>
                <td class="text-right"><strong>{{ number_format($tCantidad, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($tEntregado, 2, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($tPendiente, 2, '.', '') }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    @endif
</table>

</body>
</html>
