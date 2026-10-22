@extends('layouts.admin', ['title' => 'Invitations'])
@section('content')
    <div class="page-head">
        <div><p class="eyebrow">Guest list</p><h1>Invitations</h1><p>Each invitee receives a personal RSVP link. The RSVP reminder goes only to invitees who have not replied.</p></div>
    </div>

    @if (session('import_skipped'))
        <div class="flash flash--error"><strong>Some lines were skipped:</strong><ul>@foreach (session('import_skipped') as $s)<li>{{ $s }}</li>@endforeach</ul></div>
    @endif

    <div class="grid grid--2" style="margin-bottom:20px">
        <section class="panel panel--navy">
            <h2>Invite-only mode</h2>
            <p>When on, only guests with a personal invitation link or code can RSVP, so the invitation cannot be shared beyond the list. Currently <strong>{{ $inviteOnly ? 'ON' : 'OFF' }}</strong>.</p>
            <form method="POST" action="{{ route('admin.invitations.settings') }}">
                @csrf
                <input type="hidden" name="invite_only" value="{{ $inviteOnly ? 0 : 1 }}">
                <button class="btn btn--gold" type="submit">{{ $inviteOnly ? 'Turn off invite-only mode' : 'Turn on invite-only mode' }}</button>
            </form>
        </section>
        <section class="panel">
            <h2>Send invitations</h2>
            <p><strong>{{ $unsent }}</strong> invitee(s) have not yet been sent their invitation email.</p>
            <form method="POST" action="{{ route('admin.invitations.send') }}" data-confirm="Send the invitation email to {{ $unsent }} invitee(s)?">
                @csrf<input type="hidden" name="mode" value="unsent">
                <button class="btn btn--gold" type="submit" @disabled(! $unsent)>Send to all unsent</button>
            </form>
            <p class="small muted" style="margin-bottom:0">Preview the design under <a href="{{ route('admin.emails') }}">Emails</a> before sending.</p>
        </section>
    </div>

    <div class="grid grid--2" style="margin-bottom:24px">
        <section class="panel">
            <h2>Add an invitee</h2>
            <form method="POST" action="{{ route('admin.invitations.store') }}" class="form-grid">
                @csrf
                <div class="field"><label for="i-title">Title</label><select class="select" id="i-title" name="title"><option value="">—</option>@foreach ($titles as $t)<option @selected(old('title') === $t)>{{ $t }}</option>@endforeach</select></div>
                <div class="field"><label for="i-name">Full name</label><input class="input" id="i-name" name="name" required value="{{ old('name') }}"></div>
                <div class="field"><label for="i-email">Email</label><input class="input" id="i-email" name="email" type="email" required value="{{ old('email') }}"></div>
                <div class="field"><label for="i-phone">Phone</label><input class="input" id="i-phone" name="phone" value="{{ old('phone') }}"></div>
                <div class="field full"><button class="btn" type="submit">Add invitee</button></div>
            </form>
        </section>
        <section class="panel">
            <h2>Paste a list</h2>
            <form method="POST" action="{{ route('admin.invitations.import') }}">
                @csrf
                <div class="field"><label for="rows">One guest per line: Title, Full name, Email, Phone</label>
                    <textarea class="textarea" id="rows" name="rows" placeholder="Chief, Adewale Johnson, adewale@example.com, 08030000000&#10;Dr, Ngozi Okafor, ngozi@example.com">{{ old('rows') }}</textarea></div>
                <p class="small muted">Copy rows straight from Excel (tab-separated) or CSV. Title and phone are optional. Duplicates are skipped.</p>
                <button class="btn" type="submit">Import</button>
            </form>
        </section>
    </div>

    <form class="toolbar" method="GET">
        <div class="field grow"><label for="q">Search invitees</label><input class="input" id="q" name="q" type="search" value="{{ $q }}"></div>
        <button class="btn" type="submit">Search</button>
    </form>

    <form method="POST" action="{{ route('admin.invitations.send') }}" id="send-selected" data-confirm="Send (or re-send) the invitation email to the selected invitees?">
        @csrf<input type="hidden" name="mode" value="selected">
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th><span class="sr-only">Select</span></th><th>Invitee</th><th>Email / phone</th><th>Personal link</th><th>Invitation sent</th><th>Reply</th><th></th></tr></thead>
            <tbody>
            @forelse ($invitations as $i)
                @php($status = $i->rsvp?->status ?? ($rsvpEmails[$i->email] ?? null))
                <tr>
                    <td>@unless ($status)<input type="checkbox" name="ids[]" value="{{ $i->id }}" form="send-selected" aria-label="Select {{ $i->name }}">@endunless</td>
                    <td><strong>{{ $i->salutation() }}</strong></td>
                    <td class="small">{{ $i->email }}<br>{{ $i->phone }}</td>
                    <td class="small"><code>{{ $i->code }}</code><br><span class="muted">{{ $i->link() }}</span></td>
                    <td class="small">{{ $i->invitation_sent_at?->format('j M, g:i A') ?? '—' }}@if ($i->email_opt_out)<br><span class="badge badge--off">opted out</span>@endif</td>
                    <td>@if ($status)<span class="badge badge--{{ $status }}">{{ \App\Models\Rsvp::STATUS_LABELS[$status] }}</span>@else<span class="badge badge--pending">No reply</span>@endif</td>
                    <td>@unless ($status)<form method="POST" action="{{ route('admin.invitations.destroy', $i) }}" data-confirm="Remove this invitation?">@csrf @method('DELETE')<button class="btn btn--danger btn--sm" type="submit">Remove</button></form>@endunless</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No invitations yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="actions" style="margin-top:12px"><button class="btn btn--ghost" type="submit" form="send-selected">Send invitation to selected</button></div>
    <div class="pagination">{{ $invitations->links('admin.pagination') }}</div>
@endsection
