@php($speakers = config('event.speakers'))
<section class="section grain" id="speakers" aria-labelledby="speakers-title">
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow eyebrow--both">Speakers &amp; Dignitaries</span>
            <h2 class="display" id="speakers-title">Distinguished <em>voices</em></h2>
        </div>

        <div class="people">
            @forelse ($speakers as $i => $p)
                <article class="person reveal" style="--delay:{{ ($i % 4) * .08 }}s">
                    @if (! empty($p['photo']))
                        <div class="person__photo"><img src="{{ $p['photo'] }}" alt="Portrait of {{ $p['name'] }}" loading="lazy" decoding="async"></div>
                    @else
                        <div class="person__photo person__photo--empty"><x-motif /></div>
                    @endif
                    <span class="person__role">{{ $p['role'] }}</span>
                    <h3 class="person__name">{{ $p['name'] }}</h3>
                    @if (! empty($p['title']))<p class="person__title">{{ $p['title'] }}</p>@endif
                </article>
            @empty
                @foreach (config('event.speaker_placeholder_roles') as $i => $role)
                    <article class="person reveal" style="--delay:{{ ($i % 4) * .08 }}s" data-placeholder>
                        <div class="person__photo person__photo--empty"><x-motif /></div>
                        <span class="person__role">{{ $role }}</span>
                        <h3 class="person__name person__name--tba">To be announced</h3>
                        <p class="person__title">Name, title and portrait awaiting confirmation</p>
                    </article>
                @endforeach
            @endforelse
        </div>
    </div>
</section>
