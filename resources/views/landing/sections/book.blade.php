@php($book = config('event.book'))
<section class="section section--forest on-dark" id="book" aria-labelledby="book-title">
    <div class="pattern pattern--fade" aria-hidden="true"></div>
    <div class="container book__grid">
        <div class="book__stage" data-reveal>
            <div data-book-tilt><x-book-cover /></div>
            <div class="book__shadow" aria-hidden="true"></div>
        </div>

        <div>
            <span class="kicker" data-reveal>The Book</span>
            <h2 class="book__title" id="book-title" data-split>Forged in <b>His Fire</b></h2>

            @if ($book['author'])<p class="book__author" data-reveal>By {{ $book['author'] }}</p>@endif
            @if ($book['synopsis'])<p class="book__synopsis" data-reveal>{{ $book['synopsis'] }}</p>@endif

            <p class="book__note" data-reveal>Donations at the book presentation go to {{ \App\Support\EventInfo::charityLabel() }}.@if ($book['purchase_supports_charity']) Book sales also support the charity.@endif</p>
            <div data-reveal><a class="btn" href="#give" data-magnetic>Donate <x-arrow /></a></div>
        </div>
    </div>
</section>
