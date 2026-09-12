@php
    $ticketLayout = $ticketLayout ?? app(\App\Services\TicketLayoutService::class);
    $width = (float) config('tickets.content_width_mm');
    $quantityWithUnit = trim($quantity . ' ' . ($unit ?? ''));
    $quantityFits = $ticketLayout->fits($quantityWithUnit, $width * 0.40 - 0.8);
    $priceFits = $ticketLayout->fits($price, $width * 0.27 - 0.8);
    $amountFits = $ticketLayout->fits($amount, $width * 0.33 - 0.8);
@endphp
<div class="ticket-item">
    <p class="product-name">{{ $name }}</p>
    <table class="item-values">
        <tr>
            <td class="item-quantity"><span class="number compact-quantity">{{ $quantityFits ? $quantityWithUnit : '' }}</span></td>
            <td class="item-price"><span class="number">{{ $priceFits ? $price : '' }}</span></td>
            <td class="item-amount"><span class="number">{{ $amountFits ? $amount : '' }}</span></td>
        </tr>
    </table>
    @unless($quantityFits)
        <p>Cant.: <span class="number compact-quantity">{{ $quantityWithUnit }}</span></p>
    @endunless
    @unless($priceFits)
        <p>P.Unit: <span class="number">{{ $price }}</span></p>
    @endunless
    @unless($amountFits)
        <p>Importe: <span class="number">{{ $amount }}</span></p>
    @endunless
</div>
