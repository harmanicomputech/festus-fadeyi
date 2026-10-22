@extends('emails.layout')
@section('preheader')Three days to go — programme, directions and your check-in code inside.@endsection
@section('body')
    @include('emails._heading', ['eyebrow' => 'In three days', 'title' => 'We look forward to <em style="color:#7D5F24;">receiving you</em>'])
    <p style="margin:0 0 16px;">Dear {{ $name }},</p>
    @if ($event['email']['copy']['event_reminder'])
        @include('emails._copy', ['copy' => $event['email']['copy']['event_reminder']])
    @else
        <p style="margin:0 0 16px;">The celebration is almost here. Below you will find the programme, directions and arrival information, together with your personal check-in code.</p>
    @endif
    @include('emails._qr')
    @include('emails._details', ['showParking' => true, 'showArrival' => true])

    <p style="margin:0 0 8px;font-family:'Plus Jakarta Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:22px;color:#0B1730;">Programme</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;font-size:14px;">
        @foreach ($event['programme'] as $item)
            <tr>
                <td style="padding:7px 12px 7px 0;color:#7D5F24;font-weight:600;width:90px;vertical-align:top;">{{ $item['time'] ?? '' }}</td>
                <td style="padding:7px 0;border-bottom:1px solid #F1EBDD;">{{ $item['category'] }}@if ($item['detail'])<br><span style="color:#5B6072;font-size:13px;">{{ $item['detail'] }}</span>@endif</td>
            </tr>
        @endforeach
    </table>

    <p style="margin:0 0 8px;font-family:'Plus Jakarta Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:22px;color:#0B1730;">Directions</p>
    <p style="margin:0 0 22px;">{{ $event['venue']['full'] }} &middot; <a href="{{ $event['venue']['map_link'] }}" style="color:#7D5F24;">Open in Google Maps</a></p>

    <p style="margin:0 0 8px;font-family:'Plus Jakarta Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:22px;color:#0B1730;">Ways to give</p>
    <p style="margin:0 0 16px;">All donations go to {{ $charityLabel }}. You may give online, before or during the event.
        @if (\App\Support\EventInfo::bankDetailsConfigured())
            Or transfer to {{ $event['charity']['bank']['account_name'] }}, {{ $event['charity']['bank']['bank_name'] }}, {{ $event['charity']['bank']['account_number'] }}.
        @endif
    </p>
    @include('emails._button', ['href' => $donateUrl, 'label' => 'Donate'])
    @include('emails._button', ['href' => $manageUrl, 'label' => 'My RSVP', 'variant' => 'outline'])
    <p style="margin:18px 0 0;font-size:13px;color:#5B6072;">Unable to attend after all? Kindly <a href="{{ $manageUrl }}" style="color:#7D5F24;">update your reply</a> so your seat may be offered to another guest.</p>
@endsection
