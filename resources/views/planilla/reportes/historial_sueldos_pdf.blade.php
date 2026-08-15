<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de sueldos - {{ $empleado->nombre }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
        h2, p { margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #999; padding: 4px; }
        th { background: #e9ecef; }
        .right { text-align: right; }
        .center { text-align: center; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <h2>{{ $empresa->razon_social }}</h2>
    <p>Historial de sueldos</p>
    <p><strong>Empleado:</strong> {{ $empleado->nombre }} · <strong>DNI:</strong> {{ $empleado->dni }}</p>
    @if ($fechaInicio || $fechaFin)
        <p class="muted">Rango: {{ $fechaInicio ? \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') : 'Inicio' }} - {{ $fechaFin ? \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') : 'Actualidad' }}</p>
    @endif
    <table>
        <thead><tr><th>Desde</th><th>Hasta</th><th>Base</th><th>Real</th><th>Planilla</th><th>Cambio</th><th>Δ Base</th><th>Δ Real</th><th>Δ Planilla</th><th>Motivo</th></tr></thead>
        <tbody>
        @forelse ($historial as $sueldo)
            <tr>
                <td>{{ $sueldo->vigente_desde->format('d/m/Y') }}</td>
                <td>{{ $sueldo->vigente_hasta?->format('d/m/Y') ?? 'Vigente' }}</td>
                <td class="right">S/ {{ number_format((float) $sueldo->sueldo_base, 2) }}</td>
                <td class="right">S/ {{ number_format((float) $sueldo->sueldo_real, 2) }}</td>
                <td class="right">S/ {{ number_format((float) $sueldo->sueldo_planilla, 2) }}</td>
                <td>{{ $sueldo->tipo_cambio }}</td>
                <td class="right">{{ number_format($sueldo->variacion_base, 2) }}</td>
                <td class="right">{{ number_format($sueldo->variacion_real, 2) }}</td>
                <td class="right">{{ number_format($sueldo->variacion_planilla, 2) }}</td>
                <td>{{ $sueldo->motivo ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="10" class="center">No hay registros.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
