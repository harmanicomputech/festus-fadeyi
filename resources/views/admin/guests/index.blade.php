@extends('layouts.admin', ['title' => 'Guests'])
@section('content')
    <div class="page-head">
        <div><p class="eyebrow">Guest management</p><h1>Guests</h1></div>
        <div class="actions">
            <a class="btn btn--ghost" href="{{ route('admin.guests.export', ['csv', 'status' => $filter]) }}">CSV</a>
            <a class="btn btn--ghost" href="{{ route('admin.guests.export', ['xlsx', 'status' => $filter]) }}">Excel</a>
        </div>
    </div>

    <nav class="tabs" aria-label="Filter by status">
        @foreach (['all' => 'All replies', 'confirmed' => 'Accepted', 'maybe' => 'Maybe', 'declined' => 'Declined', 'waitlisted' => 'Waitlisted', 'pending' => 'No reply yet', 'checked_in' => 'Checked in'] as $key => $label)
            <a href="{{ route('admin.guests', ['status' => $key, 'q' => $q ?: null]) }}" @class(['is-active' => $filter === $key])>{{ $label }}<span>{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </nav>

    <form class="toolbar" method="GET">
        <input type="hidden" name="status" value="{{ $filter }}">
        <div class="field grow"><label for="q">Search name, email, phone or organisation</label><input class="input" id="q" name="q" value="{{ $q }}" type="search"></div>
        <button class="btn" type="submit">Search</button>
        @if ($q)<a class="btn btn--ghost" href="{{ route('admin.guests', ['status' => $filter]) }}">Clear</a>@endif
    </form>

    @if ($invitations)
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Invitee</th><th>Email</th><th>Phone</th><th>Invitation sent</th><th>Code</th></tr></thead>
                <tbody>
                @forelse ($invitations as $i)
                    <tr><td>{{ $i->salutation() }}</td><td>{{ $i->email }}</td><td>{{ $i->phone }}</td>
                        <td>{{ $i->invitation_sent_at ? $i->invitation_sent_at->format('j M, g:i A') : 'Not yet' }}</td><td><code>{{ $i->code }}</code></td></tr>
                @empty
                    <tr><td colspan="5" class="muted">Everyone invited has replied, or no invitations have been added yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $invitations->links('admin.pagination') }}</div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Guest</th><th>Contact</th><th>Status</th><th class="num">Seats</th><th>Dietary / access</th><th class="num">Pledge</th><th>Checked in</th><th>Replied</th></tr></thead>
                <tbody>
                @forelse ($rsvps as $r)
                    <tr>
                        <td><a href="{{ route('admin.guests.show', $r) }}"><strong>{{ $r->salutation() }}</strong></a><div class="small muted">{{ $r->organisation }}</div></td>
                        <td class="small">{{ $r->email }}<br>{{ $r->phone }}</td>
                        <td><span class="badge badge--{{ $r->status }}">{{ $r->statusLabel() }}</span></td>
                        <td class="num">{{ $r->attendance === 'no' ? '—' : $r->party_size }}</td>
                        <td class="small clip" title="{{ $r->dietary_needs }}">{{ $r->dietary_needs }}</td>
                        <td class="num">{{ $r->pledge ? \App\Support\EventInfo::naira($r->pledge->amount_kobo) : '' }}</td>
                        <td class="small nowrap">{{ $r->checked_in_at?->format('g:i A') }}</td>
                        <td class="small muted nowrap">{{ $r->created_at->format('j M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No guests match.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $rsvps->links('admin.pagination') }}</div>
    @endif
@endsection
