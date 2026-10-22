@extends('layouts.site', ['title' => 'Your gift', 'noindex' => true])

@section('content')
<section class="page page--navy on-dark">
    <div class="pattern" aria-hidden="true"></div>
    <div class="container" style="position:relative">
        <div class="reply-card">
            <x-corner class="reply-card__corner reply-card__corner--tl" /><x-corner class="reply-card__corner reply-card__corner--tr" />
            <x-corner class="reply-card__corner reply-card__corner--bl" /><x-corner class="reply-card__corner reply-card__corner--br" />
            <div class="status-card">
                @switch($donation->status)
                    @case('success')
                        <span class="status-badge status-badge--confirmed">Payment confirmed</span>
                        <h1 class="reply-card__title">Thank <em>you</em></h1>
                        <p class="reply-card__sub">Your gift of <strong>{{ \App\Support\EventInfo::naira($donation->amount_kobo) }}</strong> to {{ \App\Support\EventInfo::charityLabel() }} has been received and verified. A receipt is on its way to {{ $donation->email }}.</p>
                        @break
                    @case('pending')
                        <span class="status-badge status-badge--pending">Awaiting confirmation</span>
                        <h1 class="reply-card__title">Confirming your <em>gift</em></h1>
                        <p class="reply-card__sub">We have not yet received confirmation from the payment provider. If you completed payment, this usually takes a few moments — refresh this page shortly. Your receipt will be emailed as soon as it is verified.</p>
                        <p><a class="btn btn--outline-navy btn--sm" href="{{ route('donate.status', $donation->reference) }}">Refresh status</a></p>
                        @break
                    @case('review')
                        <span class="status-badge status-badge--pending">Under review</span>
                        <h1 class="reply-card__title">Thank you — <em>we are checking</em></h1>
                        <p class="reply-card__sub">We need to confirm a detail of this payment with the payment provider. The planning committee will be in touch; no further action is needed from you.</p>
                        @break
                    @default
                        <span class="status-badge status-badge--failed">Not completed</span>
                        <h1 class="reply-card__title">Payment <em>not completed</em></h1>
                        <p class="reply-card__sub">This payment was not completed and nothing has been recorded as received. You are welcome to try again.</p>
                        <p><a class="btn btn--navy btn--sm" href="{{ route('donate.show') }}">Try again</a></p>
                @endswitch

                <dl class="summary">
                    <div><dt>Reference</dt><dd>{{ $donation->reference }}</dd></div>
                    <div><dt>Amount</dt><dd>{{ \App\Support\EventInfo::naira($donation->amount_kobo) }}</dd></div>
                    @if ($donation->paid_at)<div><dt>Date</dt><dd>{{ $donation->paid_at->timezone(config('event.timezone'))->format('j F Y, g:i A') }}</dd></div>@endif
                </dl>
                <p style="margin-top:1.6rem"><a class="link-gold" href="{{ route('home') }}">Return to the celebration</a></p>
            </div>
        </div>
    </div>
</section>
@endsection
