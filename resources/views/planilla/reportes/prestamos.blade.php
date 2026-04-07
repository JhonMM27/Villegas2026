<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Préstamos - Planilla</title>
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
        .badge { padding: 2px 8px; border-radius: 3px; }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-primary { background-color: #cce5ff; color: #004085; }
        .badge-secondary { background-color: #e2e3e5; color: #383d41; }
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
        <h3>REPORTE DE PRÉSTAMOS - PLANILLA</h3>
        <p>Fecha: {{ date('d/m/Y') }}</p>
    </div>

    <div class="info">
        <strong>Total de Préstamos:</strong> {{ $totalRegistros }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>N° Int.</th>
                <th>Empleado</th>
                <th>DNI</th>
                <th>Fecha Préstamo</th>
                <th class="text-right">Monto Original</th>
                <th class="text-right">Saldo Pendiente</th>
                <th class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @php $totalOriginal = 0; $totalSaldo = 0; @endphp
            @foreach($prestamos as $index => $p)
            @php 
                $totalOriginal += (float)$p->monto_original; 
                $totalSaldo += (float)$p->saldo_pendiente; 
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $p->numero_interno }}</td>
                <td>{{ $p->empleado->nombre ?? 'N/A' }}</td>
                <td>{{ $p->empleado->dni ?? 'N/A' }}</td>
                <td>{{ \Carbon\Carbon::parse($p->fecha_prestamo)->format('d/m/Y') }}</td>
                <td class="text-right">S/ {{ number_format((float)$p->monto_original, 2) }}</td>
                <td class="text-right">S/ {{ number_format((float)$p->saldo_pendiente, 2) }}</td>
                <td class="text-center">
                    @if($p->estado === 'activo')
                        <span class="badge badge-success">Activo</span>
                    @elseif($p->estado === 'pagado')
                        <span class="badge badge-primary">Pagado</span>
                    @else
                        <span class="badge badge-secondary">Anulado</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right"><strong>TOTALES:</strong></td>
                <td class="text-right"><strong>S/ {{ number_format($totalOriginal, 2) }}</strong></td>
                <td class="text-right"><strong>S/ {{ number_format($totalSaldo, 2) }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
