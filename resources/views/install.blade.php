<!DOCTYPE html>
<html lang="en-NG">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Install · Dr. FF @ 85</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500&family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script src="{{ asset('js/admin.js') }}" defer></script>
</head>
<body>
<header class="topbar"><div class="topbar__inner"><span class="topbar__brand">Dr. FF <span>@ 85</span> · Installer</span></div></header>
<main class="wrap" style="max-width:900px">
    <div class="page-head"><div><p class="eyebrow">One-time setup</p><h1>Install the event website</h1>
        <p>Work through the steps in order. When finished, the installer switches itself off.</p></div></div>

    @if (session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="flash flash--error" role="alert">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="flash flash--error" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    @if (session('output'))<pre class="panel small" style="white-space:pre-wrap;margin-bottom:18px">{{ session('output') }}</pre>@endif

    @php($blocking = collect($requirements)->where('required', true)->where('ok', false)->count())
    <section class="panel" style="margin-bottom:16px">
        <h2>1. Server check</h2>
        <table class="table small">
            @foreach ($requirements as $r)
                <tr><td>{{ $r['label'] }}</td><td class="num"><span class="badge badge--{{ $r['ok'] ? 'on' : ($r['required'] ? 'failed' : 'pending') }}">{{ $r['ok'] ? 'OK' : ($r['required'] ? 'Required' : 'Recommended') }}</span></td></tr>
            @endforeach
        </table>
        @if ($blocking)<p class="small" style="color:var(--danger)">Fix the items marked <strong>Required</strong> (DirectAdmin → PHP version selector / file permissions), then reload this page.</p>@endif
    </section>

    <section class="panel" style="margin-bottom:16px">
        <h2>2. Database</h2>
        @if (! $dbOk)
            <div class="flash flash--error">Cannot connect to the database. Check <code>DB_HOST</code>, <code>DB_DATABASE</code>, <code>DB_USERNAME</code> and <code>DB_PASSWORD</code> in <code>ff85-app/.env</code>.<br><span class="small">{{ $dbError }}</span></div>
        @else
            <p>Connected to <strong>{{ config('database.connections.'.config('database.default').'.database') }}</strong>.
                @if ($pending) {{ count($pending) }} setup step(s) to apply. @else Database tables are up to date. @endif</p>
            @if ($pending)
                <form method="POST" action="{{ route('install.migrate') }}" data-confirm="Create/update the database tables now?">
                    @csrf<input type="hidden" name="token" value="{{ $token }}">
                    <button class="btn btn--gold" type="submit" @disabled($blocking)>Set up database tables</button>
                </form>
            @endif
        @endif
    </section>

    <section class="panel" style="margin-bottom:16px">
        <h2>3. Administrator account</h2>
        @if (! $tablesReady)
            <p class="muted">Complete step 2 first.</p>
        @else
            @if ($admins->isNotEmpty())
                <p class="small">Existing accounts: @foreach ($admins as $a){{ $a->email }} ({{ $a->role }})@if (! $loop->last), @endif @endforeach</p>
            @endif
            <form method="POST" action="{{ route('install.admin') }}" class="form-grid">
                @csrf<input type="hidden" name="token" value="{{ $token }}">
                <div class="field"><label for="name">Full name</label><input class="input" id="name" name="name" required value="{{ old('name') }}"></div>
                <div class="field"><label for="email">Email (used to sign in)</label><input class="input" id="email" name="email" type="email" required value="{{ old('email') }}"></div>
                <div class="field"><label for="password">Password</label><input class="input" id="password" name="password" type="password" required autocomplete="new-password"></div>
                <div class="field"><label for="password_confirmation">Confirm password</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"></div>
                <p class="small muted full" style="margin:0">At least 12 characters with upper- and lower-case letters and a number. More accounts (including check-in staff) can be added later under Admin → Users.</p>
                <div class="field full"><button class="btn" type="submit">Save administrator</button></div>
            </form>
        @endif
    </section>

    <section class="panel">
        <h2>4. Finish</h2>
        <p>This locks the installer (creates <code>storage/app/installed.lock</code>) and takes you to the admin sign-in.</p>
        <form method="POST" action="{{ route('install.finish') }}">
            @csrf<input type="hidden" name="token" value="{{ $token }}">
            <button class="btn btn--gold" type="submit" @disabled(! $tablesReady || $pending || $admins->where('role', 'admin')->isEmpty())>Finish installation</button>
        </form>
    </section>
</main>
</body>
</html>
