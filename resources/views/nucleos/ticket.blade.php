<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Núcleo {{ $nucleo->id }} - {{ $nucleo->nombre }}</title>
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
        <p class="bold">Núcleo ID: {{ $nucleo->id }} {{ $nucleo->nombre }}</p>
    </div>
    <div class="line"></div>

    {{-- DETALLE PRODUCTOS --}}
    <table>
        <tr class="bold">
            <td style="width:15%;">ID</td>
            <td style="width:40%; text-align:center;">Producto</td>
            <td style="width:30%;">Unidad</td>
            <td style="width:15%; text-align:right; padding-right:3px;">Cantidad</td>
        </tr>
        <tr><td colspan="4" style="border-top:1px solid #000;"></td></tr>

        @foreach($nucleo->detalles as $detalle)
        <tr>
            <td>{{ $detalle->producto_id }}</td>
            <td style="text-align:center;">{{ $detalle->producto_nombre }}</td>
            <td>{{ $detalle->unidad_codigo }}</td>
            <td style="text-align:right; padding-right:3px;">{{ number_format($detalle->cantidad,2) }}</td>
        </tr>
        @endforeach

        <tr><td colspan="4" style="border-top:1px solid #000;"></td></tr>
    </table>

    {{-- TOTALES --}}
    <table class="totales">
        <tr class="bold">
            <td>Total:</td>
            <td style="text-align:right;">{{ number_format($nucleo->detalles->sum('cantidad'),2) }}</td>
        </tr>
    </table>

    <div class="line"></div>
</div>
</body>
</html>