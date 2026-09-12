@php
    $ticketLayout = $ticketLayout ?? app(\App\Services\TicketLayoutService::class);
    $width = (float) config('tickets.content_width_mm');
    $size = ($isTotal ?? false) ? 12.0 : 10.0;
    $isBold = $isBold ?? false;
    $labelWidth = $ticketLayout->widthMm($label, $size, $isTotal ?? false) + 1.2;
    $inline = $labelWidth + $ticketLayout->widthMm($value, $size, $isTotal ?? false) <= $width;
@endphp
<table class="compact-summary {{ ($isTotal ?? false) ? 'compact-total' : '' }} {{ $isBold ? 'bold-summary' : '' }}">
    @if($inline)
        <tr><td style="width:{{ $labelWidth }}mm; text-align:left; white-space:nowrap">{{ $label }}</td><td style="width:{{ $width - $labelWidth }}mm; text-align:right"><span class="number">{{ $value }}</span></td></tr>
    @else
        <tr><td>{{ $label }}</td></tr>
        <tr><td class="right"><span class="number">{{ $value }}</span></td></tr>
    @endif
</table>
