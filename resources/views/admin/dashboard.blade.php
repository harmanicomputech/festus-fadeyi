@extends('layouts.admin', ['title' => 'Overview'])
@php($n = fn ($s, $k = 'n') => (int) ($byStatus[$s]->{$k} ?? 0))
@php($naira = fn ($k) => \App\Support\EventInfo::naira($k))
@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">{{ config('event.date_label') }} · {{ config('event.venue.name') }}</p>
            <h1>Overview</h1>
            <p>RSVPs {{ $rsvpOpen ? 'open until '.config('event.rsvp.deadline_label') : 'closed' }} · Invite-only mode {{ $inviteOnly ? 'on' : 'off' }}</p>
        </div>
        <div class="actions">
            <a class="btn btn--ghost" href="{{ route('admin.guests.export', 'xlsx') }}">Export guests</a>
            <a class="btn btn--ghost" href="{{ route('admin.donations.export', 'xlsx') }}">Export donations</a>
            <a class="btn btn--gold" href="{{ route('admin.checkin') }}">Open check-in desk</a>
        </div>
    </div>

    <div class="grid grid--stats" style="margin-bottom:16px">
        <div class="stat">
            <div class="stat__label">Seats confirmed</div>
            <div class="stat__value">{{ $seatsUsed }} <span class="stat__sub">/ {{ $capacity }}</span></div>
            <div class="meter" aria-hidden="true"><span style="width:{{ min(100, $capacity ? round($seatsUsed / $capacity * 100) : 0) }}%"></span></div>
        </div>
        <a class="stat" href="{{ route('admin.guests', ['status' => 'confirmed']) }}"><div class="stat__label">Accepted</div><div class="stat__value">{{ $n('confirmed') }}</div><div class="stat__sub">replies</div></a>
        <a class="stat" href="{{ route('admin.guests', ['status' => 'maybe']) }}"><div class="stat__label">Maybe</div><div class="stat__value">{{ $n('maybe') }}</div></a>
        <a class="stat" href="{{ route('admin.guests', ['status' => 'declined']) }}"><div class="stat__label">Declined</div><div class="stat__value">{{ $n('declined') }}</div></a>
        <a class="stat" href="{{ route('admin.guests', ['status' => 'waitlisted']) }}"><div class="stat__label">Waitlisted</div><div class="stat__value">{{ $n('waitlisted') }}</div><div class="stat__sub">{{ $n('waitlisted', 'seats') }} seats</div></a>
        <a class="stat" href="{{ route('admin.guests', ['status' => 'pending']) }}"><div class="stat__label">No reply yet</div><div class="stat__value">{{ $pendingInvitations }}</div><div class="stat__sub">of {{ $invitations }} invited</div></a>
        <a class="stat" href="{{ route('admin.guests', ['status' => 'checked_in']) }}"><div class="stat__label">Checked in</div><div class="stat__value">{{ $checkedIn }}</div><div class="stat__sub">{{ $checkedInSeats }} seats</div></a>
    </div>

    <div class="grid grid--stats" style="margin-bottom:24px">
        <a class="stat" href="{{ route('admin.donations') }}"><div class="stat__label">Verified donations</div><div class="stat__value">{{ $naira($donationTotal) }}</div><div class="stat__sub">{{ $donationCount }} gifts</div></a>
        <a class="stat" href="{{ route('admin.donations') }}#pledges"><div class="stat__label">Open pledges (not received)</div><div class="stat__value">{{ $naira($pledgeOpen) }}</div><div class="stat__sub">{{ $pledgeOpenCount }} pledges</div></a>
        <a class="stat" href="{{ route('admin.emails') }}"><div class="stat__label">Email failures</div><div class="stat__value" style="{{ $emailFailures ? 'color:var(--danger)' : '' }}">{{ $emailFailures }}</div></a>
    </div>

    <div class="grid grid--2">
        <section class="panel">
            <h2>Latest replies</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Guest</th><th>Status</th><th class="num">Seats</th><th>When</th></tr></thead>
                    <tbody>
                    @forelse ($recentRsvps as $r)
                        <tr><td><a href="{{ route('admin.guests.show', $r) }}">{{ $r->salutation() }}</a><div class="small muted">{{ $r->organisation }}</div></td>
                            <td><span class="badge badge--{{ $r->status }}">{{ $r->statusLabel() }}</span></td>
                            <td class="num">{{ $r->attendance === 'no' ? '—' : $r->party_size }}</td>
                            <td class="small muted nowrap">{{ $r->created_at->diffForHumans() }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="muted">No replies yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <div class="grid">
            <section class="panel">
                <h2>Latest verified gifts</h2>
                <table class="table">
                    <tbody>
                    @forelse ($recentDonations as $d)
                        <tr><td>{{ $d->displayName() }}<div class="small muted">{{ $d->methodLabel() }}</div></td><td class="num">{{ $naira($d->amount_kobo) }}</td></tr>
                    @empty
                        <tr><td class="muted">No verified donations yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </section>
            <section class="panel setup">
                <h2>Launch checklist</h2>
                <p class="small muted" style="margin-top:-6px">Client-supplied items and credentials. See <code>config/event.php</code> and <code>.env</code>.</p>
                <ul>
                    @foreach ($setup as $label => $done)
                        <li><span>{{ $label }}</span><span class="badge badge--{{ $done ? 'on' : 'pending' }}">{{ $done ? 'Ready' : 'Pending' }}</span></li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
@endsection
