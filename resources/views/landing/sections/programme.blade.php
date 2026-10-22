@php($items = config('event.programme'))
@php($tbc = collect($items)->contains(fn ($i) => ! $i['time']))
<section class="section section--navy programme on-dark" id="programme" aria-labelledby="programme-title">
    <div class="pattern pattern--fade" aria-hidden="true"></div>
    <div class="container">
        <div class="section-head">
            <span class="kicker" data-reveal>Programme</span>
            <h2 class="h2" id="programme-title" data-split>Order of <em>proceedings</em></h2>
            @if ($tbc)<div data-reveal><x-placeholder label="Timings" /></div>@endif
        </div>

        <ol class="prog" data-prog>
            @foreach ($items as $i => $item)
                <li data-reveal-stagger>
                    <span class="prog__num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <div>
                        <h3 class="prog__title">{{ $item['category'] }}</h3>
                        @if ($item['time'] || $item['detail'])
                            <p class="prog__time" style="margin:.4rem 0 0">{{ collect([$item['time'], $item['detail']])->filter()->implode(' · ') }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>
