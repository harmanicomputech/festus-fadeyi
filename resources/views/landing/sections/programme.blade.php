@php($roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'])
<section class="section section--navy programme on-dark" id="programme" aria-labelledby="programme-title">
    <div class="pattern pattern--fade" aria-hidden="true"></div>
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow eyebrow--both">Programme</span>
            <h2 class="display" id="programme-title">Order of <em>proceedings</em></h2>
            <p class="lede">{{ config('event.date_label') }} · {{ config('event.time_label') }}</p>
        </div>

        <ol class="timeline">
            @foreach (config('event.programme') as $i => $item)
                <li class="reveal" style="--delay:{{ ($i % 4) * .06 }}s">
                    <span class="timeline__time">
                        @if ($item['time']){{ $item['time'] }}@else<span class="tbc">Time to be confirmed</span>@endif
                    </span>
                    <div class="timeline__body">
                        <h3 class="timeline__title"><span class="timeline__num">{{ $roman[$i] ?? $i + 1 }}.</span>{{ $item['category'] }}</h3>
                        @if ($item['detail'])
                            <p class="timeline__detail">{{ $item['detail'] }}</p>
                        @else
                            <p class="timeline__detail"><span class="tbc">Details to be announced</span></p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>

        <p class="fine-print reveal" style="text-align:center;margin-top:3rem">The final programme, speakers and timings will be published here once confirmed by the planning committee.</p>
    </div>
</section>
