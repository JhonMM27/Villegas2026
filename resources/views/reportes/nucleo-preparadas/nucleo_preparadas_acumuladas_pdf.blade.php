<!DOCTYPE html>
<html>

<head>
    <title>Acumulado en Preparadas
        {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
        al
        {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</title>
    <meta charset="utf-8">

    <style>
        body {
            font-family: Courier, monospace;
            font-size: 11.5px;
            margin: 0;
            padding: 0;
        }

        @page {
            margin-top: 60px;
            margin-bottom: 40px;
            margin-left: 20px;
            margin-right: 20px;
        }

        .page-header {
            position: fixed;
            top: -60px;
            left: 0;
            right: 0;
            height: 50px;
            font-family: Courier, monospace;
            font-size: 11.5px;
            padding: 5px 20px;
        }

        .page-header table {
            width: 100%;
            border-collapse: collapse;
        }

        .page-header td {
            padding: 2px 0;
        }

        .text-right {
            text-align: right;
        }

        .header-separator {
            border-bottom: 1px solid #000;
            margin: 5px 0 10px 0;
        }

        .page-number:before {
            content: "Página " counter(page);
        }

        table.reporte {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.reporte th,
        table.reporte td {
            padding: 2px 3px;
            white-space: nowrap;
            overflow: hidden;
        }

        table.reporte th {
            border-bottom: 1px solid #000;
        }

        .linea {
            border-bottom: 1px dashed #000;
        }

        .linea2 {
            border-bottom: 0.5px dashed #000;
        }
    </style>

</head>

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
                    Acumulado en Preparadas
                    {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
                    al
                    {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
                </td>
                <td class="text-right">
                    <small>Usuario: {{ auth()->user()->name }}</small>
                </td>
            </tr>
        </table>
        <div class="header-separator"></div>
    </div>

    @php
        // =========================
        // ACUMULAR POR PRODUCTO
        // =========================
        $acum = collect($reportes ?? [])
            ->groupBy(function($r){
                // clave por producto
                return (string)($r->nucleo_id ?? $r->nucleo_nombre ?? '');
            })
            ->map(function($items){
                $first = $items->first();

                // si costo_unitario varía por registros, aquí puedes:
                // - usar promedio ponderado por kg/sacos o
                // - usar el último
                // Por ahora: promedio simple de costo_unitario (si es numérico)
                $costos = $items->pluck('costo_unitario')->map(fn($x)=>(float)$x);
                $costoProm = $costos->count() ? ($costos->sum() / $costos->count()) : 0;

                return (object)[
                    'nucleo_id'      => $first->nucleo_id ?? null,
                    'nucleo_nombre'  => $first->nucleo_nombre ?? '',
                    'producto_empaque' => $first->producto_empaque ?? '',
                    'linea_nombre'     => $first->linea_nombre ?? '',
                    'costo_unitario'   => $costoProm,

                    'ingreso_kg'       => (float)$items->sum(fn($x)=>(float)($x->ingreso_kg ?? 0)),
                    'ingreso_saco'     => (float)$items->sum(fn($x)=>(float)($x->ingreso_saco ?? 0)),
                    'ingreso_soles'    => (float)$items->sum(fn($x)=>(float)($x->ingreso_soles ?? 0)),
                ];
            })
            ->sortBy(fn($x)=> mb_strtoupper((string)$x->nucleo_nombre, 'UTF-8'))
            ->values();

        // Totales generales
        $subIngKg  = 0.0;
        $subIngSac = 0.0;
        $subIngSol = 0.0;
    @endphp

    <table class="reporte">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Emp</th>
                <th>Línea</th>
                <th>Costo Unitario</th>
                <th class="text-right">Toneladas</th>
                <th class="text-right">Total Sacos</th>
                <th class="text-right">Total Soles</th>
            </tr>
        </thead>

        <tbody>
            @forelse($acum as $r)
                @php
                    $subIngKg  += (float)($r->ingreso_kg ?? 0);
                    $subIngSac += (float)($r->ingreso_saco ?? 0);
                    $subIngSol += (float)($r->ingreso_soles ?? 0);
                @endphp

                <tr class="linea2">
                    <td>{{ $r->nucleo_nombre }}</td>
                    <td>{{ $r->producto_empaque }}</td>
                    <td>{{ $r->linea_nombre }}</td>
                    <td class="text-right">{{ number_format((float)$r->costo_unitario, 4, '.', '') }}</td>
                    <td class="text-right">{{ number_format(((float)($r->ingreso_kg ?? 0)) / 1000, 3, '.', '') }}</td>
                    <td class="text-right">{{ number_format((float)($r->ingreso_saco ?? 0), 4, '.', '') }}</td>
                    <td class="text-right">{{ number_format((float)($r->ingreso_soles ?? 0), 4, '.', '') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-right">No se encontraron registros.</td>
                </tr>
            @endforelse
        </tbody>

        <tfoot>
            <tr class="linea">
                <td colspan="4" class="text-right"><strong>TOTALES</strong></td>
                {{-- Mantengo tu lógica, pero la columna dice "Toneladas", entonces muestro en toneladas --}}
                <td class="text-right"><strong>{{ number_format($subIngKg / 1000, 3, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($subIngSac, 4, '.', '') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($subIngSol, 4, '.', '') }}</strong></td>
            </tr>
        </tfoot>
    </table>

</body>

</html>