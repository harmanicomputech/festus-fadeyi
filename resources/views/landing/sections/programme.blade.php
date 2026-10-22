@php($items = config('event.programme'))
<section class="section section--navy programme on-dark" id="programme" aria-labelledby="programme-title">
    <div class="pattern pattern--fade" aria-hidden="true"></div>
    <div class="container">
        <div class="section-head">
            <span class="kicker" data-reveal>Programme · {{ config('event.time_label') }}</span>
            <h2 class="h2" id="programme-title" data-split>Order of <em>proceedings</em></h2>
        </div>

        <ol class="prog" data-prog>
            @foreach ($items as $i => $item)
                <li data-reveal-stagger>
                    <div class="prog__top">
                        <span class="prog__num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        @if ($item['time'])<time class="prog__time">{{ $item['time'] }}</time>@endif
                    </div>
                    <div>
                        <h3 class="prog__title">{{ $item['category'] }}</h3>
                        @if ($item['detail'])<p class="prog__detail">{{ $item['detail'] }}</p>@endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>
