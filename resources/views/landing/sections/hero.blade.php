@php($start = \App\Support\EventInfo::startsAt()->utc()->toIso8601String())
<section class="hero" id="top" aria-labelledby="hero-title" data-hero>
    <canvas class="hero__particles" data-particles aria-hidden="true"></canvas>
    <div class="hero__glow" data-glow aria-hidden="true"></div>
    <div class="pattern pattern--fade" aria-hidden="true"></div>

    <div class="container hero__grid">
        <div class="hero__copy" data-hero-copy>
            <p class="hero__kicker" data-hero-item>Symposium in Honour of</p>

            <h1 class="hero__title" id="hero-title">
                <span class="line"><span>Dr. Festus</span></span>
                <span class="line"><span>Fadeyi <span class="at">at</span> <span class="num">85</span></span></span>
            </h1>

            <p class="hero__sub" data-hero-item>A Symposium in Honour &amp; Book Presentation of <strong>{{ config('event.book_title') }}</strong></p>

            <div class="hero__actions" data-hero-item>
                <a class="btn" href="#rsvp" data-magnetic>RSVP Now <x-arrow /></a>
                <a class="btn btn--ghost" href="#give" data-magnetic>Donate</a>
            </div>

            <ul class="hero__meta" data-hero-item>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>{{ config('event.date_label') }}</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ config('event.time_label') }}</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ config('event.venue.full') }}</li>
            </ul>

            @unless (\App\Support\EventInfo::eventOver())
                <ol class="countdown" data-countdown="{{ $start }}" data-hero-item aria-label="Time until the event">
                    <li><b data-unit="days">--</b><span>Days</span></li>
                    <li><b data-unit="hours">--</b><span>Hours</span></li>
                    <li><b data-unit="minutes">--</b><span>Mins</span></li>
                    <li><b data-unit="seconds">--</b><span>Secs</span></li>
                </ol>
            @endunless

            <p class="sr-only">Hosted by {{ config('event.host') }}</p>
        </div>

        <div class="hero__visual" data-hero-visual data-tilt>
            <div class="hero__arch" aria-hidden="true"></div>
            <div class="hero__portrait" data-depth="0.25">
                <picture>
                    <source type="image/webp" srcset="/images/celebrant/portrait-01-600.webp 600w, {{ config('event.images.portrait_webp') }} 1000w" sizes="(min-width: 960px) 470px, 75vw">
                    <img src="{{ config('event.images.portrait') }}" srcset="/images/celebrant/portrait-01-600.jpg 600w, {{ config('event.images.portrait') }} 1000w" sizes="(min-width: 960px) 470px, 75vw"
                         alt="{{ config('event.images.portrait_alt') }}" width="1000" height="1000" fetchpriority="high" data-hero-img>
                </picture>
            </div>
            <div class="hero__badge" data-depth="0.9" aria-hidden="true">
                <svg viewBox="0 0 200 200"><defs><path id="badge-circle" d="M100,100 m-78,0 a78,78 0 1,1 156,0 a78,78 0 1,1 -156,0"/></defs>
                    <circle cx="100" cy="100" r="98" fill="none" stroke="currentColor" stroke-opacity=".35"/>
                    <text font-size="13" font-weight="700" fill="currentColor"><textPath href="#badge-circle" textLength="486" lengthAdjust="spacing">SYMPOSIUM IN HONOUR · BOOK PRESENTATION ·</textPath></text></svg>
                <b>85</b>
            </div>
            <div class="hero__book" data-depth="1.2"><x-book-cover /></div>
        </div>
    </div>

    <div class="marquee" aria-hidden="true">
        <div class="marquee__track">
            @for ($i = 0; $i < 2; $i++)
                <span>Symposium in Honour</span><span>{{ config('event.book_title') }}</span><span>{{ config('event.date_label') }}</span><span>Eko Hotels, Lagos</span><span>Dr. Festus Fadeyi at 85</span>
            @endfor
        </div>
    </div>

    <a class="scroll-cue" href="#welcome">Scroll</a>
</section>
