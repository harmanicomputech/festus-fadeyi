<!DOCTYPE html>
<html lang="en-NG">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Dashboard' }} · Dr. FF @ 85 Admin</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500&family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    <script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}" defer></script>
    @stack('head')
</head>
<body>
    @auth
        <header class="topbar">
            <div class="topbar__inner">
                <a class="topbar__brand" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('admin.checkin') }}">Dr. FF <span>@ 85</span></a>
                <nav aria-label="Administration">
                    @if (auth()->user()->isAdmin())
                        @foreach (['admin.dashboard' => 'Overview', 'admin.guests' => 'Guests', 'admin.invitations' => 'Invitations', 'admin.donations' => 'Donations', 'admin.messages' => 'Messages', 'admin.emails' => 'Emails', 'admin.reports' => 'Reports'] as $route => $label)
                            <a href="{{ route($route) }}" @class(['is-active' => request()->routeIs($route.'*')])>{{ $label }}</a>
                        @endforeach
                    @endif
                    <a href="{{ route('admin.checkin') }}" @class(['is-active' => request()->routeIs('admin.checkin*')])>Check-in</a>
                </nav>
                <span class="topbar__user">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit">Sign out</button></form>
            </div>
        </header>
    @endauth

    <main class="wrap">
        @if (session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="flash flash--error" role="alert">{{ session('error') }}</div>@endif
        @if ($errors->any())
            <div class="flash flash--error" role="alert">
                Please correct the following:
                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
