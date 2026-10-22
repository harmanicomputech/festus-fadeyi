<section class="section section--ivory-2" id="celebrant" aria-labelledby="celebrant-title">
    <div class="container">
        <div class="about__grid">
            <div class="about__portrait">
                <figure class="portrait reveal">
                    <div class="portrait__img reveal-img">
                        <picture>
                            <source type="image/webp" srcset="/images/celebrant/portrait-01-600.webp 600w, {{ config('event.images.portrait_webp') }} 1000w" sizes="(min-width: 900px) 40vw, 92vw">
                            <img src="{{ config('event.images.portrait') }}" srcset="/images/celebrant/portrait-01-600.jpg 600w, {{ config('event.images.portrait') }} 1000w" sizes="(min-width: 900px) 40vw, 92vw"
                                 alt="{{ config('event.images.portrait_alt') }}" width="1000" height="1000" loading="lazy" decoding="async">
                        </picture>
                    </div>
                    <figcaption>{{ config('event.celebrant') }}</figcaption>
                </figure>
            </div>

            <div>
                <span class="eyebrow reveal">The Celebrant</span>
                <h2 class="about__name reveal" id="celebrant-title">Dr. Festus Fadeyi</h2>
                <p class="about__role reveal">Pioneering oil executive · Pathfinder Award recipient, Nigerian Oil and Gas Hall of Fame</p>
                <p class="bio reveal">{{ config('event.biography') }}</p>
            </div>
        </div>

        <ol class="milestones" aria-label="Highlights">
            @foreach (config('event.milestones') as $i => $m)
                <li class="reveal" style="--delay:{{ $i * .08 }}s">
                    @if ($m['year'])
                        <span class="year">{{ $m['year'] }}</span>
                    @else
                        <span class="year--mark" aria-hidden="true"></span>
                    @endif
                    <p>{{ $m['text'] }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>
