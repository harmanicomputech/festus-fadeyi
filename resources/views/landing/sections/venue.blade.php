@php($venue = config('event.venue'))
<section class="section venue" id="venue" aria-labelledby="venue-title">
    <div class="container">
        <div class="section-head">
            <span class="kicker" data-reveal>Venue</span>
            <h2 class="h2" id="venue-title" data-split>Sky Restaurant, <em>Eko Hotels</em></h2>
        </div>
    </div>

    <div class="venue__map" data-reveal>
        <iframe src="{{ $venue['map_embed_url'] }}" title="Map showing {{ $venue['full'] }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        <div class="venue__card on-dark">
            <h3>{{ $venue['name'] }}</h3>
            <p class="area">{{ $venue['area'] }}</p>
            <dl class="facts">
                <div><dt>When</dt><dd>{{ config('event.date_label') }}<br>{{ config('event.time_label') }}</dd></div>
                <div><dt>Dress</dt><dd>{{ config('event.dress_code') }}</dd></div>
                @if ($venue['parking_notes'])<div><dt>Parking</dt><dd>{{ $venue['parking_notes'] }}</dd></div>@endif
                @if ($venue['arrival_notes'])<div><dt>Arrival</dt><dd>{{ $venue['arrival_notes'] }}</dd></div>@endif
            </dl>
            @if (! $venue['parking_notes'] || ! $venue['arrival_notes'])
                <x-placeholder label="Parking and arrival notes" />
            @endif
            <div class="venue__actions">
                <a class="btn btn--sm" href="{{ $venue['map_link'] }}" target="_blank" rel="noopener">Directions</a>
                <x-calendar-menu class="btn btn--ghost btn--sm" up />
            </div>
        </div>
    </div>
</section>
