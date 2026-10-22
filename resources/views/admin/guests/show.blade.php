@extends('layouts.admin', ['title' => $rsvp->full_name])
@section('content')
    <div class="page-head">
        <div><p class="eyebrow"><a href="{{ route('admin.guests') }}">Guests</a></p><h1>{{ $rsvp->salutation() }}</h1><p><span class="badge badge--{{ $rsvp->status }}">{{ $rsvp->statusLabel() }}</span></p></div>
        <div class="actions">
            <form method="POST" action="{{ route('admin.guests.resend', $rsvp) }}">@csrf<button class="btn btn--ghost" type="submit">Re-send confirmation email</button></form>
        </div>
    </div>
    <div class="grid grid--2">
        <section class="panel">
            <h2>RSVP details</h2>
            <dl class="kv">
                <dt>Title</dt><dd>{{ $rsvp->title }}</dd>
                <dt>Full name</dt><dd>{{ $rsvp->full_name }}</dd>
                <dt>Email</dt><dd><a href="mailto:{{ $rsvp->email }}">{{ $rsvp->email }}</a></dd>
                <dt>Phone (WhatsApp)</dt><dd>{{ $rsvp->phone }} · <a href="https://wa.me/{{ preg_replace('/^0/', '234', preg_replace('/\D/', '', $rsvp->phone)) }}" target="_blank" rel="noopener">WhatsApp</a></dd>
                <dt>Organisation</dt><dd>{{ $rsvp->organisation ?: '—' }}</dd>
                <dt>Attending</dt><dd>{{ ucfirst($rsvp->attendance) }}</dd>
                <dt>Seats</dt><dd>{{ $rsvp->attendance === 'no' ? '—' : $rsvp->party_size }}</dd>
                <dt>Dietary / access</dt><dd>{{ $rsvp->dietary_needs ?: '—' }}</dd>
                <dt>Message</dt><dd>{{ $rsvp->message ?: '—' }}</dd>
                <dt>Invitation</dt><dd>{{ $rsvp->invitation ? $rsvp->invitation->code.' ('.$rsvp->invitation->name.')' : 'Open RSVP (no invitation code)' }}</dd>
                <dt>Replied</dt><dd>{{ $rsvp->created_at->format('j M Y, g:i A') }}</dd>
                <dt>Updated</dt><dd>{{ $rsvp->updated_at->format('j M Y, g:i A') }}</dd>
                @if ($rsvp->waitlisted_at)<dt>Waitlisted</dt><dd>{{ $rsvp->waitlisted_at->format('j M Y, g:i A') }}</dd>@endif
                <dt>Checked in</dt><dd>{{ $rsvp->checked_in_at ? $rsvp->checked_in_at->format('j M Y, g:i A').' by '.($rsvp->checkedInBy?->name ?? '—') : 'Not yet' }}</dd>
                <dt>Reminder emails</dt><dd>{{ $rsvp->email_opt_out ? 'Opted out' : 'Subscribed' }}</dd>
            </dl>
        </section>
        <div class="grid">
            <section class="panel">
                <h2>Change status</h2>
                <p class="small muted">Use with care: confirming a waitlisted guest may exceed the {{ config('event.rsvp.capacity') }}-seat capacity. The guest is emailed about the change.</p>
                <form class="actions" method="POST" action="{{ route('admin.guests.status', $rsvp) }}" data-confirm="Change this guest's status and email them?">
                    @csrf
                    <select class="select" name="status" style="max-width:220px">
                        @foreach (\App\Models\Rsvp::STATUS_LABELS as $k => $label)
                            <option value="{{ $k }}" @selected($rsvp->status === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn" type="submit">Update</button>
                </form>
            </section>
            <section class="panel">
                <h2>Pledges</h2>
                @forelse ($pledges as $p)
                    <p>{{ \App\Support\EventInfo::naira($p->amount_kobo) }} <span class="badge badge--{{ $p->status }}">{{ $p->status }}</span> @if ($p->donation) — paid, ref {{ $p->donation->reference }}@endif</p>
                @empty
                    <p class="muted">No pledge.</p>
                @endforelse
            </section>
            <section class="panel">
                <h2>Emails</h2>
                <table class="table small">
                    @forelse ($emails as $e)
                        <tr><td>{{ \App\Services\Notifier::TEMPLATES[$e->template] ?? $e->template }}</td><td><span class="badge badge--{{ $e->status }}">{{ $e->status }}</span></td><td class="nowrap muted">{{ $e->created_at->format('j M, g:i A') }}</td></tr>
                    @empty
                        <tr><td class="muted">None yet.</td></tr>
                    @endforelse
                </table>
            </section>
        </div>
    </div>
@endsection
