@php
    $charity = config('event.charity');
    $bank = $charity['bank'];
    $bankReady = \App\Support\EventInfo::bankDetailsConfigured();
@endphp
<section class="section" id="give" aria-labelledby="give-title">
    <div class="container">
        <div class="section-head">
            <span class="kicker" data-reveal>Give</span>
            <h2 class="h2" id="give-title" data-split>Give in <em>his honour</em></h2>
        </div>

        <div class="charity-line" data-reveal>
            @if ($charity['logo'])<img src="{{ $charity['logo'] }}" alt="{{ $charity['name'] }} logo" loading="lazy">@endif
            @if ($charity['name'])
                <span>All donations go to <strong>{{ $charity['name'] }}</strong>@if ($charity['cause']) — {{ $charity['cause'] }}@endif</span>
            @else
                <span>Every gift supports the charitable cause of the symposium.</span>
            @endif
        </div>

        <div class="give__cards">
            <article class="give-card give-card--navy on-dark" data-reveal>
                <div class="pattern" aria-hidden="true"></div>
                <div style="position:relative">
                    <span class="give-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19M6 15h4"/></svg></span>
                    <h3>Give online</h3>
                    <p>Card, bank transfer or USSD via Paystack. Instant email receipt.</p>
                </div>
                <div style="position:relative">
                    <a class="btn" href="{{ route('donate.show') }}" data-magnetic>Donate now <x-arrow /></a>
                </div>
            </article>

            <article class="give-card" data-reveal>
                <div>
                    <span class="give-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M3 10l9-6 9 6M5 10v8M9.5 10v8M14.5 10v8M19 10v8M3 20h18"/></svg></span>
                    <h3>Bank transfer</h3>
                </div>
                @if ($bankReady)
                    <dl class="bank">
                        <div><dt>Account number</dt><dd class="acct">{{ $bank['account_number'] }} <button class="copy-btn" type="button" data-copy="{{ $bank['account_number'] }}">Copy</button></dd></div>
                        <div><dt>Account name</dt><dd>{{ $bank['account_name'] }}</dd></div>
                        <div><dt>Bank</dt><dd>{{ $bank['bank_name'] }}</dd></div>
                    </dl>
                @else
                    <x-placeholder label="Account details" />
                @endif
            </article>
        </div>

        @if ($charity['description'] || $charity['fund_use'])
            <div class="charity-about" data-reveal>
                @if ($charity['description'])<p>{{ $charity['description'] }}</p>@endif
                @if ($charity['fund_use'])<p><strong style="color:var(--navy)">How your gift helps.</strong> {{ $charity['fund_use'] }}</p>@endif
            </div>
        @endif

        @if ($acknowledgements->isNotEmpty())
            <div class="acknowledgements" data-reveal>
                <span class="eyebrow eyebrow--both">With gratitude</span>
                <ul>
                    @foreach ($acknowledgements as $a)
                        <li>{{ $a->donor_name }}@if ($a->show_amount_publicly)<small>{{ \App\Support\EventInfo::naira($a->amount_kobo) }}</small>@endif</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</section>
