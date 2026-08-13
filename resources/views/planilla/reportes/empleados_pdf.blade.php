<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Empleado - Planilla</title>
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
            margin-bottom: 15px;
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
            margin-bottom: 10px;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: left;
            font-size: 10px;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 9px;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-secondary {
            background-color: #e9ecef;
            color: #495057;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
        }

        .resumen-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .resumen-row {
            display: table-row;
        }

        .resumen-cell {
            display: table-cell;
            padding: 4px 8px;
            border: 1px solid #ddd;
            width: 25%;
        }

        .two-col {
            width: 100%;
        }

        .two-col td {
            vertical-align: top;
        }

        .section-title {
            font-weight: bold;
            background-color: #e9ecef;
            padding: 4px;
            margin-bottom: 5px;
        }

        .empleado-block {
            page-break-inside: avoid;
            margin-bottom: 20px;
        }

        .empleado-header {
            background-color: #e9ecef;
            padding: 6px;
            margin-bottom: 8px;
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
            <h3>BOLETA DE PAGO</h3>
            <p>Fecha: {{ date('d/m/Y') }}</p>
            @if (isset($fechaInicio) && isset($fechaFin))
                <p style="margin: 2px 0;"><strong>Rango de Fechas:</strong>
                    {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} al
                    {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
                    ({{ $resumen['dias_en_rango'] ?? 30 }} días)
                </p>
            @endif
        </div>

        @if (isset($empleado))
            {{-- SINGLE EMPLEADO --}}
            <div class="info">
                <strong>Empleado:</strong> {{ $empleado->nombre }}<br>
                <strong>DNI:</strong> {{ $empleado->dni ?? '--' }} |
                <strong>Teléfono:</strong> {{ $empleado->telefono ?? '--' }}
            </div>

            <div class="section-title">RESUMEN DE SUELDO</div>
            <div class="resumen-grid">
                <div class="resumen-row">
                    <div class="resumen-cell">
                        <strong>Total Sueldo Planilla:</strong><br>
                        S/ {{ number_format($resumen['total_sueldo_planilla'] ?? 0, 2) }}
                    </div>
                    <div class="resumen-cell">
                        <strong>Total Sueldo Real:</strong><br>
                        S/ {{ number_format($resumen['total_sueldo_real'] ?? 0, 2) }}
                    </div>
                    <div class="resumen-cell">
                        <strong>Total Adelantos:</strong><br>
                        S/ {{ number_format($resumen['total_adelantos'] ?? 0, 2) }}
                    </div>
                    @if (($resumen['total_pendiente'] ?? 0) > 0)
                        <div class="resumen-cell">
                            <strong>Total Pendiente:</strong><br>
                            S/ {{ number_format($resumen['total_pendiente'], 2) }}
                        </div>
                    @endif
                </div>
            </div>

            <table class="two-col">
                <tr>
                    <td style="width: 50%; padding-right: 10px;">
                        <div class="section-title">DETALLE DE ADELANTOS</div>
                        @if ($adelantos->count() > 0)
                            <table>
                                <thead>
                                    <tr>
                                        <th>N° Int.</th>
                                        <th>Fecha</th>
                                        <th class="text-right">Monto</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($adelantos as $a)
                                        <tr>
                                            <td>{{ $a->numero_interno }}</td>
                                            <td>{{ \Carbon\Carbon::parse($a->fecha)->format('d/m/Y') }}</td>
                                            <td class="text-right">S/ {{ number_format((float) $a->monto, 2) }}</td>
                                            <td>{{ $a->observaciones ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th colspan="1" class="text-right">TOTAL:</th>
                                        <th class="text-right">S/ {{ number_format($adelantos->sum('monto'), 2) }}</th>
                                        <th></th>

                                    </tr>
                                </tfoot>
                            </table>
                        @else
                            <p style="color: #666; font-size: 10px;">No hay adelantos registrados</p>
                        @endif
                    </td>
                    <td style="width: 50%; padding-left: 10px;">
                        <div class="section-title">RESUMEN DE PAGOS</div>
                        <table>
                            <tbody>
                                <tr>
                                    <td>Cantidad de Períodos:</td>
                                    <td class="text-right fw-bold">{{ $pagos->count() }}</td>
                                </tr>
                                <tr>
                                    <td>Total Sueldo Base (Disponible):</td>
                                    <td class="text-right fw-bold">S/
                                        {{ number_format($resumen['total_sueldo_base'] ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Total Horas Extras:</td>
                                    <td class="text-right fw-bold">S/
                                        {{ number_format($resumen['total_horas_extras'] ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Total Días Faltantes:</td>
                                    <td class="text-right fw-bold" style="color: red;">
                                        {{ number_format((float) ($resumen['total_dias_faltas'] ?? 0), 2) }} - S/
                                        {{ number_format((float) ($resumen['total_descuento_faltas'] ?? 0), 2) }}
                                    </td>
                                </tr>
                                @if (($resumen['total_cts_planilla'] ?? 0) > 0)
                                    <tr>
                                        <td>CTS (Depósito)</td>
                                        <td class="text-right fw-bold">S/
                                            {{ number_format($resumen['total_cts_planilla'], 2) }}</td>
                                    </tr>
                                @endif
                                @if (($resumen['total_cts_sueldo_real'] ?? 0) > 0)
                                    <tr>
                                        <td>CTS</td>
                                        <td class="text-right fw-bold">S/
                                            {{ number_format($resumen['total_cts_sueldo_real'], 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td>Total General a Pagar ({{ $resumen['dias_en_rango'] ?? 30 }} días):</td>
                                    <td class="text-right fw-bold">S/
                                        {{ number_format($resumen['total_proporcional'] ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Total Pagado:</td>
                                    <td class="text-right fw-bold" style="color: green;">S/
                                        {{ number_format($resumen['total_pagado'] ?? 0, 2) }}</td>
                                </tr>
                                @if (($resumen['total_pendiente'] ?? 0) > 0)
                                    <tr>
                                        <td>Total Pendiente:</td>
                                        <td class="text-right fw-bold" style="color: orange;">S/
                                            {{ number_format($resumen['total_pendiente'], 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="section-title" style="margin-top: 15px;">PAGOS POR PERÍODO</div>
            @if ($pagos->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th class="text-center">ID</th>
                            <th>Período</th>
                            <th class="text-right">Sueldo Planilla</th>
                            <th class="text-right">Sueldo Real</th>
                            <th class="text-right">Sueldo Base</th>
                            <th class="text-right">H. Extras</th>
                            <th class="text-right">Días Faltas</th>
                            <th class="text-right">Desc. Faltas</th>
                            <th class="text-right">Total Pagar</th>
                            <th class="text-center">Estado</th>
                            <th>Fecha Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pagos as $pago)
                            <tr>
                                <td class="text-center">{{ $pago->id }}</td>
                                <td>{{ str_pad($pago->mes, 2, '0', STR_PAD_LEFT) }}/{{ $pago->anio }}</td>
                                <td class="text-right">S/
                                    {{ number_format((float) ($pago->sueldo_planilla_proporcional ?? 0), 2) }}
                                </td>
                                <td class="text-right">S/ {{ number_format((float) $empleado->sueldo_real, 2) }}</td>
                                <td class="text-right">S/ {{ number_format((float) $pago->sueldo_base, 2) }}</td>
                                <td class="text-right">S/ {{ number_format((float) $pago->horas_extras, 2) }}</td>
                                <td class="text-right">{{ number_format((float) ($pago->dias_faltados ?? 0), 2) }}</td>
                                <td class="text-right" style="color: red;">S/
                                    {{ number_format((float) ($pago->descuento_faltas ?? 0), 2) }}</td>
                                <td class="text-right">S/
                                    {{ number_format((float) ($pago->monto_proporcional ?? 0), 2) }}</td>
                                <td class="text-center">
                                    @if ($pago->estado === 'pagado')
                                        <span class="badge badge-success">Pagado</span>
                                    @else
                                        <span class="badge badge-warning">Pendiente</span>
                                    @endif
                                </td>
                                <td>{{ $pago->fecha_pago ? \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-right">TOTALES:</th>
                            <th></th>
                            <th></th>
                            <th class="text-right">S/ {{ number_format($resumen['total_sueldo_base'] ?? 0, 2) }}</th>
                            <th class="text-right">S/ {{ number_format($resumen['total_horas_extras'] ?? 0, 2) }}</th>
                            <th></th>
                            <th class="text-right">S/ {{ number_format($resumen['total_descuento_faltas'] ?? 0, 2) }}
                            </th>
                            <th class="text-right">S/ {{ number_format($resumen['total_proporcional'] ?? 0, 2) }}</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            @else
                <p class="text-center" style="color: #666; font-size: 10px;">No hay pagos registrados</p>
            @endif
        @else
            {{-- ALL EMPLEADOS --}}
            <div class="info">
                <strong>Total Empleados:</strong> {{ $pagos->groupBy('empleado_id')->count() }} |
                <strong>Total Pagos:</strong> {{ $totalRegistros }}
            </div>

            @php
                $groupedPagos = $pagos->groupBy('empleado_id');
            @endphp

            @foreach ($groupedPagos as $empleadoId => $empPagos)
                <div class="empleado-block">
                    @php
                        $emp = $empPagos->first()->empleado;
                    @endphp
                    <div class="empleado-header">
                        <strong>{{ $emp->nombre }} ({{ $emp->dni }})</strong> -
                        Sueldo Planilla: S/ {{ number_format((float) $emp->sueldo_planilla, 2) }} |
                        Sueldo Real: S/ {{ number_format((float) $emp->sueldo_real, 2) }}
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th class="text-center">ID</th>
                                <th>Período</th>
                                <th class="text-right">Sueldo Base</th>
                                <th class="text-right">H. Extras</th>
                                <th class="text-right">Días Faltas</th>
                                <th class="text-right">Desc. Faltas</th>
                                <th class="text-right">Total Pagar</th>
                                <th class="text-center">Estado</th>
                                <th>Fecha Pago</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($empPagos as $pago)
                                <tr>
                                    <td class="text-center">{{ $pago->id }}</td>
                                    <td>{{ str_pad($pago->mes, 2, '0', STR_PAD_LEFT) }}/{{ $pago->anio }}</td>
                                    <td class="text-right">S/ {{ number_format((float) $pago->sueldo_base, 2) }}</td>
                                    <td class="text-right">S/ {{ number_format((float) $pago->horas_extras, 2) }}</td>
                                    <td class="text-right">{{ number_format((float) ($pago->dias_faltados ?? 0), 2) }}
                                    </td>
                                    <td class="text-right" style="color: red;">S/
                                        {{ number_format((float) ($pago->descuento_faltas ?? 0), 2) }}</td>
                                    <td class="text-right">S/
                                        {{ number_format((float) ($pago->monto_proporcional ?? 0), 2) }}</td>
                                    <td class="text-center">
                                        @if ($pago->estado === 'pagado')
                                            <span class="badge badge-success">Pagado</span>
                                        @else
                                            <span class="badge badge-warning">Pendiente</span>
                                        @endif
                                    </td>
                                    <td>{{ $pago->fecha_pago ? \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-right">SUBTOTAL:</th>
                                <th class="text-right">S/ {{ number_format($empPagos->sum('sueldo_base'), 2) }}</th>
                                <th class="text-right">S/ {{ number_format($empPagos->sum('horas_extras'), 2) }}</th>
                                <th></th>
                                <th class="text-right">S/ {{ number_format($empPagos->sum('descuento_faltas'), 2) }}
                                </th>
                                <th class="text-right">S/ {{ number_format($empPagos->sum('total_pagar'), 2) }}</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endforeach

            <div class="section-title" style="margin-top: 15px;">RESUMEN GENERAL</div>
            <table>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-right">TOTAL GENERAL:</th>
                        <th class="text-right">S/ {{ number_format($pagos->sum('sueldo_base'), 2) }}</th>
                        <th class="text-right">S/ {{ number_format($pagos->sum('horas_extras'), 2) }}</th>
                        <th class="text-right">S/ {{ number_format($pagos->sum('adelantos'), 2) }}</th>
                        <th class="text-right">S/ {{ number_format($pagos->sum('total_proporcional'), 2) }}
                        </th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        @endif

        @if (isset($empleado))
            <div style="position: fixed; bottom: 60px; left: 0; right: 0; page-break-inside: avoid;">
                <div style="width: 100%; display: table; border-collapse: collapse;">
                    <div style="display: table-row;">
                        <div style="display: table-cell; width: 45%; text-align: center; padding: 0 20px;">
                            <div style="border-top: 1px solid #333; height: 40px;"></div>
                            <p style="margin: 5px 0 0 0; font-size: 10px;">Firma del Empleado</p>
                            <p style="margin: 5px 0; font-size: 10px;"><strong>{{ $empleado->nombre }}</strong></p>
                            <p style="margin: 0; font-size: 10px;">DNI: {{ $empleado->dni }}</p>
                        </div>
                        <div style="display: table-cell; width: 10%;"></div>
                        <div style="display: table-cell; width: 45%; text-align: center; padding: 0 20px;">
                            <div style="border-top: 1px solid #333; height: 40px;"></div>
                            <p style="margin: 5px 0 0 0; font-size: 10px;">Firma del Empleador</p>
                            <p style="margin: 5px 0; font-size: 10px;"><strong>Cesar Villegas Guevara</strong></p>
                            <p style="margin: 0; font-size: 10px;">DNI: 42874639</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="footer">
            Generado el {{ date('d/m/Y H:i:s') }}
        </div>
    </div>
</body>

</html>
