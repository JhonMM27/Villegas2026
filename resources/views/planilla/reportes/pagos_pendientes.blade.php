<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Pagos Pendientes - Planilla</title>
    <style>
        @page {
            margin: 10mm;
            size: landscape;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .header h2 {
            margin: 0;
            font-size: 14px;
        }

        .header p {
            margin: 2px 0;
            font-size: 10px;
        }

        .info {
            margin-bottom: 10px;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: center;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 9px;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .signature-box {
            border: 1px solid #333;
            width: 90px;
            height: 35px;
            display: inline-block;
            vertical-align: middle;
        }

        .totals {
            margin-top: 20px;
        }

        .totals td {
            font-weight: bold;
            font-size: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>{{ $empresa->razon_social ?? 'EMPRESA' }}</h2>
        <p>{{ $empresa->direccion ?? '' }}</p>
        <p>RUC: {{ $empresa->ruc ?? '' }}</p>
        <h3>REPORTE DE PAGOS PENDIENTES - PLANILLA</h3>
        <p>Fecha: {{ date('d/m/Y') }}</p>
    </div>

    <div class="info">
        <strong>Total Pagos Pendientes:</strong> {{ $totalRegistros }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-left">EMPLEADO</th>
                <th>DNI</th>
                <th class="text-right">SUELDO</th>
                <th class="text-right">PLANILLA</th>
                <th class="text-right">ADELANTOS</th>
                <th class="text-right">H. EXTRAS</th>
                <th class="text-right">DESC. FALTAS</th>
                <th class="text-right">TOTAL A PAGAR</th>
                <th>FIRMA</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalSueldo = 0;
                $totalPlanilla = 0;
                $totalAdelantos = 0;
                $totalXPorPagar = 0;
                $totalHorasExtras = 0;
                $totalDescuentoFaltas = 0;
            @endphp
            @foreach ($pagos as $p)
                @php
                    $sueldoReal = (float) ($p->empleado->sueldo_real ?? 0);
                    $sueldoPlanilla = (float) ($p->empleado->sueldo_planilla ?? 0);
                    $disponible = $sueldoReal - $sueldoPlanilla;
                    $adelantos = (float) ($p->adelantos_calculado ?? 0);
                    $xPagar = $disponible - $adelantos;
                    $horasExtras = (float) $p->horas_extras;
                    $descuentoFaltas = (float) ($p->descuento_faltas ?? 0);

                    $totalSueldo += $sueldoReal;
                    $totalPlanilla += $sueldoPlanilla;
                    $totalAdelantos += $adelantos;
                    $totalHorasExtras += $horasExtras;
                    $totalDescuentoFaltas += $descuentoFaltas;
                    $totalXPorPagar += $xPagar + $horasExtras - $descuentoFaltas;
                @endphp
                <tr>
                    <td class="text-left">{{ $p->empleado->nombre ?? 'N/A' }}</td>
                    <td>{{ $p->empleado->dni ?? 'N/A' }}</td>
                    <td class="text-right">S/ {{ number_format($sueldoReal, 2) }}</td>
                    <td class="text-right">S/ {{ number_format($sueldoPlanilla, 2) }}</td>
                    <td class="text-right">S/ {{ number_format($adelantos, 2) }}</td>
                    <td class="text-right">S/ {{ number_format($horasExtras, 2) }}</td>
                    <td class="text-right">S/ {{ number_format($descuentoFaltas, 2) }}</td>
                    <td class="text-right">S/ {{ number_format($xPagar + $horasExtras - $descuentoFaltas, 2) }}</td>
                    <td><span class="signature-box"></span></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="text-left"><strong>TOTALES:</strong></td>
                <td></td>
                <td class="text-right"><strong>S/ {{ number_format($totalSueldo, 2) }}</strong></td>
                <td class="text-right"><strong>S/ {{ number_format($totalPlanilla, 2) }}</strong></td>
                <td class="text-right"><strong>S/ {{ number_format($totalAdelantos, 2) }}</strong></td>
                <td class="text-right"><strong>S/ {{ number_format($totalHorasExtras, 2) }}</strong></td>
                <td class="text-right"><strong>S/ {{ number_format($totalDescuentoFaltas, 2) }}</strong></td>
                <td class="text-right"><strong>S/ {{ number_format($totalXPorPagar, 2) }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>

</html>
