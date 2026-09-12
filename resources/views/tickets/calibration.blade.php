<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Calibración Epson TM-U220</title>
    @include('tickets.styles')
</head>
<body>
<div class="ticket">
    <h3>PRUEBA EPSON TM-U220</h3>
    <p>Rollo 76 mm / contenido 63 mm</p>
    <p>Imprimir a tamaño real: 100 %.</p>
    <p>La línea debe medir 60 mm:</p>
    <div style="width:60mm; height:3mm; border-bottom:0.5pt solid #000;"></div>
    <p>0 <span style="float:right; margin-right:3mm;">60 mm</span></p>
    @foreach(['DejaVu Sans', 'DejaVu Sans Mono'] as $font)
        @foreach([10, 11, 12] as $size)
        <div class="ticket-item">
            <p class="bold">{{ $font }} / {{ $size }} pt</p>
            <div style="font-family:'{{ $font }}'; font-size:{{ $size }}pt; font-weight:normal;">
                <p>0123456789</p>
                <p>6.00 / 8.00</p>
                <p>0.00 / 9.00</p>
                <p>16.00 / 18.00 / 38.00</p>
                <p>938.00 / 1,000.00</p>
                <p>Ñ ñ Á É Í Ó Ú / S/</p>
            </div>
        </div>
        @endforeach
    @endforeach
    <p>Compare original y copia.</p>
</div>
<div id="ticket-end"></div>
</body>
</html>
