<!DOCTYPE html>
<html lang="en-NG">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in · Dr. FF @ 85 Admin</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <div class="login">
        <form class="panel" method="POST" action="{{ route('admin.login.attempt') }}">
            @csrf
            <p class="eyebrow">Planning committee</p>
            <h1 style="margin:8px 0 22px">Sign in</h1>
            @if ($errors->any())<div class="flash flash--error" role="alert">{{ $errors->first() }}</div>@endif
            <div class="field" style="margin-bottom:12px">
                <label for="email">Email</label>
                <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>
            <div class="field" style="margin-bottom:20px">
                <label for="password">Password</label>
                <input class="input" id="password" name="password" type="password" required autocomplete="current-password">
            </div>
            <button class="btn btn--gold" type="submit" style="width:100%">Sign in</button>
            <p class="small muted" style="margin:16px 0 0">Authorised event administrators only. Access is logged.</p>
        </form>
    </div>
</body>
</html>
