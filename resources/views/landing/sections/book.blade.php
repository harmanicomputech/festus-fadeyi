@php($book = config('event.book'))
<section class="section section--forest book on-dark" id="book" aria-labelledby="book-title">
    <div class="pattern pattern--edge" aria-hidden="true"></div>
    <div class="container book__grid">
        <div class="book__cover-wrap reveal">
            <x-book-cover />
        </div>

        <div>
            <span class="eyebrow reveal">The Book</span>
            <h2 class="book__title reveal" id="book-title">{{ config('event.book_title') }}</h2>

            @if ($book['author'])
                <p class="book__author reveal">By {{ $book['author'] }}</p>
            @else
                <div class="reveal" style="margin-bottom:1.8rem"><x-placeholder inline label="Author’s name to be confirmed" /></div>
            @endif

            @if ($book['synopsis'])
                <p class="book__synopsis reveal">{{ $book['synopsis'] }}</p>
            @else
                <x-placeholder class="reveal" label="Synopsis">A two-to-three-line synopsis will appear here once approved.</x-placeholder>
            @endif

            <p class="book__note reveal">
                <strong>A gift that gives onward.</strong>
                Donations made at the presentation of <em>{{ config('event.book_title') }}</em> go in full to {{ \App\Support\EventInfo::charityLabel() }}.
                @if ($book['purchase_supports_charity'])
                    Proceeds from sales of the book will also support the charity.
                @endif
            </p>

            <div class="reveal"><a class="btn" href="#give">Donate <x-arrow /></a></div>
        </div>
    </div>
</section>
