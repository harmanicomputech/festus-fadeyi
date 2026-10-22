@php($photos = collect(config('event.gallery')))
<section class="section grain" id="gallery" aria-labelledby="gallery-title">
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow eyebrow--both">Gallery</span>
            <h2 class="display" id="gallery-title">Moments &amp; <em>memories</em></h2>
        </div>

        @if ($photos->isEmpty())
            <div class="gallery-empty reveal" aria-hidden="true"><div></div><div></div><div></div></div>
            <x-placeholder class="reveal" label="Archive photographs" style="max-width:620px;margin:3rem auto 0;text-align:center;justify-items:center">
                Archive photographs of Dr. Fadeyi will appear here, and photographs from the celebration will be added after the event.
            </x-placeholder>
        @else
            @foreach (['archive' => 'From the archive', 'event' => 'From the celebration'] as $group => $heading)
                @php($items = $photos->where('group', $group))
                @if ($items->isNotEmpty())
                    <div class="gallery-group">
                        <h3>{{ $heading }}</h3>
                        <div class="gallery">
                            @foreach ($items as $photo)
                                <figure class="reveal">
                                    <img src="{{ $photo['src'] }}" alt="{{ $photo['alt'] }}" loading="lazy" decoding="async">
                                    @if (! empty($photo['caption']))<figcaption>{{ $photo['caption'] }}</figcaption>@endif
                                </figure>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
</section>
