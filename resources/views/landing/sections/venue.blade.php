@php($venue = config('event.venue'))
<section class="section venue" id="venue" aria-labelledby="venue-title">
    <div class="container venue__grid">
        <div class="venue__info">
            <span class="kicker" data-reveal>Venue</span>
            <h2 class="h2" id="venue-title" data-split>Sky Restaurant, <em>Eko Hotels</em></h2>
            <p class="sr-only">{{ $venue['full'] }}</p>

            <ul class="venue__facts" data-reveal style="margin-top:2.2rem">
                <li>
                    <span class="venue__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg></span>
                    <div><small>Date</small><span>{{ config('event.date_label') }}</span></div>
                </li>
                <li>
                    <span class="venue__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <div><small>Time</small><span>{{ config('event.time_label') }}</span></div>
                </li>
                <li>
                    <span class="venue__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M8 3l4 3 4-3 4 3-3 4v11H7V10L4 6z"/></svg></span>
                    <div><small>Dress code</small><span>{{ config('event.dress_code') }}</span></div>
                </li>
                @if ($venue['parking_notes'])
                    <li>
                        <span class="venue__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M9 17V7h4a3 3 0 0 1 0 6H9"/></svg></span>
                        <div><small>Parking</small><span>{{ $venue['parking_notes'] }}</span></div>
                    </li>
                @endif
                @if ($venue['arrival_notes'])
                    <li>
                        <span class="venue__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                        <div><small>Arrival</small><span>{{ $venue['arrival_notes'] }}</span></div>
                    </li>
                @endif
            </ul>

            <div class="venue__actions" data-reveal>
                <a class="btn" href="{{ $venue['map_link'] }}" target="_blank" rel="noopener" data-magnetic>Get directions <x-arrow /></a>
                <x-calendar-menu class="btn btn--outline-navy" />
            </div>
        </div>

        <div class="venue__map-wrap" data-reveal>
            <div class="venue__map">
                <iframe src="{{ $venue['map_embed_url'] }}" title="Map showing {{ $venue['full'] }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                <div class="venue__tint" aria-hidden="true"></div>
                <a class="venue__chip" href="{{ $venue['map_link'] }}" target="_blank" rel="noopener">
                    <span class="venue__pulse" aria-hidden="true"></span>
                    Eko Hotels &amp; Suites · Open in Google Maps
                </a>
            </div>
        </div>
    </div>
</section>
