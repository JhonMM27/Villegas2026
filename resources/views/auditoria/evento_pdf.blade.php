<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría - {{ $accion }} - {{ $evento->registro_referencia }}</title>
    <style>
        @page { margin: 28px 32px 42px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; background: #fff; font-family: DejaVu Sans, sans-serif; font-size: 8.5px; line-height: 1.35; }
        .header { width: 100%; margin-bottom: 14px; border-bottom: 3px solid #0f5180; }
        .header td { padding: 0 0 10px; vertical-align: bottom; }
        .company { color: #0f5180; font-size: 15px; font-weight: bold; letter-spacing: .15px; }
        .company-data { margin-top: 4px; color: #000; font-size: 8px; }
        .title { text-align: right; }
        .title h1 { margin: 0; color: #1f2937; font-size: 18px; font-weight: bold; }
        .protected { display: inline-block; margin-top: 5px; padding: 3px 8px; color: #31566d; border: 1px solid #9bc5dc; background: #edf7fc; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .event-banner { margin-bottom: 10px; padding: 9px 10px; border: 2px solid #d97706; border-left-width: 6px; background: #fff7ed; }
        .event-banner strong { color: #854d0e; font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .event-anulacion .event-banner { border-color: #b91c1c; background: #fef2f2; }
        .event-anulacion .event-banner strong { color: #991b1b; }
        .event-reference { margin-top: 3px; font-weight: bold; }
        .event-code { float: right; color: #000; font-size: 8px; font-weight: bold; }
        .meta { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .meta td { width: 50%; padding: 7px 8px; border: 1px solid #000; background: #fff; vertical-align: top; }
        .meta-value { display: block; color: #000; font-size: 9px; font-weight: bold; }
        .label { display: block; margin-bottom: 3px; color: #000; font-size: 7px; font-weight: bold; letter-spacing: .2px; text-transform: uppercase; }
        .reason { margin-bottom: 12px; padding: 8px 9px; border: 1px solid #d97706; border-left-width: 4px; background: #fffaf0; }
        .event-anulacion .reason { border-color: #b91c1c; background: #fff7f7; }
        .reason-value { color: #000; font-size: 9px; font-weight: bold; }
        .section-title { margin: 12px 0 5px; padding: 5px 7px; color: #fff; border: 1px solid #d97706; background: #d97706; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .event-anulacion .section-title { border-color: #b91c1c; background: #b91c1c; }
        .count { float: right; color: #fff; font-size: 8px; font-weight: bold; }
        table.changes { width: 100%; margin-bottom: 10px; border-collapse: collapse; table-layout: fixed; }
        table.changes thead { display: table-header-group; }
        table.changes tr { page-break-inside: avoid; }
        table.changes th { padding: 6px; color: #fff; border: 1px solid #376b89; background: #376b89; font-size: 7px; font-weight: bold; text-align: left; text-transform: uppercase; }
        table.changes td { padding: 6px; color: #000; border: 1px solid #b8c8d1; background: #fff; vertical-align: top; overflow-wrap: break-word; }
        .field { width: 28%; font-weight: bold; }
        .type { width: 14%; text-align: center; }
        .old, .new { width: 29%; }
        .old { color: #991b1b; background: #fef2f2 !important; font-weight: normal; }
        .new { color: #166534; background: #f0fdf4 !important; font-weight: bold; }
        .event-anulacion .new { color: #991b1b; background: #fef2f2 !important; }
        .badge { display: inline-block; padding: 2px 4px; border: 1px solid; font-size: 6px; font-weight: bold; text-transform: uppercase; }
        .badge-modificado { color: #854d0e; border-color: #d97706; background: #fef3c7; }
        .badge-agregado { color: #166534; border-color: #16a34a; background: #dcfce7; }
        .badge-eliminado { color: #991b1b; border-color: #dc2626; background: #fee2e2; }
        .empty { padding: 14px; color: #31566d; border: 1px solid #9bc5dc; background: #edf7fc; font-weight: bold; text-align: center; }
        .footer-note { margin-top: 12px; padding-top: 6px; color: #000; border-top: 1px solid #000; font-size: 7px; font-weight: bold; text-align: center; }
    </style>
</head>
<body class="event-{{ $evento->tipo_evento }}">
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
    <div class="event-reference">{{ $moduloNombre }} - {{ $textoSeguro($evento->registro_referencia) }}</div>
</div>

<table class="meta">
    <tr>
        <td><span class="label">Fecha del evento</span><span class="meta-value">{{ $evento->created_at?->format('d/m/Y') ?? '-' }}</span></td>
        <td><span class="label">Hora exacta</span><span class="meta-value">{{ $evento->created_at?->format('H:i:s') ?? '-' }}</span></td>
    </tr>
    <tr>
        <td><span class="label">Realizado por</span><span class="meta-value">{{ $evento->user_nombre ?: 'Sistema' }}</span></td>
        <td><span class="label">Registro afectado</span><span class="meta-value">{{ $textoSeguro($evento->registro_referencia) }}</span></td>
    </tr>
</table>

<div class="reason"><span class="label">Motivo registrado</span><span class="reason-value">{{ filled($evento->motivo) ? $textoSeguro($evento->motivo) : 'Sin motivo registrado' }}</span></div>

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
        $pdf->page_text(470, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 7, array(0, 0, 0));
    }
</script>
</body>
</html>
