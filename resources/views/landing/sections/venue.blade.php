@php($venue = config('event.venue'))
<section class="section section--ivory-2" id="venue" aria-labelledby="venue-title">
    <div class="container venue__grid">
        <div>
            <span class="eyebrow reveal">Venue &amp; Directions</span>
            <h2 class="venue__name reveal" id="venue-title">{{ $venue['name'] }}</h2>
            <p class="venue__area reveal">{{ $venue['area'] }}</p>

            <dl class="facts reveal">
                <div><dt>Date</dt><dd>{{ config('event.date_label') }}</dd></div>
                <div><dt>Time</dt><dd>{{ config('event.time_label') }} (Lagos time)</dd></div>
                <div><dt>Address</dt><dd>{{ $venue['full'] }}</dd></div>
                <div><dt>Dress code</dt><dd>{{ config('event.dress_code') }}</dd></div>
                <div><dt>Parking</dt><dd>@if ($venue['parking_notes']){{ $venue['parking_notes'] }}@else<x-placeholder inline label="Parking information to be confirmed" />@endif</dd></div>
                <div><dt>Arrival</dt><dd>@if ($venue['arrival_notes']){{ $venue['arrival_notes'] }}@else<x-placeholder inline label="Arrival notes to be confirmed" />@endif</dd></div>
            </dl>

            <div class="reveal" style="display:flex;flex-wrap:wrap;gap:.8rem;margin-top:2rem">
                <a class="btn btn--outline-navy btn--sm" href="{{ $venue['map_link'] }}" target="_blank" rel="noopener">Open in Google Maps</a>
                <x-calendar-menu class="btn btn--outline-navy btn--sm" />
            </div>
        </div>

        <div class="map reveal">
            <iframe src="{{ $venue['map_embed_url'] }}" title="Map showing {{ $venue['full'] }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        </div>
    </div>
</section>
