{{-- resources/views/pdf/ventas_por_entregar_fechas.blade.php --}}
<!DOCTYPE html>
<html lang="es">
@php
    $fi = $fechaInicio instanceof \Carbon\Carbon ? $fechaInicio : \Carbon\Carbon::parse($fechaInicio);
    $ff = $fechaFin instanceof \Carbon\Carbon ? $fechaFin : \Carbon\Carbon::parse($fechaFin);

    $titulo = "Ventas por Entregar (".$fi->format('d/m/Y')." al ".$ff->format('d/m/Y').")";
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
                Ventas por Entregar ({{ $fi->format('d/m/Y') }} al {{ $ff->format('d/m/Y') }})
                @if(!empty($vendedorId))
                    <small> &nbsp;|&nbsp; Vendedor ID: {{ $vendedorId }}</small>
                @endif
            </td>
            <td class="text-right">
                <small>Usuario: {{ auth()->user()->name }}</small>
            </td>
        </tr>
    </table>
    <div class="header-separator"></div>
</div>

@php
    $tMonto = 0.0;
    $tVentas = 0;
@endphp

<table class="reporte">
    <thead>
        <tr>
            <th style="width:40px;">ID</th>
            <th style="width:70px;">Fecha</th>
            <th style="width:105px;">Documento</th>
            <th style="width:90px;">Usuario</th>
            <th>Cliente</th>
            <th class="text-right" style="width:70px;">Total</th>
            <th style="width:210px;">Productos por entregar</th>
        </tr>
    </thead>

    <tbody>
        @forelse($reportes as $r)
            @php
                $tMonto += (float)($r->monto ?? 0);
                $tVentas++;
                $fecha = !empty($r->fecha_venta) ? \Carbon\Carbon::parse($r->fecha_venta)->format('d/m/Y') : '';
            @endphp

            <tr>
                <td class="text-right">{{ $r->id }}</td>
                <td>{{ $fecha }}</td>
                <td>{{ $r->documento }}</td>
                <td>{{ $r->user_nombre }}</td>
                <td>{{ $r->cliente_nombre }}</td>
                <td class="text-right">{{ number_format((float)($r->monto ?? 0), 2, '.', '') }}</td>
                <td>{{ $r->productos_por_entregar }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-right">No se encontraron registros.</td>
            </tr>
        @endforelse
    </tbody>

    @if($reportes && $reportes->count() > 0)
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right"><strong>TOTAL GENERAL</strong></td>
                <td class="text-right"><strong>{{ number_format($tMonto, 2, '.', '') }}</strong></td>
                <td class="text-right">
                    <strong>{{ $tVentas }} venta{{ $tVentas === 1 ? '' : 's' }}</strong>
                </td>
            </tr>
        </tfoot>
    @endif
</table>

</body>
</html>