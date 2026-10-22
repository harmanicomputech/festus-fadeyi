@php
    $bio = config('event.biography');
    // First sentence as the lead (skips abbreviations such as "Dr.")
    preg_match('/^(.{40,}?\.)\s+(?=[A-Z])(.*)$/s', $bio, $m);
    $lead = $m[1] ?? $bio;
    $rest = $m[2] ?? '';
@endphp
<section class="section section--ivory-2" id="celebrant" aria-labelledby="celebrant-title">
    <div class="container">
        <div class="about__grid">
            <div class="about__media" data-reveal>
                <div class="about__frame">
                    <picture>
                        <source type="image/webp" srcset="/images/celebrant/portrait-01-600.webp 600w, {{ config('event.images.portrait_webp') }} 1000w" sizes="(min-width: 900px) 40vw, 92vw">
                        <img src="{{ config('event.images.portrait') }}" srcset="/images/celebrant/portrait-01-600.jpg 600w, {{ config('event.images.portrait') }} 1000w" sizes="(min-width: 900px) 40vw, 92vw"
                             alt="{{ config('event.images.portrait_alt') }}" width="1000" height="1000" loading="lazy" decoding="async" data-parallax-img>
                    </picture>
                </div>
                <div class="about__tag"><b>85</b><span>Years</span></div>
            </div>

            <div>
                <span class="kicker" data-reveal>The Celebrant</span>
                <h2 class="h2 about__name" id="celebrant-title" data-split>Dr. Festus <em>Fadeyi</em></h2>
                <p class="about__lead" data-reveal>{{ $lead }}</p>
                @if ($rest)
                    <details class="about__more" data-reveal>
                        <summary>Read full biography</summary>
                        <p class="about__body">{{ $rest }}</p>
                    </details>
                @endif
            </div>
        </div>
    </div>

    <div class="milestones-wrap" data-milestones>
        <ol class="milestones" aria-label="Highlights" data-milestones-track>
            @foreach (config('event.milestones') as $m)
                <li class="milestone">
                    @if ($m['year'])
                        <span class="milestone__year">{{ $m['year'] }}</span>
                    @else
                        <span class="milestone__year milestone__year--star" aria-hidden="true">★</span>
                    @endif
                    <p>{{ $m['text'] }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>
