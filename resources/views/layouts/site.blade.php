@php($home = request()->routeIs('home'))
@php($anchor = fn ($id) => $home ? '#'.$id : route('home').'#'.$id)
<!DOCTYPE html>
<html lang="en-NG">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('event.title') }}</title>
    <meta name="description" content="{{ config('event.subtitle') }} — {{ config('event.date_label') }}, {{ config('event.venue.full') }}. Hosted by {{ config('event.host') }}.">
    <meta name="theme-color" content="#0B1730">
    @if (! empty($noindex))<meta name="robots" content="noindex, nofollow">@endif
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('event.title') }}">
    <meta property="og:description" content="{{ config('event.subtitle') }} · {{ config('event.book_title') }} · {{ config('event.date_label') }}">
    <meta property="og:image" content="{{ url(config('event.images.og_image')) }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
    @stack('head')
    <script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}" defer></script>
</head>
<body class="{{ $bodyClass ?? '' }}">
    <a class="skip-link" href="#main">Skip to content</a>

    <header class="site-header {{ $home ? '' : 'is-solid' }}" data-header>
        <div class="container">
            <a class="brand" href="{{ $home ? '#top' : route('home') }}" aria-label="{{ config('event.title') }} — home">
                <span class="brand__mark" aria-hidden="true"><span>85</span></span>
                <span class="brand__text">Dr. Festus Fadeyi<small>at Eighty-Five</small></span>
            </a>
            <nav class="nav" aria-label="Primary">
                <ul>
                    <li><a href="{{ $anchor('celebrant') }}" data-nav>Celebrant</a></li>
                    <li><a href="{{ $anchor('book') }}" data-nav>The Book</a></li>
                    <li><a href="{{ $anchor('give') }}" data-nav>Give</a></li>
                    <li><a href="{{ $anchor('programme') }}" data-nav>Programme</a></li>
                    <li><a href="{{ $anchor('venue') }}" data-nav>Venue</a></li>
                    <li><a class="btn btn--sm" href="{{ $anchor('rsvp') }}">RSVP</a></li>
                </ul>
            </nav>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="mobile-nav" data-nav-toggle>
                <span></span><span class="sr-only">Menu</span>
            </button>
        </div>
    </header>
    <div class="mobile-nav" id="mobile-nav" data-mobile-nav>
        <div class="pattern" aria-hidden="true"></div>
        <nav aria-label="Mobile">
            <ul>
                <li><a href="{{ $anchor('welcome') }}">Welcome</a></li>
                <li><a href="{{ $anchor('celebrant') }}">The Celebrant</a></li>
                <li><a href="{{ $anchor('book') }}">The Book</a></li>
                <li><a href="{{ $anchor('give') }}">Give</a></li>
                <li><a href="{{ $anchor('programme') }}">Programme</a></li>
                <li><a href="{{ $anchor('speakers') }}">Speakers</a></li>
                <li><a href="{{ $anchor('venue') }}">Venue</a></li>
                <li><a href="{{ $anchor('gallery') }}">Gallery</a></li>
            </ul>
            <a class="btn btn--block" href="{{ $anchor('rsvp') }}">RSVP Now</a>
        </nav>
    </div>

    <main id="main">
        @yield('content')
    </main>

    @include('partials.footer')
</body>
</html>
