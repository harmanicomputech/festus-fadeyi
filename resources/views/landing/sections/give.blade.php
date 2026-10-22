@php($charity = config('event.charity'))
@php($bank = $charity['bank'])
<section class="section give grain" id="give" aria-labelledby="give-title">
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow eyebrow--both">Give</span>
            <h2 class="display" id="give-title">Honour the occasion <em>with a gift</em></h2>
            <p class="lede">In celebration of Dr. Fadeyi’s 85th birthday, every donation goes to {{ \App\Support\EventInfo::charityLabel() }}. Gifts of any size are received with gratitude.</p>
        </div>

        <div class="give__grid">
            <article class="card card--navy card--keyline on-dark reveal" aria-label="The beneficiary">
                <div class="charity__head">
                    <div class="charity__logo">
                        @if ($charity['logo'])
                            <img src="{{ $charity['logo'] }}" alt="{{ $charity['name'] }} logo" loading="lazy">
                        @else
                            <span>Logo to be supplied</span>
                        @endif
                    </div>
                    <div>
                        <span class="eyebrow">The beneficiary</span>
                        @if ($charity['name'])
                            <h3 class="charity__name">{{ $charity['name'] }}</h3>
                        @else
                            <h3 class="charity__name" style="font-style:italic;opacity:.8">Charity to be announced</h3>
                        @endif
                    </div>
                </div>

                <div class="charity__block">
                    <h4>The cause</h4>
                    @if ($charity['cause'])<p>{{ $charity['cause'] }}</p>@else<x-placeholder label="Designated charitable cause" />@endif
                </div>
                <div class="charity__block">
                    <h4>About the charity</h4>
                    @if ($charity['description'])<p>{{ $charity['description'] }}</p>@else<x-placeholder label="Charity description" />@endif
                </div>
                <div class="charity__block">
                    <h4>How your gift will be used</h4>
                    @if ($charity['fund_use'])<p>{{ $charity['fund_use'] }}</p>@else<x-placeholder label="How the proceeds will be used" />@endif
                </div>
            </article>

            <div class="reveal" style="--delay:.1s">
                <ol class="ways" aria-label="Ways to give">
                    <li class="way">
                        <span class="way__num">I</span>
                        <div>
                            <h3>Give online</h3>
                            <p>Give securely by card, bank transfer or USSD. You will receive an emailed receipt as soon as your gift is confirmed.</p>
                            <div class="actions">
                                @if ($donationsReady)
                                    <a class="btn btn--navy btn--sm" href="{{ route('donate.show') }}">Donate <x-arrow /></a>
                                @else
                                    <span class="status-pill">Online giving opens shortly</span>
                                @endif
                            </div>
                        </div>
                    </li>
                    <li class="way">
                        <span class="way__num">II</span>
                        <div>
                            <h3>Bank transfer</h3>
                            @if (\App\Support\EventInfo::bankDetailsConfigured())
                                <p>Transfer directly to the dedicated account:</p>
                                <dl class="bank">
                                    <dt>Bank</dt><dd>{{ $bank['bank_name'] }}</dd>
                                    <dt>Account name</dt><dd>{{ $bank['account_name'] }}</dd>
                                    <dt>Account number</dt><dd>{{ $bank['account_number'] }}</dd>
                                </dl>
                                <p class="fine-print" style="margin-top:.8rem">To receive a receipt, kindly email your name, amount and transfer date to <a class="link-gold" href="mailto:{{ config('event.contact.email') }}">{{ config('event.contact.email') }}</a>.</p>
                            @else
                                <x-placeholder label="Dedicated bank account details">Bank, account name and account number will appear here once supplied.</x-placeholder>
                            @endif
                        </div>
                    </li>
                    <li class="way">
                        <span class="way__num">III</span>
                        <div>
                            <h3>At the venue</h3>
                            <p>On the day, scan the QR code on your table card or in the programme to give from your phone during the book presentation.</p>
                        </div>
                    </li>
                    <li class="way">
                        <span class="way__num">IV</span>
                        <div>
                            <h3>Pledge with your RSVP</h3>
                            <p>Commit an amount when you reply. We will send you a link to redeem your pledge on or before the event.</p>
                            <div class="actions"><a class="link-gold" href="#rsvp">Pledge when you RSVP</a></div>
                        </div>
                    </li>
                </ol>
                <p class="fine-print" style="margin-top:1rem">Donors are acknowledged publicly only with their consent, and amounts are never shown without explicit permission.</p>
            </div>
        </div>

        @if ($acknowledgements->isNotEmpty())
            <div class="acknowledgements reveal">
                <x-ornament />
                <h3 class="display" style="font-size:2rem;margin-top:1rem">With <em>gratitude</em></h3>
                <ul>
                    @foreach ($acknowledgements as $a)
                        <li>{{ $a->donor_name }}@if ($a->show_amount_publicly)<small>{{ \App\Support\EventInfo::naira($a->amount_kobo) }}</small>@endif</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</section>
