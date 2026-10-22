@extends('emails.layout')
@section('preheader')You are warmly invited to the {{ $event['title'] }} on {{ $event['date_label'] }}.@endsection
@section('body')
    @include('emails._heading', ['eyebrow' => 'An invitation', 'title' => 'You are cordially <em style="color:#7D5F24;">invited</em>'])
    <p style="margin:0 0 16px;">Dear {{ $name }},</p>
    @if ($event['email']['copy']['invitation'])
        @include('emails._copy', ['copy' => $event['email']['copy']['invitation']])
    @else
        <p style="margin:0 0 16px;">{{ $event['host'] }}, on behalf of the Planning Committee, has the honour of inviting you to a Symposium in Honour of Dr. Festus Fadeyi at 85, and the presentation of the book <em>{{ $event['book_title'] }}</em>.</p>
        <p style="margin:0 0 16px;">It would be a privilege to welcome you as we celebrate this milestone together.</p>
    @endif
    @include('emails._details')
    <p style="margin:0 0 18px;">Kindly reply by <strong>{{ $event['rsvp']['deadline_label'] }}</strong>. Your personal link below takes under a minute.</p>
    @include('emails._button', ['href' => $rsvpUrl, 'label' => 'RSVP now'])
    <p style="margin:26px 0 16px;padding-top:18px;border-top:1px solid #E8DFCB;color:#5B6072;font-size:14px;">
        <strong style="color:#0B1730;">A note on giving.</strong> In celebration of this occasion, guests are graciously invited to give at the book presentation. All donations go to {{ $charityLabel }}, online or by bank transfer.@if ($event['donations']['pledges_enabled']) You may also indicate a pledge when you reply.@endif
    </p>
    <p style="margin:0;color:#5B6072;font-size:13px;">This invitation is personal to you. If the button does not work, copy this link into your browser: <a href="{{ $rsvpUrl }}" style="color:#7D5F24;">{{ $rsvpUrl }}</a></p>
@endsection
