<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $compra->comprobante_tipo_nombre }} - compra {{ $compra->serie }}-{{ $compra->correlativo }}</title>
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
        <!--<p>{{ $empresa->direccion }}</p>-->
        <p>RUC: {{ $empresa->ruc }}</p>
        <p class="bold">{{ $compra->comprobante_tipo_nombre}} {{ $compra->serie }}-{{ str_pad($compra->correlativo,8,'0',STR_PAD_LEFT) }}</p>
    </div>
    <div class="line"></div>
    {{-- DATOS PROVEEDOR --}}
    <p><strong>Proveedor:</strong> {{ $compra->proveedor_nombre }}</p>
    <p><strong>Documento:</strong> {{ $compra->proveedor->documentoTipo->descripcion }} {{ $compra->proveedor_documento }}</p>
    <p><strong>Dirección:</strong> {{ $compra->proveedor_direccion ?? '-' }}</p>
    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($compra->fecha_compra)->format('d/m/Y H:i') }}</p>
    <div class="line"></div>
    {{-- DETALLE PRODUCTOS --}}
    <table>
        <tr class="bold">
            <td style="width: 38%;">Descripción</td>
            <td style="width: 12%; text-align: center;">Cant.</td>
            <td style="width: 25%; text-align: right; padding-right: 3px;">P.Unit.</td>
            <td style="width: 25%; text-align: right;">Total</td>
        </tr>
        <tr><td colspan="4" style="border-top: 1px solid #000;"></td></tr>
        @foreach($compra->detalles as $detalle)
        <tr>
            <td>{{ $detalle->producto_nombre }}</td>
            <td style="text-align: center;">{{ $detalle->cantidad }}</td>
            <td style="text-align: right; padding-right: 3px;">{{ number_format($detalle->costo_unitario,2) }}</td>
            <td style="text-align: right;">{{ number_format($detalle->total,2) }}</td>
        </tr>
        @endforeach
        <tr><td colspan="4" style="border-top: 1px solid #000;"></td></tr>
    </table>
    {{-- TOTALES --}}
    <table class="totales">
        <tr>
            <td>OP. GRAVADAS:</td>
            <td style="text-align: right;">S/ {{ number_format($compra->op_gravada,2) }}</td>
        </tr>
        <tr>
            <td>OP. EXONERADAS:</td>
            <td style="text-align: right;">S/ {{ number_format($compra->op_exonerada,2) }}</td>
        </tr>
        <tr>
            <td>OP. INAFECTAS:</td>
            <td style="text-align: right;">S/ {{ number_format($compra->op_inafecta,2) }}</td>
        </tr>
        <tr>
            <td>Impuesto (18%):</td>
            <td style="text-align: right;">S/ {{ number_format($compra->impuesto,2) }}</td>
        </tr>
        <tr class="bold">
            <td>TOTAL:</td>
            <td style="text-align: right;">S/ {{ number_format($compra->total,2) }}</td>
        </tr>
    </table>
    <div class="line"></div>
</div>
</body>
</html>