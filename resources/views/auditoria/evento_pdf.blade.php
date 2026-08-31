<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría - {{ $accion }} - {{ $evento->registro_referencia }}</title>
    <style>
        @page { margin: 22px 28px 34px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #263238; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        .header { width: 100%; margin-bottom: 12px; border-bottom: 3px solid {{ $evento->tipo_evento === 'anulacion' ? '#b91c1c' : '#d97706' }}; }
        .header td { padding: 0 0 9px; vertical-align: bottom; }
        .company { color: #0f5180; font-size: 15px; font-weight: bold; }
        .company-data { margin-top: 3px; color: #667580; font-size: 8px; }
        .title { text-align: right; }
        .title h1 { margin: 0; color: #1f2937; font-size: 18px; }
        .protected { display: inline-block; margin-top: 4px; padding: 3px 8px; color: #31566d; border: 1px solid #b8d3e2; border-radius: 10px; background: #edf7fc; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .event-banner { margin-bottom: 10px; padding: 8px 10px; border-left: 4px solid {{ $evento->tipo_evento === 'anulacion' ? '#b91c1c' : '#d97706' }}; background: {{ $evento->tipo_evento === 'anulacion' ? '#fef2f2' : '#fff7ed' }}; }
        .event-banner strong { color: {{ $evento->tipo_evento === 'anulacion' ? '#991b1b' : '#9a4d08' }}; font-size: 12px; }
        .event-code { float: right; color: #667580; font-size: 8px; }
        .meta { width: 100%; margin-bottom: 10px; border-collapse: separate; border-spacing: 4px; }
        .meta td { width: 25%; padding: 6px 7px; border: 1px solid #d9e1e6; border-radius: 3px; background: #f8fafb; vertical-align: top; }
        .label { display: block; margin-bottom: 2px; color: #657680; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .reason { margin: 0 4px 12px; padding: 7px 9px; border: 1px solid #d9e1e6; border-radius: 3px; background: #f8fafb; }
        .section-title { margin: 10px 0 5px; padding-bottom: 4px; color: #204d68; border-bottom: 1px solid #b8c9d3; font-size: 11px; font-weight: bold; }
        .count { display: inline-block; margin-left: 5px; padding: 1px 5px; color: #0f5180; border-radius: 8px; background: #e4f1f8; font-size: 7px; }
        table.changes { width: 100%; margin-bottom: 8px; border-collapse: collapse; table-layout: fixed; }
        table.changes thead { display: table-header-group; }
        table.changes tr { page-break-inside: avoid; }
        table.changes th { padding: 5px 6px; color: #fff; border: 1px solid #376b89; background: #376b89; font-size: 7px; text-align: left; text-transform: uppercase; }
        table.changes td { padding: 5px 6px; border: 1px solid #cfd9df; vertical-align: top; overflow-wrap: break-word; }
        .field { width: 31%; font-weight: bold; }
        .type { width: 12%; text-align: center; }
        .old, .new { width: 28.5%; }
        .old { color: #8f2525; background: #fff5f5; }
        .new { color: #146c43; background: #f0fdf4; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 5px; border-radius: 8px; font-size: 6px; font-weight: bold; text-transform: uppercase; }
        .badge-modificado { color: #854d0e; background: #fef3c7; }
        .badge-agregado { color: #166534; background: #dcfce7; }
        .badge-eliminado { color: #991b1b; background: #fee2e2; }
        .empty { padding: 12px; color: #667580; border: 1px solid #d9e1e6; background: #f8fafb; text-align: center; }
        .footer-note { margin-top: 10px; color: #70808a; font-size: 7px; text-align: center; }
    </style>
</head>
<body>
@php
    $cambios = is_array($evento->cambios) ? $evento->cambios : [];
    $cabecera = is_array($cambios['cabecera'] ?? null) ? $cambios['cabecera'] : [];
    $detalles = is_array($cambios['detalles'] ?? null) ? $cambios['detalles'] : [];
    $textoSeguro = static fn ($valor) => str_replace(['—', '–'], '-', (string) $valor);
    $formatear = static function ($valor, ?string $formato) use ($textoSeguro): string {
        if ($valor === null || $valor === '') {
            return '-';
        }
        if (is_array($valor) || is_object($valor)) {
            return $textoSeguro(json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-');
        }
        if ($formato === 'moneda' && is_numeric($valor)) {
            return 'S/ '.number_format((float) $valor, 2, '.', ',');
        }
        if (in_array($formato, ['cantidad', 'costo'], true) && is_numeric($valor)) {
            return rtrim(rtrim(number_format((float) $valor, 4, '.', ','), '0'), '.');
        }
        if ($formato === 'fecha' && preg_match('/^(\d{4})-(\d{2})-(\d{2})(.*)$/', (string) $valor, $fecha)) {
            return $fecha[3].'/'.$fecha[2].'/'.$fecha[1].$fecha[4];
        }

        return $textoSeguro($valor);
    };
    $tipos = ['modificado' => 'Modificado', 'agregado' => 'Agregado', 'eliminado' => 'Eliminado'];
@endphp

<table class="header">
    <tr>
        <td>
            <div class="company">{{ $empresa->razon_social }}</div>
            <div class="company-data">RUC {{ $empresa->ruc }} | {{ $empresa->direccion }}</div>
        </td>
        <td class="title">
            <h1>Reporte de auditoría</h1>
            <span class="protected">Registro protegido e inmutable</span>
        </td>
    </tr>
</table>

<div class="event-banner">
    <span class="event-code">Evento #{{ $evento->id }}</span>
    <strong>{{ $accion }}{{ $evento->numero_rectificacion ? ' '.$evento->numero_rectificacion : '' }}</strong>
    <div>{{ $moduloNombre }} - {{ $textoSeguro($evento->registro_referencia) }}</div>
</div>

<table class="meta">
    <tr>
        <td><span class="label">Fecha</span>{{ $evento->created_at?->format('d/m/Y') ?? '-' }}</td>
        <td><span class="label">Hora</span>{{ $evento->created_at?->format('H:i:s') ?? '-' }}</td>
        <td><span class="label">Realizado por</span>{{ $evento->user_nombre ?: 'Sistema' }}</td>
        <td><span class="label">Registro afectado</span>{{ $textoSeguro($evento->registro_referencia) }}</td>
    </tr>
</table>

<div class="reason"><span class="label">Motivo</span>{{ filled($evento->motivo) ? $textoSeguro($evento->motivo) : 'Sin motivo registrado' }}</div>

@foreach ([['Cambios de cabecera', $cabecera], ['Cambios en productos y detalles', $detalles]] as [$titulo, $lista])
    @if (count($lista))
        <div class="section-title">{{ $titulo }} <span class="count">{{ count($lista) }}</span></div>
        <table class="changes">
            <thead>
                <tr><th class="field">Campo</th><th class="type">Tipo</th><th class="old">Valor anterior</th><th class="new">Valor nuevo</th></tr>
            </thead>
            <tbody>
                @foreach ($lista as $cambio)
                    @php $tipo = $cambio['tipo'] ?? 'modificado'; @endphp
                    <tr>
                        <td class="field">{{ $textoSeguro($cambio['campo'] ?? 'Campo') }}</td>
                        <td class="type"><span class="badge badge-{{ $tipo }}">{{ $tipos[$tipo] ?? 'Cambio' }}</span></td>
                        <td class="old">{{ $formatear($cambio['anterior'] ?? null, $cambio['formato'] ?? null) }}</td>
                        <td class="new">{{ $formatear($cambio['nuevo'] ?? null, $cambio['formato'] ?? null) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endforeach

@if (! count($cabecera) && ! count($detalles))
    <div class="empty">La operación se registró sin diferencias adicionales de datos.</div>
@endif

<div class="footer-note">Documento generado el {{ now()->format('d/m/Y H:i:s') }}. Este reporte es de consulta y no modifica el registro de auditoría.</div>

<script type="text/php">
    if (isset($pdf)) {
        $pdf->page_text(720, 565, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 7, array(0.38, 0.45, 0.49));
    }
</script>
</body>
</html>
