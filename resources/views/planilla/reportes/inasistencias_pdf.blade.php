<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inasistencias - Planilla</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .header p { margin: 2px 0; }
        .info { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 10px; }
        .badge-complete { background-color: #dc3545; color: white; }
        .badge-half { background-color: #ffc107; color: black; }
        .footer { position: fixed; bottom: 60px; left: 0; right: 0; text-align: center; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $empresa->razon_social ?? 'EMPRESA' }}</h2>
        <p>{{ $empresa->direccion ?? '' }}</p>
        <p>RUC: {{ $empresa->ruc ?? '' }}</p>
        <h3>REPORTE DE INASISTENCIAS - PLANILLA</h3>
        <p>Período: {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</p>
    </div>

    <div class="info">
        <strong>Total de Inasistencias:</strong> {{ $totalRegistros }}&nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Total Días:</strong> {{ number_format($totalDias, 2) }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Empleado</th>
                <th>DNI</th>
                <th>Fecha</th>
                <th class="text-center">Tipo</th>
                <th class="text-right">Días</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inasistencias as $index => $i)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $i->empleado->nombre ?? 'N/A' }}</td>
                <td>{{ $i->empleado->dni ?? 'N/A' }}</td>
                <td>{{ \Carbon\Carbon::parse($i->fecha)->format('d/m/Y') }}</td>
                <td class="text-center">
                    @if($i->medio_dia)
                        <span class="badge badge-half">Medio Día</span>
                    @else
                        <span class="badge badge-complete">Día Completo</span>
                    @endif
                </td>
                <td class="text-right">{{ $i->medio_dia ? '0.50' : '1.00' }}</td>
                <td>{{ $i->observacion ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right"><strong>TOTAL:</strong></td>
                <td class="text-right"><strong>{{ number_format($totalDias, 2) }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Generado: {{ date('d/m/Y H:i:s') }}
    </div>
</body>
</html>
