@extends('layouts.site', ['title' => $heading, 'noindex' => true])

@section('content')
<section class="page page--navy on-dark">
    <div class="pattern" aria-hidden="true"></div>
    <div class="container" style="position:relative">
        <div class="reply-card status-card">
            <x-ornament />
            <h1 class="reply-card__title" style="font-size:2.4rem">{{ $heading }}</h1>
            <p class="reply-card__sub">{{ $body }}</p>
            <p><a class="link-gold" href="{{ route('home') }}">Return to the celebration</a></p>
        </div>
    </div>
</section>
@endsection
