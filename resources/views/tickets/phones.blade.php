@php
    $phones = $ticketPhones ?? app(\App\Services\TelefonoTicketService::class)->current();
@endphp
<p style="overflow-wrap:break-word; word-wrap:break-word">CEL: {{ $phones['telefono_1'] }}@if(filled($phones['telefono_2'])) / {{ $phones['telefono_2'] }}@endif</p>
