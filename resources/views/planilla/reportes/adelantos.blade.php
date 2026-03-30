<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Adelantos - Planilla</title>
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
        .totals { margin-top: 20px; }
        .totals table { width: 50%; margin-left: auto; }
        .totals td { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $empresa->razon_social ?? 'EMPRESA' }}</h2>
        <p>{{ $empresa->direccion ?? '' }}</p>
        <p>RUC: {{ $empresa->ruc ?? '' }}</p>
        <h3>REPORTE DE ADELANTOS - PLANILLA</h3>
        <p>Fecha: {{ date('d/m/Y') }}</p>
    </div>

    <div class="info">
        <strong>Total de Adelantos:</strong> {{ $totalRegistros }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Empleado</th>
                <th>DNI</th>
                <th>Fecha</th>
                <th class="text-right">Monto</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @php $totalMonto = 0; @endphp
            @foreach($adelantos as $index => $a)
            @php $totalMonto += (float)$a->monto; @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $a->empleado->nombre ?? 'N/A' }}</td>
                <td>{{ $a->empleado->dni ?? 'N/A' }}</td>
                <td>{{ \Carbon\Carbon::parse($a->fecha)->format('d/m/Y') }}</td>
                <td class="text-right">S/ {{ number_format((float)$a->monto, 2) }}</td>
                <td>{{ $a->observaciones ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right"><strong>TOTAL:</strong></td>
                <td class="text-right"><strong>S/ {{ number_format($totalMonto, 2) }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
