<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Trabajadores con Sueldo - Planilla</title>
    <style>
        @page {
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }

        .watermark {
            position: fixed;
            top: 20%;
            left: 5%;
            width: 600px;
            opacity: 0.08;
            z-index: -1000;
        }

        .contenido {
            position: relative;
            z-index: 1;
        }

        .logo {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 120px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-top: 10px;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
        }

        .header h3 {
            margin: 15px 0 10px 0;
            font-size: 14px;
        }

        .header p {
            margin: 2px 0;
            font-size: 12px;
        }

        .info {
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 5px 6px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            position: fixed;
            bottom: 60px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
        }
    </style>
</head>

<body>
    @include('planilla.reportes.partials.logo')
    @include('planilla.reportes.partials.watermark')
    <div class="contenido">
    <div class="header">
        <h2>{{ $empresa->razon_social ?? 'EMPRESA' }}</h2>
        <p>{{ $empresa->direccion ?? '' }}</p>
        <p>RUC: {{ $empresa->ruc ?? '' }}</p>
        <h3>REPORTE DE TRABAJADORES CON SUELDO</h3>
        <p>Fecha: {{ date('d/m/Y') }}</p>
    </div>

    <div class="info">
        <strong>Total de Trabajadores:</strong> {{ $totalRegistros }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Nombre</th>
                <th>DNI</th>
                <th class="text-right">Sueldo Planilla</th>
                <th class="text-right">Sueldo Real</th>
                {{-- <th class="text-center">Estado</th> --}}
            </tr>
        </thead>
        <tbody>
            @foreach ($empleados as $index => $emp)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $emp->nombre }}</td>
                    <td>{{ $emp->dni }}</td>
                    <td class="text-right">S/ {{ number_format((float) $emp->sueldo_planilla, 2) }}</td>
                    <td class="text-right">S/ {{ number_format((float) $emp->sueldo_real, 2) }}</td>
                    {{-- <td class="text-center">{{ ucfirst($emp->estado) }}</td> --}}
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generado: {{ date('d/m/Y H:i:s') }}
    </div>
    </div>
</body>

</html>
