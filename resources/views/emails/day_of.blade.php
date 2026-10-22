@extends('emails.layout')
@section('preheader')Today: {{ $event['time_label'] }} at {{ $event['venue']['full'] }}.@endsection
@section('body')
    @include('emails._heading', ['eyebrow' => 'Today', 'title' => 'Welcome — <em style="color:#7D5F24;">today is the day</em>'])
    <p style="margin:0 0 16px;">Dear {{ $name }},</p>
    @if ($event['email']['copy']['day_of'])
        @include('emails._copy', ['copy' => $event['email']['copy']['day_of']])
    @else
        <p style="margin:0 0 16px;">We look forward to welcoming you this morning. Proceedings begin at {{ \Illuminate\Support\Str::before($event['time_label'], ' –') }} at {{ $event['venue']['full'] }}.</p>
    @endif
    @if ($event['venue']['parking_notes'])
        <p style="margin:0 0 16px;"><strong style="color:#0B1730;">Parking:</strong> {{ $event['venue']['parking_notes'] }}</p>
    @endif
    @if ($event['venue']['arrival_notes'])
        <p style="margin:0 0 16px;"><strong style="color:#0B1730;">On arrival:</strong> {{ $event['venue']['arrival_notes'] }}</p>
    @endif
    @include('emails._qr')
    @include('emails._button', ['href' => $event['venue']['map_link'], 'label' => 'Directions', 'variant' => 'navy'])
@endsection
