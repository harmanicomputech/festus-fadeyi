@php($cover = config('event.images.book_cover'))
@if ($cover)
    <div {{ $attributes->class('book-cover') }}>
        <img src="{{ $cover }}" alt="Cover of {{ config('event.book_title') }}" loading="lazy" decoding="async">
    </div>
@else
    {{-- Typographic stand-in until the official cover artwork is supplied. --}}
    <div {{ $attributes->class(['book-cover', 'book-cover--placeholder']) }} role="img" aria-label="Book cover placeholder — official cover artwork to be supplied">
        <x-motif class="book-cover__mark" />
        <span class="book-cover__title">{{ config('event.book_title') }}</span>
        <span class="book-cover__note">Cover artwork<br>to be supplied</span>
    </div>
@endif
