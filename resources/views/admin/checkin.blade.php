@extends('layouts.admin', ['title' => 'Check-in'])
@push('head')<script src="{{ asset('vendor/jsQR.js') }}" defer></script><script src="{{ asset('js/checkin.js') }}?v={{ filemtime(public_path('js/checkin.js')) }}" defer></script>@endpush
@section('content')
    <div class="desk" data-checkin data-lookup-url="{{ route('admin.checkin.lookup') }}" data-search-url="{{ route('admin.checkin.search') }}" data-csrf="{{ csrf_token() }}">
        <div class="page-head">
            <div><p class="eyebrow">Welcome desk</p><h1>Check-in</h1></div>
            <div class="stat" style="padding:10px 16px"><div class="stat__label">Arrived</div><div class="stat__value" style="font-size:1.8rem"><span data-count>{{ $checkedIn }}</span> <span class="stat__sub">/ <span data-total>{{ $confirmed }}</span> confirmed</span></div></div>
        </div>

        <div class="scanner">
            <video data-video playsinline muted></video>
            <div class="scanner__frame" aria-hidden="true"></div>
            <div class="scanner__idle" data-idle>
                <div>
                    <p style="margin:0 0 12px">Scan the guest’s QR code from their email or phone.</p>
                    <button class="btn btn--gold btn--lg" type="button" data-start>Start camera</button>
                    <p class="small" style="margin:12px 0 0;opacity:.8" data-camera-msg></p>
                </div>
            </div>
        </div>

        <div class="result" data-result role="status" aria-live="assertive">
            <p class="result__title" data-result-title></p>
            <p class="result__name" data-result-name></p>
            <p data-result-detail style="margin:0 0 12px"></p>
            <div class="actions">
                <button class="btn btn--gold btn--lg" type="button" data-admit hidden>Confirm arrival</button>
                <button class="btn btn--ghost btn--lg" type="button" data-next>Next guest</button>
            </div>
        </div>

        <div class="grid grid--2" style="margin-top:20px">
            <form class="panel" data-manual>
                <h3>Enter code manually</h3>
                <div class="actions"><input class="input" name="code" autocomplete="off" aria-label="Check-in code" style="flex:1"><button class="btn" type="submit">Check</button></div>
            </form>
            <form class="panel" data-search>
                <h3>Find by name, email or phone</h3>
                <input class="input" name="q" type="search" autocomplete="off" aria-label="Search guests">
                <ul class="search-results" data-search-results></ul>
            </form>
        </div>
    </div>
@endsection
