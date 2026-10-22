@php($welcome = config('event.welcome'))
<section class="section welcome" id="welcome" aria-labelledby="welcome-title">
    <div class="container container--narrow">
        <div class="section-head section-head--center">
            <span class="eyebrow eyebrow--both" id="welcome-title" data-reveal>Welcome</span>
        </div>

        @if ($welcome['message'])
            <div data-scrub-words>
                @foreach (preg_split('/\n\s*\n/', trim($welcome['message'])) as $para)
                    <p class="welcome__text">{{ $para }}</p>
                @endforeach
            </div>
            @if ($welcome['signed_by'])
                <p class="welcome__sign" data-reveal>{{ $welcome['signed_by'] }}</p>
            @endif
        @else
            <p class="welcome__text" data-split>With gratitude and joy, we celebrate</p>
            <p class="welcome__text" data-split style="margin-top:.2em"><span class="gold">eighty-five years</span>.</p>
            <p style="margin-top:2.5rem" data-reveal><x-placeholder label="Welcome note from the host or family" /></p>
        @endif
    </div>
</section>
