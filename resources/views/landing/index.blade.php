@extends('layouts.site')

@section('content')
    @include('landing.sections.hero')
    @include('landing.sections.welcome')
    @include('landing.sections.celebrant')
    @include('landing.sections.book')
    @include('landing.sections.give')
    @include('landing.sections.programme')
    @include('landing.sections.venue')
    @include('landing.sections.rsvp')
    @if (count(config('event.gallery')))
        @include('landing.sections.gallery')
    @endif
@endsection
