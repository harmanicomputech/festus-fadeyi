@extends('layouts.admin', ['title' => 'Goodwill messages'])
@section('content')
    <div class="page-head">
        <div><p class="eyebrow">For the family</p><h1>Goodwill messages</h1><p>{{ $messages->count() }} messages to the celebrant from RSVPs and donations.</p></div>
        <div class="actions no-print"><button class="btn btn--ghost" type="button" data-print>Print</button></div>
    </div>
    <section class="panel">
        @forelse ($messages as $m)
            <article class="message">
                <blockquote>“{{ $m['message'] }}”</blockquote>
                <div class="small"><strong>{{ $m['name'] }}</strong>@if ($m['organisation']) · {{ $m['organisation'] }}@endif <span class="muted">· via {{ $m['source'] }}, {{ $m['at']?->format('j M Y') }}</span></div>
            </article>
        @empty
            <p class="muted">No messages yet.</p>
        @endforelse
    </section>
@endsection
