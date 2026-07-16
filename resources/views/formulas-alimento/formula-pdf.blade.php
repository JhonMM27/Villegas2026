<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Fórmula Vacuno — {{ $data['formula']['nombre'] }}</title>
    <style>
        @page { margin: 30px 25px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #212529; }
        h1, h2, h3 { margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 6px; vertical-align: top; }
        th { text-align: left; background: #f1f3f5; font-weight: bold; border-bottom: 1px solid #dee2e6; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .section { margin-bottom: 14px; }
        .section-title {
            background: #0d6efd; color: #fff;
            padding: 5px 8px; font-size: 11px; font-weight: bold;
            border-radius: 3px; margin-bottom: 4px;
        }
        .header-box {
            border: 1px solid #0d6efd;
            padding: 10px; border-radius: 4px; margin-bottom: 14px;
        }
        .header-box table th { background: transparent; border: 0; padding: 2px 4px; }
        .header-box table td { border: 0; padding: 2px 4px; }
        .totals { background: #f8f9fa; font-weight: bold; }
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
        .nutricion-box { border: 1px solid #0dcaf0; border-radius: 4px; padding: 8px; }
        .costos-box { border: 1px solid #198754; border-radius: 4px; padding: 8px; }
        .footer { font-size: 8px; color: #6c757d; text-align: center; margin-top: 12px; }
    </style>
</head>
<body>
    <h1 style="font-size: 16px; text-align: center; margin-bottom: 8px;">
        FÓRMULA DE ALIMENTO — VACUNOS
    </h1>

    @php
        $f = $data['formula'];
        $c = $data['resumen_costos'];
        $n = $data['resumen_nutricional'];
        $totalKg = collect($data['ingredientes'])->sum('cantidad_kg');
        $totalCosto = collect($data['ingredientes'])->sum('costo');
    @endphp

    <div class="header-box">
        <table style="border-collapse: separate; border-spacing: 8px 0;">
            <tr>
                <td style="width: 35%; border-right: 1px solid #0d6efd; padding: 2px 8px 2px 0;">
                    <small style="color: #6c757d; text-transform: uppercase; font-size: 7.5px; letter-spacing: 0.5px;">Nombre</small><br>
                    <strong style="font-size: 12px;">{{ $f['nombre'] }}</strong>
                </td>
                <td style="width: 15%; border-right: 1px solid #0d6efd; padding: 2px 8px;">
                    <small style="color: #6c757d; text-transform: uppercase; font-size: 7.5px; letter-spacing: 0.5px;">Kg / Saco</small><br>
                    <strong>{{ number_format($f['kg_saco'], 2) }}</strong>
                </td>
                <td style="width: 20%; border-right: 1px solid #0d6efd; padding: 2px 8px;">
                    <small style="color: #6c757d; text-transform: uppercase; font-size: 7.5px; letter-spacing: 0.5px;">Fecha</small><br>
                    <strong>{{ $f['fecha'] ?? '—' }}</strong>
                </td>
                <td style="width: 30%; padding: 2px 0 2px 8px; text-align: right;">
                    <small style="color: #6c757d; text-transform: uppercase; font-size: 7.5px; letter-spacing: 0.5px;">Precio Venta</small><br>
                    <strong style="font-size: 12px; color: #198754;">S/ {{ number_format($f['precio_venta'], 2) }}</strong>
                </td>
            </tr>
            <tr>
                <td colspan="4" style="padding: 6px 0 2px 0; border-top: 1px solid #dee2e6;">
                    <small style="color: #6c757d; text-transform: uppercase; font-size: 7.5px; letter-spacing: 0.5px;">Descripción</small><br>
                    <span>{{ $f['descripcion'] ?? '—' }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Ingredientes</div>
        <table class="table-bordered">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 24%;">Ingrediente</th>
                    <th style="width: 14%;">Clasificación</th>
                    <th style="width: 12%;">Procedencia</th>
                    <th style="width: 12%;">Nutriente</th>
                    <th class="text-end" style="width: 7%;">Aporte</th>
                    <th class="text-end" style="width: 9%;">S/ Kg</th>
                    <th class="text-end" style="width: 9%;">Cantidad (kg)</th>
                    <th class="text-end" style="width: 9%;">Costo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['ingredientes'] as $i => $ing)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td><strong>{{ $ing['ingrediente'] }}</strong></td>
                        <td>{{ $ing['clasificacion'] ?? '—' }}</td>
                        <td>{{ $ing['procedencia'] ?? '—' }}</td>
                        <td>{{ $ing['nutriente'] ?? '—' }}</td>
                        <td class="text-end">{{ number_format((float)($ing['aporte'] ?? 0), 2) }}</td>
                        <td class="text-end">{{ number_format((float)$ing['precio_kg'], 4) }}</td>
                        <td class="text-end">{{ number_format((float)$ing['cantidad_kg'], 2) }}</td>
                        <td class="text-end">{{ number_format((float)$ing['costo'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="totals">
                    <td colspan="7" class="text-end">TOTAL</td>
                    <td class="text-end">{{ number_format($totalKg, 2) }}</td>
                    <td class="text-end">{{ number_format($totalCosto, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <table style="margin-top: 6px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 6px;">
                <div class="costos-box">
                    <div class="section-title" style="background: #198754;">Resumen de Costos</div>
                    <table>
                        <tr><td>S/ Tonelada</td><td class="text-end"><strong>{{ number_format($c['costo_tonelada'], 2) }}</strong></td></tr>
                        <tr><td>S/ Kg</td><td class="text-end"><strong>{{ number_format($c['costo_kg'], 4) }}</strong></td></tr>
                        <tr><td>&nbsp;&nbsp;+ Saco vacío</td><td class="text-end">{{ number_format($f['saco_vacio'], 2) }}</td></tr>
                        <tr><td>&nbsp;&nbsp;+ Mano de obra</td><td class="text-end">{{ number_format($f['mano_obra'], 2) }}</td></tr>
                        <tr><td>&nbsp;&nbsp;+ Energía</td><td class="text-end">{{ number_format($f['energia'], 2) }}</td></tr>
                        <tr><td>&nbsp;&nbsp;+ Merma</td><td class="text-end">{{ number_format($f['merma'], 2) }}</td></tr>
                        <tr class="totals"><td>Costo / Saco</td><td class="text-end">{{ number_format($c['costo_saco'], 2) }}</td></tr>
                        <tr><td>Ganancia / Saco</td><td class="text-end">{{ number_format($c['ganancia_saco'], 2) }}</td></tr>
                        <tr><td>Margen</td><td class="text-end">{{ number_format($c['margen_porcentaje'], 2) }} %</td></tr>
                    </table>
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 6px;">
                <div class="nutricion-box">
                    <div class="section-title" style="background: #0dcaf0;">Aporte Nutricional (Base 1000 kg)</div>
                    <table>
                        <tr><td>Materia Seca</td><td class="text-end"><strong>{{ number_format($n['materia_seca'], 4) }}</strong></td></tr>
                        <tr><td>Proteína Cruda</td><td class="text-end"><strong>{{ number_format($n['proteina_cruda'], 4) }}</strong></td></tr>
                        <tr><td>ENL (Mcal/kg)</td><td class="text-end"><strong>{{ number_format($n['enl'], 4) }}</strong></td></tr>
                        <tr><td>FDN</td><td class="text-end"><strong>{{ number_format($n['fdn'], 4) }}</strong></td></tr>
                        <tr><td>Grasa</td><td class="text-end"><strong>{{ number_format($n['grasa'], 4) }}</strong></td></tr>
                        <tr><td>Almidón</td><td class="text-end"><strong>{{ number_format($n['almidon'], 4) }}</strong></td></tr>
                        <tr><td>Azúcar</td><td class="text-end"><strong>{{ number_format($n['azucar'], 4) }}</strong></td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} — Consorcios Villegas
    </div>
</body>
</html>
