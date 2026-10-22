@extends('emails.layout')
@php($status = $rsvp->status)
@section('preheader')
@if ($status === 'confirmed')Your seat is confirmed. Your check-in QR code is inside.@elseif ($status === 'waitlisted')You are on the waitlist. We will email you if a seat becomes available.@else Thank you for your reply.@endif
@endsection
@section('body')
    @if ($status === 'confirmed')
        @include('emails._heading', ['eyebrow' => $promoted ? 'Good news' : 'RSVP confirmed', 'title' => $promoted ? 'A seat is now <em style="color:#7D5F24;">yours</em>' : 'Thank you — your place is <em style="color:#7D5F24;">confirmed</em>'])
        <p style="margin:0 0 16px;">Dear {{ $name }},</p>
        @if ($promoted)
            <p style="margin:0 0 16px;">A seat has become available and we are delighted to confirm your place{{ $rsvp->party_size > 1 ? ' for '.$rsvp->party_size.' guests' : '' }}.</p>
        @elseif ($duplicate)
            <p style="margin:0 0 16px;">We already have your reply on record — here are your details again. You are confirmed{{ $rsvp->party_size > 1 ? ' for '.$rsvp->party_size.' seats' : '' }}.</p>
        @else
            <p style="margin:0 0 16px;">Thank you for your kind reply. We are delighted to confirm your place{{ $rsvp->party_size > 1 ? ' for '.$rsvp->party_size.' guests' : '' }} and look forward to welcoming you.</p>
        @endif
        @include('emails._qr')
        @include('emails._details')
        @include('emails._calendar')
    @elseif ($status === 'waitlisted')
        @include('emails._heading', ['eyebrow' => 'RSVP received', 'title' => 'You are on the <em style="color:#7D5F24;">waitlist</em>'])
        <p style="margin:0 0 16px;">Dear {{ $name }},</p>
        <p style="margin:0 0 16px;">Thank you for your kind reply. All seats are presently reserved, so we have placed you on the waitlist{{ $rsvp->party_size > 1 ? ' for '.$rsvp->party_size.' seats' : '' }}. Should a seat become available, we will email you immediately with your confirmation and check-in code.</p>
        @include('emails._details')
    @elseif ($status === 'maybe')
        @include('emails._heading', ['eyebrow' => 'RSVP received', 'title' => 'Thank you for your <em style="color:#7D5F24;">reply</em>'])
        <p style="margin:0 0 16px;">Dear {{ $name }},</p>
        <p style="margin:0 0 16px;">Thank you for letting us know you may be able to join us. Seats are reserved only for confirmed guests, so kindly update your reply to “Yes” by <strong>{{ $event['rsvp']['deadline_label'] }}</strong> if you are able to attend.</p>
        @include('emails._details')
    @else
        @include('emails._heading', ['eyebrow' => 'RSVP received', 'title' => 'You will be <em style="color:#7D5F24;">missed</em>'])
        <p style="margin:0 0 16px;">Dear {{ $name }},</p>
        <p style="margin:0 0 16px;">Thank you for letting us know that you are unable to attend. Your kind response is much appreciated. Should your plans change, you may update your reply using the link below.</p>
    @endif

    @if ($pledge)
        <p style="margin:0 0 16px;padding:14px 18px;background:#F8F5ED;">Thank you for your pledge of <strong>{{ \App\Support\EventInfo::naira($pledge->amount_kobo) }}</strong> to {{ $charityLabel }}. A pledge is a promise only — nothing has been charged.
            @if (\App\Support\EventInfo::donationsReady())<br><a href="{{ $pledge->payUrl() }}" style="color:#7D5F24;font-weight:600;">Redeem your pledge on or before the event</a>@endif
        </p>
    @endif

    @include('emails._button', ['href' => $manageUrl, 'label' => 'View or update RSVP', 'variant' => 'navy'])
    @include('emails._button', ['href' => $donateUrl, 'label' => 'Donate', 'variant' => 'outline'])
    <p style="margin:22px 0 0;color:#5B6072;font-size:13px;">Your RSVP link is personal: anyone with it can change your reply, so please do not forward this email.</p>
@endsection
