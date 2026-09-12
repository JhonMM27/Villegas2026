@php($ticketProfile = $ticketProfile ?? config('tickets'))
<style>
    @page {
        margin-top: {{ $ticketProfile['margin_top_mm'] }}mm;
        margin-right: {{ $ticketProfile['paper_width_mm'] - $ticketProfile['margin_left_mm'] - $ticketProfile['content_width_mm'] }}mm;
        margin-bottom: {{ $ticketProfile['margin_bottom_mm'] }}mm;
        margin-left: {{ $ticketProfile['margin_left_mm'] }}mm;
    }
    body { margin: 0; padding: 0; color: #000; }
    body { font-family: 'DejaVu Sans'; font-size: {{ $ticketProfile['font_size_pt'] }}pt; line-height: 1.25; }
    .ticket { width: 100%; margin: 0; padding: 0; }
    p, h3 { margin: 0 0 1mm; padding: 0; overflow-wrap: break-word; word-wrap: break-word; }
    h3 { font-size: 11pt; }
    .center { text-align: center; }
    .right { text-align: right; }
    .bold, strong, .data-label, .product-name { font-weight: bold; }
    .number { font-family: 'DejaVu Sans Mono'; font-size: {{ $ticketProfile['number_size_pt'] }}pt; font-weight: normal; white-space: nowrap; }
    .total { font-size: 13pt; text-align: right; font-weight: bold; }
    .small { font-size: 10pt; }
    .line, .line-thin { border-top: 0.5pt solid #000; margin: 2mm 0; }
    .spacer { height: 1mm; }
    .footer-space { height: 2mm; }
    .ticket-item, .ticket-row { page-break-inside: avoid; margin-bottom: 2mm; }
    .ticket-field { margin-bottom: 0.5mm; overflow-wrap: break-word; word-wrap: break-word; }
    .field-label { font-size: 10pt; }
    .product-name { margin-top: 1mm; }
    .item-values { width: 100%; table-layout: fixed; border-collapse: collapse; margin: 0; }
    .item-values td, .item-values th { padding: 0; vertical-align: top; }
    .item-quantity { width: 32%; text-align: left; }
    .item-price { width: 32%; text-align: right; }
    .item-amount { width: 36%; text-align: right; }
    .item-values .number { font-size: {{ $ticketProfile['detail_number_size_pt'] }}pt; }
    .item-unit, .item-currency { font-size: 9pt; }
    .detail-header { border-bottom: 0.5pt dashed #000; margin-bottom: 2mm; page-break-after: avoid; }
    .detail-header th { font-size: 10pt; padding-bottom: 1mm; }
    .ticket-item { border-bottom: 0.5pt dotted #000; padding-bottom: 1mm; }
    #ticket-end { height: 0.1pt; }
</style>
