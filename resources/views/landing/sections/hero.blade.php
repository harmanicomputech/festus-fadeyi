<section class="hero" id="top" aria-labelledby="hero-title">
    <div class="pattern pattern--fade" aria-hidden="true"></div>
    <div class="hero__frame" aria-hidden="true">
        <x-corner class="hero__corner hero__corner--tl" /><x-corner class="hero__corner hero__corner--tr" />
        <x-corner class="hero__corner hero__corner--bl" /><x-corner class="hero__corner hero__corner--br" />
    </div>

    <div class="container hero__grid">
        <div class="hero__copy">
            <span class="eyebrow hero-in">{{ config('event.subtitle') }}</span>

            <h1 class="hero__title hero-in" id="hero-title" style="--delay:.1s">
                <span class="pre">Symposium in Honour of</span>
                <span class="name">Dr. Festus Fadeyi</span>
                <span class="at">at <b>85</b></span>
            </h1>

            <x-ornament align="left" class="hero-in" style="--delay:.2s" />

            <p class="hero__book hero-in" style="--delay:.25s">
                and the presentation of the book
                <cite>{{ config('event.book_title') }}</cite>
            </p>

            <dl class="hero__details hero-in" style="--delay:.35s">
                <div><dt>Date</dt><dd class="nowrap">{{ config('event.date_label') }}</dd></div>
                <div><dt>Time</dt><dd class="nowrap">{{ config('event.time_label') }}</dd></div>
                <div><dt>Venue</dt><dd>{{ config('event.venue.full') }}</dd></div>
            </dl>

            <div class="hero__actions hero-in" style="--delay:.45s">
                <a class="btn" href="#rsvp">RSVP Now <x-arrow /></a>
                <a class="btn btn--ghost" href="#give">Donate</a>
            </div>

            <p class="hero__host hero-in" style="--delay:.55s">Hosted by <strong>{{ config('event.host') }}</strong></p>
        </div>

        <div class="hero__visual hero-in" style="--delay:.3s">
            <div class="arch-frame" aria-hidden="true"></div>
            <div class="arch">
                <picture>
                    <source type="image/webp" srcset="/images/celebrant/portrait-01-600.webp 600w, {{ config('event.images.portrait_webp') }} 1000w" sizes="(min-width: 960px) 440px, 90vw">
                    <img src="{{ config('event.images.portrait') }}" srcset="/images/celebrant/portrait-01-600.jpg 600w, {{ config('event.images.portrait') }} 1000w" sizes="(min-width: 960px) 440px, 90vw"
                         alt="{{ config('event.images.portrait_alt') }}" width="1000" height="1000" fetchpriority="high">
                </picture>
            </div>
            <div class="hero__book-card"><x-book-cover /></div>
        </div>
    </div>

    <a class="scroll-cue" href="#welcome">Scroll</a>
</section>
