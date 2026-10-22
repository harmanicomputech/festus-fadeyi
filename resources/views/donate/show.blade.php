@extends('layouts.site', ['title' => 'Donate'])

@php($charity = \App\Support\EventInfo::charityLabel())

@section('content')
<section class="page page--navy on-dark">
    <div class="pattern" aria-hidden="true"></div>
    <div class="container" style="position:relative">
        <div class="reply-card">
            <x-corner class="reply-card__corner reply-card__corner--tl" /><x-corner class="reply-card__corner reply-card__corner--tr" />
            <x-corner class="reply-card__corner reply-card__corner--bl" /><x-corner class="reply-card__corner reply-card__corner--br" />

            <div class="reply-card__head">
                <span class="eyebrow eyebrow--both">Give</span>
                <h1 class="reply-card__title">A gift in <em>his honour</em></h1>
                <p class="reply-card__sub">Every gift made for {{ config('event.short_title') }} goes to <strong>{{ $charity }}</strong>. Thank you for your generosity.</p>
                <x-ornament />
            </div>

            @if (! $ready)
                <div class="notice" role="status">
                    <p><strong>Online card payment is being connected.</strong> {{ $bankReady ? 'In the meantime, you can give by bank transfer:' : 'Please check back shortly.' }}</p>
                </div>
                @if ($bankReady)
                    @php($bank = config('event.charity.bank'))
                    <dl class="summary">
                        <div><dt>Bank</dt><dd>{{ $bank['bank_name'] }}</dd></div>
                        <div><dt>Account name</dt><dd>{{ $bank['account_name'] }}</dd></div>
                        <div><dt>Account number</dt><dd>{{ $bank['account_number'] }}</dd></div>
                    </dl>
                @endif
                <p style="text-align:center;margin-top:2rem"><a class="btn btn--outline-navy btn--sm" href="{{ route('home') }}#give">Back to the celebration</a></p>
            @else
                @if ($pledge)
                    <p class="greeting">Redeeming the pledge of {{ $pledge->name }}</p>
                @endif

                @if ($errors->any())
                    <div class="notice notice--error" role="alert" data-error-summary><p>Please check the highlighted fields below.</p></div>
                @endif

                @php($err = fn ($f) => $errors->has($f) ? 'aria-invalid=true aria-describedby='.$f.'-error' : '')
                <form method="POST" action="{{ route('donate.store') }}" novalidate data-loading-form>
                    @csrf
                    @if ($pledge)<input type="hidden" name="pledge" value="{{ $pledge->pay_token }}">@endif
                    <div class="form-grid">
                        <div class="field col-6">
                            <label for="amount">Amount</label>
                            <div class="input-prefix">
                                <span aria-hidden="true">₦</span>
                                <input class="input" id="amount" name="amount" type="text" inputmode="numeric" required maxlength="14" data-money
                                       value="{{ old('amount', $pledge ? number_format($pledge->amount_kobo / 100) : '') }}" {!! $err('amount') !!}>
                            </div>
                            <div class="amount-chips" role="group" aria-label="Suggested amounts">
                                @foreach (config('event.donations.suggested') as $amt)
                                    <button type="button" data-amount-chip="{{ $amt }}" data-target="amount" aria-pressed="false">₦{{ number_format($amt) }}</button>
                                @endforeach
                            </div>
                            <x-field-error name="amount" />
                        </div>

                        <div class="field col-6">
                            <label class="checkbox"><input type="checkbox" name="anonymous" value="1" data-anonymous @checked(old('anonymous'))><span>Give anonymously — your name will not be shown to anyone outside the planning committee’s receipts record.</span></label>
                        </div>

                        <div class="field col-3" data-when-named>
                            <label for="donor_name">Your name</label>
                            <input class="input" id="donor_name" name="donor_name" type="text" autocomplete="name" maxlength="120" value="{{ old('donor_name', $pledge?->name) }}" {!! $err('donor_name') !!}>
                            <x-field-error name="donor_name" />
                        </div>

                        <div class="field col-3">
                            <label for="email">Email <span class="optional">for your receipt</span></label>
                            <input class="input" id="email" name="email" type="email" autocomplete="email" required maxlength="190" value="{{ old('email', $pledge?->email) }}" {!! $err('email') !!}>
                            <x-field-error name="email" />
                        </div>

                        <div class="field col-6">
                            <label for="phone">Phone <span class="optional">optional</span></label>
                            <input class="input" id="phone" name="phone" type="tel" autocomplete="tel" maxlength="20" value="{{ old('phone') }}" {!! $err('phone') !!}>
                            <x-field-error name="phone" />
                        </div>

                        <div class="field col-6">
                            <label for="message">Message to the celebrant <span class="optional">optional</span></label>
                            <textarea class="textarea" id="message" name="message" rows="3" maxlength="1000" {!! $err('message') !!}>{{ old('message') }}</textarea>
                            <x-field-error name="message" />
                        </div>

                        <div class="field col-6" data-when-named>
                            <label class="checkbox"><input type="checkbox" name="acknowledge_publicly" value="1" data-ack @checked(old('acknowledge_publicly'))><span>You may acknowledge my name publicly, on this website and at the event.</span></label>
                            <label class="checkbox" style="margin-top:.6rem"><input type="checkbox" name="show_amount_publicly" value="1" data-ack-amount @checked(old('show_amount_publicly'))><span>You may also show the amount of my gift.</span></label>
                        </div>

                        <div class="honeypot" aria-hidden="true"><label for="website">Leave this empty</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>

                        <div class="privacy-note">
                            <p><strong>Privacy notice.</strong> Your details are used only to process this gift, send your receipt and report donations to the planning committee and {{ $charity }}. Card details are entered on Paystack’s secure checkout and never reach this website. <a class="link-gold" href="{{ route('privacy') }}" target="_blank">Full privacy notice</a>.</p>
                            <label class="checkbox"><input type="checkbox" name="privacy" value="1" required @checked(old('privacy'))><span>I have read the privacy notice.</span></label>
                            <x-field-error name="privacy" />
                        </div>

                        <div class="form-actions">
                            <button class="btn btn--navy" type="submit">Continue to secure payment <x-arrow /></button>
                            <p class="hint">You will be taken to Paystack to pay by card, bank transfer or USSD. Your gift is confirmed only once Paystack verifies the payment.</p>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection
