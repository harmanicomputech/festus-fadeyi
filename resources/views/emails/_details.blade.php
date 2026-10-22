{{-- Event details card --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:22px 0;background:#F8F5ED;border-left:2px solid #C7A45B;">
    <tr><td style="padding:18px 22px;font-family:Inter,'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.75;color:#202531;">
        <strong style="color:#0B1730;">{{ $event['title'] }}</strong><br>
        {{ $event['subtitle'] }} &middot; <em>{{ $event['book_title'] }}</em><br>
        <span style="color:#7D5F24;font-weight:600;">Date</span>&nbsp; {{ $event['date_label'] }}<br>
        <span style="color:#7D5F24;font-weight:600;">Time</span>&nbsp; {{ $event['time_label'] }} (Lagos time)<br>
        <span style="color:#7D5F24;font-weight:600;">Venue</span>&nbsp; {{ $event['venue']['full'] }}<br>
        <span style="color:#7D5F24;font-weight:600;">Dress code</span>&nbsp; {{ $event['dress_code'] }}
        @if (! empty($showParking) && $event['venue']['parking_notes'])<br><span style="color:#7D5F24;font-weight:600;">Parking</span>&nbsp; {{ $event['venue']['parking_notes'] }}@endif
        @if (! empty($showArrival) && $event['venue']['arrival_notes'])<br><span style="color:#7D5F24;font-weight:600;">Arrival</span>&nbsp; {{ $event['venue']['arrival_notes'] }}@endif
    </td></tr>
</table>
