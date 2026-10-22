@php($photos = collect(config('event.gallery')))
<section class="section section--ivory-2" id="gallery" aria-labelledby="gallery-title">
    <div class="container">
        <div class="section-head">
            <span class="kicker" data-reveal>Gallery</span>
            <h2 class="h2" id="gallery-title" data-split>Moments &amp; <em>memories</em></h2>
        </div>
        @foreach (['archive' => 'From the archive', 'event' => 'From the celebration'] as $group => $heading)
            @php($items = $photos->where('group', $group))
            @if ($items->isNotEmpty())
                <div class="gallery-group">
                    <h3>{{ $heading }}</h3>
                    <div class="gallery">
                        @foreach ($items as $photo)
                            <figure data-reveal>
                                <img src="{{ $photo['src'] }}" alt="{{ $photo['alt'] }}" loading="lazy" decoding="async">
                                @if (! empty($photo['caption']))<figcaption>{{ $photo['caption'] }}</figcaption>@endif
                            </figure>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</section>
