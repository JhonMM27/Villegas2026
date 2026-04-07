<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Adelanto {{ $adelanto->numero_interno }}</title>
    <style>
        @page {
            margin: 2mm 0mm 2mm 0mm;
            size: 76mm auto;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }

        .ticket {
            width: 72mm;
            margin: 0 auto;
            text-align: left;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .right {
            text-align: right;
        }

        h3 {
            margin: 0;
            padding: 0;
            font-size: 12px;
        }

        p {
            margin: 0;
            padding: 0;
            font-size: 9px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        td {
            padding: 1px 0;
            vertical-align: top;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 2px 0;
        }

        .spacer {
            height: 3px;
        }
    </style>
</head>

<body>
    <div class="ticket">

        {{-- ENCABEZADO EMPRESA --}}
        <div class="center">
            <h3>{{ $empresa->razon_social }}</h3>
            <p>{!! nl2br(e($empresa->direccion)) !!}</p>
            <p>RUC: {{ $empresa->ruc }}</p>
            <p>CEL: {{ $empresa->celular }}</p>
        </div>

        <div class="spacer"></div>

        <div class="center">
            <h3 class="bold">ADELANTO N° {{ $adelanto->numero_interno }}</h3>
        </div>

        <div class="line"></div>

        {{-- DATOS DEL ADELANTO --}}
        <p><strong>Empleado:</strong> {{ $adelanto->empleado->nombre }}</p>
        <p><strong>DNI:</strong> {{ $adelanto->empleado->dni ?? '-' }}</p>
        <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($adelanto->fecha)->format('d/m/Y') }}</p>

        <div class="line"></div>
        <div class="spacer"></div>

        {{-- MONTO --}}
        <table class="totales">
            <tr class="bold">
                <td style="text-align: right; width: 70%;">TOTAL ADELANTO:</td>
                <td style="text-align: right; width: 30%;">S/ {{ number_format($adelanto->monto, 2) }}</td>
            </tr>
        </table>

        <div class="line"></div>
        <div class="spacer"></div>

        <p class="bold">DISTRIBUCIÓN DE CAJA</p>

        <table>
            <tr>
                <td class="bold">Principal:</td>
                <td class="right">S/ {{ number_format($adelanto->importe_p, 2) }}</td>
            </tr>
            <tr>
                <td class="bold">Depósito:</td>
                <td class="right">S/ {{ number_format($adelanto->importe_d, 2) }}</td>
            </tr>
            <tr>
                <td class="bold">Consorcio:</td>
                <td class="right">S/ {{ number_format($adelanto->importe_c, 2) }}</td>
            </tr>
            <tr>
                <td class="bold" style="text-decoration: underline;">Total Distribuido:</td>
                <td class="right" style="text-decoration: underline;">S/ {{ number_format($adelanto->importe_p + $adelanto->importe_d + $adelanto->importe_c, 2) }}</td>
            </tr>
        </table>

        @if($adelanto->observaciones)
        <div class="line"></div>
        <div class="spacer"></div>
        <p><strong>Observaciones:</strong> {{ $adelanto->observaciones }}</p>
        @endif

        <div class="line"></div>
        <div class="center">
            <p><em>GRACIAS POR SU PREFERENCIA</em></p>
        </div>

        {{-- Espacio final para el corte de papel --}}
        <div style="height: 4mm;"></div>

    </div>
</body>

</html>