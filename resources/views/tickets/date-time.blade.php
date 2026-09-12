@php($date = \Carbon\Carbon::parse($date))
<table class="compact-date">
    <tr>
        <td style="width:55%">FECHA: {{ $date->format('d/m/Y') }}</td>
        <td style="width:45%; text-align:right">HORA: {{ $date->format('H:i:s') }}</td>
    </tr>
</table>
