@extends('layouts.site')

@section('content')
    @include('landing.sections.hero')
    @include('landing.sections.welcome')
    @include('landing.sections.celebrant')
    @include('landing.sections.book')
    @include('landing.sections.give')
    @include('landing.sections.programme')
    @include('landing.sections.speakers')
    @include('landing.sections.venue')
    @include('landing.sections.rsvp')
    @include('landing.sections.gallery')
@endsection
