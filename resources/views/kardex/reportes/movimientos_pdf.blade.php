<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Movimientos</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #000; padding: 4px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .text-end { text-align: right; }
        .header { text-align: center; margin-bottom: 20px; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Reporte de Movimientos</h2>
        <p>Desde: {{ $fechaInicio->format('d/m/Y') }} - Hasta: {{ $fechaFin->format('d/m/Y') }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Producto</th>
                <th>Emp.</th>
                <th>Cant.</th>
                <th>Cant. kg</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Costo Unit.</th>
                <th>Costo Total</th>
                <th>Stock Nuevo</th>
                <th>Costo Nuevo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportes as $r)
                <tr>
                    <td>{{ $r->fecha->format('d/m/Y H:i') }}</td>
                    <td>{{ $r->tipo }}</td>
                    <td>{{ $r->producto_nombre }}</td>
                    <td class="text-end">{{ number_format($r->empaque, 2) }}</td>
                    <td class="text-end">{{ number_format($r->cantidad, 2) }}</td>
                    <td class="text-end">{{ number_format($r->cantidad_kg, 2) }}</td>
                    <td class="text-end">{{ number_format($r->entrada, 2) }}</td>
                    <td class="text-end">{{ number_format($r->salida, 2) }}</td>
                    <td class="text-end">{{ number_format($r->costo_unitario, 4) }}</td>
                    <td class="text-end">{{ number_format($r->costo_total, 4) }}</td>
                    <td class="text-end fw-bold">{{ number_format($r->stock_nuevo, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($r->costo_nuevo, 4) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
