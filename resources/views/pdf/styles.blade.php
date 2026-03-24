<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Reporte' }}</title>

    <style>
        body { font-family: Courier, monospace; font-size: 11px; margin: 0; padding: 0; }

        @page { margin-top: 60px; margin-bottom: 40px; margin-left: 20px; margin-right: 20px; }

        .page-header {
            position: fixed; top: -60px; left: 0; right: 0; height: 50px;
            font-family: Courier, monospace; font-size: 11px; padding: 5px 20px;
        }

        .page-header table { width: 100%; border-collapse: collapse; }
        .page-header td { padding: 2px 0; }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }

        .header-separator { border-bottom: 1px solid #000; margin: 5px 0 10px 0; }
        .page-number:before { content: "Página " counter(page); }

        table.reporte { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.reporte th, table.reporte td { padding: 2px 3px; white-space: nowrap; overflow: hidden; }
        table.reporte th { border-bottom: 1px solid #000; }

        .linea2 { border-bottom: 0.5px dashed #000; }

        .subtotal-row { font-weight: bold; border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .total-row { font-weight: bold; border-top: 2px solid #000; }
    </style>
</head>