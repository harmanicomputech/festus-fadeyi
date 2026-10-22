<!DOCTYPE html>
<html lang="en-NG">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ config('event.title') }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
</head>
<body>
<main class="page page--navy on-dark" style="padding-top:clamp(3rem,10vw,6rem)">
    <div class="pattern" aria-hidden="true"></div>
    <div class="container" style="position:relative">
        <div class="reply-card status-card">
            <x-ornament />
            <p class="eyebrow">{{ config('event.subtitle') }}</p>
            <h1 class="reply-card__title" style="font-size:clamp(2rem,6vw,3rem)">Dr. Festus Fadeyi <em>at 85</em></h1>
            <p class="reply-card__sub">{{ config('event.date_label') }} · {{ config('event.venue.full') }}</p>
            <p class="reply-card__sub">The official website is being prepared. Please check back shortly.</p>
        </div>
    </div>
</main>
</body>
</html>
