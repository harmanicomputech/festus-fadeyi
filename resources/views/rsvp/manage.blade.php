@extends('layouts.site', ['title' => 'Your RSVP', 'noindex' => true])

@php
    use App\Models\Rsvp;
    $justSubmitted = session('just_submitted');
    $headline = match ($rsvp->status) {
        Rsvp::STATUS_CONFIRMED => $justSubmitted ? 'Your place is confirmed' : 'You are attending',
        Rsvp::STATUS_WAITLISTED => 'You are on the waitlist',
        Rsvp::STATUS_MAYBE => 'Thank you for your reply',
        default => 'Thank you for letting us know',
    };
@endphp

@section('content')
<section class="page page--navy on-dark">
    <div class="pattern" aria-hidden="true"></div>
    <div class="container" style="position:relative">
        <div class="reply-card">
            <x-corner class="reply-card__corner reply-card__corner--tl" /><x-corner class="reply-card__corner reply-card__corner--tr" />
            <x-corner class="reply-card__corner reply-card__corner--bl" /><x-corner class="reply-card__corner reply-card__corner--br" />

            @if (session('updated'))
                <div class="notice notice--success" role="status"><p>Your reply has been updated. A confirmation has been sent to {{ $rsvp->email }}.</p></div>
            @endif

            <div class="status-card">
                <span class="status-badge status-badge--{{ $rsvp->status }}">{{ $rsvp->statusLabel() }}</span>
                <h1 class="reply-card__title">{{ $headline }}</h1>
                <p class="reply-card__sub">
                    @switch($rsvp->status)
                        @case(Rsvp::STATUS_CONFIRMED)
                            {{ $rsvp->salutation() }}, we look forward to receiving you on <strong>{{ config('event.date_label') }}</strong> at {{ config('event.time_label') }}, {{ config('event.venue.full') }}.
                            @break
                        @case(Rsvp::STATUS_WAITLISTED)
                            All seats are currently reserved, {{ $rsvp->salutation() }}. You are on the waitlist and we will email you the moment a seat becomes available.
                            @break
                        @case(Rsvp::STATUS_MAYBE)
                            Thank you, {{ $rsvp->salutation() }}. Seats are reserved only for confirmed guests, so kindly update your reply to “Yes” by {{ config('event.rsvp.deadline_label') }} if you are able to join us.
                            @break
                        @default
                            Thank you, {{ $rsvp->salutation() }}. You will be missed. Should your plans change, you may update your reply below.
                    @endswitch
                </p>

                @if ($rsvp->isConfirmed())
                    <div class="qr-ticket">
                        <img src="{{ $rsvp->qrUrl() }}" alt="Your personal check-in QR code" width="220" height="220">
                        <p>Present this code at the welcome desk.<br>It is also in your confirmation email.</p>
                    </div>
                @endif

                <dl class="summary">
                    <div><dt>Guest</dt><dd>{{ $rsvp->salutation() }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $rsvp->email }}</dd></div>
                    @if ($rsvp->attendance !== 'no')<div><dt>Seats</dt><dd>{{ $rsvp->party_size }}</dd></div>@endif
                    <div><dt>Dress code</dt><dd>{{ config('event.dress_code') }}</dd></div>
                    @if ($pledge)
                        <div><dt>Pledge</dt><dd>{{ \App\Support\EventInfo::naira($pledge->amount_kobo) }} · {{ ucfirst($pledge->status) }}</dd></div>
                    @endif
                </dl>

                <div style="display:flex;flex-wrap:wrap;gap:.7rem;justify-content:center;margin-top:1.4rem">
                    @if ($rsvp->isConfirmed())<x-calendar-menu class="btn btn--outline-navy btn--sm" />@endif
                    @if ($pledge && $pledge->status === 'open' && \App\Support\EventInfo::donationsReady())
                        <a class="btn btn--navy btn--sm" href="{{ $pledge->payUrl() }}">Redeem my pledge</a>
                    @else
                        <a class="btn btn--outline-navy btn--sm" href="{{ route('home') }}#give">Ways to give</a>
                    @endif
                </div>
            </div>

            <div style="margin-top:3rem">
                <x-ornament />
                <h2 class="display" style="font-size:2rem;text-align:center;margin:1.2rem 0 .6rem">Update your reply</h2>
                @unless ($rsvpOpen)
                    <p class="hint" style="text-align:center;margin-bottom:1.5rem">RSVPs closed on {{ config('event.rsvp.deadline_label') }}. You can still reduce your seats or let us know you can no longer attend.</p>
                @endunless

                @if ($errors->any())
                    <div class="notice notice--error" role="alert" data-error-summary><p>Please check the highlighted fields below.</p></div>
                @endif

                <form method="POST" action="{{ route('rsvp.update', $token) }}" novalidate data-loading-form>
                    @csrf
                    @method('PUT')
                    @include('partials.rsvp-fields', ['rsvp' => $rsvp, 'pledge' => $pledge])
                    <div class="form-actions" style="margin-top:1.6rem">
                        <button class="btn btn--navy" type="submit">Save changes</button>
                    </div>
                </form>

                @if ($rsvp->status !== Rsvp::STATUS_DECLINED)
                    <form method="POST" action="{{ route('rsvp.cancel', $token) }}" style="text-align:center;margin-top:1.4rem" data-loading-form data-confirm="Cancel your RSVP? Your seat will be released to another guest.">
                        @csrf
                        <button class="btn btn--sm btn--outline-navy" type="submit">I can no longer attend</button>
                    </form>
                @endif
                <p class="hint" style="text-align:center;margin-top:1.4rem">To change your email address, contact {{ config('event.contact.name') }}: <a class="link-gold" href="mailto:{{ config('event.contact.email') }}">{{ config('event.contact.email') }}</a>. Keep this page’s link private: anyone with it can change your reply.</p>
            </div>
        </div>
    </div>
</section>
@endsection
