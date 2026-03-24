<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Preparada {{ $preparada->id }}</title>
    <style>
        @page { margin: 4mm; size: 80mm auto; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; margin: 0; padding: 0; }
        .ticket { width: 72mm; margin: 0; padding: 0; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        h3 { margin: 0 0 2px 0; }
        p { margin: 1px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; vertical-align: top; }
        .line { border-top: 1px dashed #000; margin: 3px 0; }
        .totales td { padding: 1px 0; }
    </style>
</head>
<body>
<div class="ticket">

    {{-- ENCABEZADO EMPRESA --}}
    <div class="center">
        <h3 class="bold">{{ $empresa->razon_social }}</h3>
        <p>RUC: {{ $empresa->ruc }}</p>
        <p class="bold">Preparada ID: {{ $preparada->id }}</p>
    </div>
    <div class="line"></div>

    {{-- DATOS CLIENTE --}}
    <p><strong>Cliente:</strong> {{ $preparada->cliente_id }} - {{ $preparada->cliente_nombre }}</p>
    <p><strong>Dirección:</strong> {{ $preparada->cliente_direccion ?? '-' }}</p>
    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($preparada->fecha)->format('d/m/Y H:i') }}</p>
    <p><strong>Preparada:</strong> {{ $preparada->producto_nombre }} | Empaque: {{ $preparada->producto_empaque }}</p>
    <p><strong>Total (Sacos / KG / Soles):</strong> {{ $preparada->ingreso_saco }} / {{ $preparada->ingreso_kg }} / {{ $preparada->ingreso_soles }}</p>
    <div class="line"></div>

    {{-- DETALLE PRODUCTOS --}}
    <table>
        <tr class="bold">
            <td style="width:40%;">Producto</td>
            <td style="width:15%; text-align:center;">Empaque</td>
            <td style="width:15%; text-align:right; padding-right:3px;">Salida (KG)</td>
            <td style="width:10%; text-align:right;">P.Unit.</td>
        </tr>
        <tr><td colspan="4" style="border-top:1px solid #000;"></td></tr>

        @foreach($preparada->detalles as $detalle)
        <tr style="{{ trim($preparada->producto_nombre) == trim($detalle->producto_nombre) ? 'background-color:#d3d3d3;' : '' }}">
            <td>{{ $detalle->producto_nombre }}</td>
            <td style="text-align:center;">{{ $detalle->producto_empaque }}</td>
            <td style="text-align:right; padding-right:3px;">{{ number_format($detalle->salida_kg,2) }}</td>
            <td style="text-align:right;">{{ number_format($detalle->precio_unitario,2) }}</td>
        </tr>
        @endforeach

        <tr><td colspan="4" style="border-top:1px solid #000;"></td></tr>
    </table>

    {{-- TOTALES --}}
    <table class="totales">
        <tr class="bold">
            <td>Kilos por saco:</td>
            <td style="text-align:right;">{{ number_format($preparada->producto_empaque,2) }}</td>
        </tr>
        <tr class="bold">
            <td>Costo por saco:</td>
            <td style="text-align:right;">{{ number_format($preparada->costo_unitario,2) }}</td>
        </tr>
        <tr class="bold">
            <td>Cantidad sacos:</td>
            <td style="text-align:right;">{{ number_format($preparada->ingreso_saco,2) }}</td>
        </tr>
        <tr class="bold">
            <td>Costo Total:</td>
            <td style="text-align:right;">{{ number_format($preparada->ingreso_soles,2) }}</td>
        </tr>
    </table>

    <div class="line"></div>
</div>
</body>
</html>