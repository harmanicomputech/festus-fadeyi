@extends('layouts.admin', ['title' => 'Donations'])
@php($naira = fn ($k) => \App\Support\EventInfo::naira((int) $k))
@section('content')
    <div class="page-head">
        <div><p class="eyebrow">Giving · {{ \App\Support\EventInfo::charityLabel() }}</p><h1>Donations</h1>
            <p>Verified total <strong>{{ $naira($verifiedTotal) }}</strong> from {{ $verifiedCount }} gifts. Pledges are listed separately and are never counted as received.</p></div>
        <div class="actions">
            <a class="btn btn--ghost" href="{{ route('admin.donations.export', ['csv'] + array_filter($filters)) }}">CSV</a>
            <a class="btn btn--ghost" href="{{ route('admin.donations.export', ['xlsx'] + array_filter($filters)) }}">Excel</a>
            <a class="btn btn--ghost" href="{{ route('admin.donations.venue-qr') }}">Venue donation QR (PNG)</a>
        </div>
    </div>

    <form class="toolbar" method="GET">
        <div class="field"><label for="f-status">Status</label><select class="select" id="f-status" name="status">
            @foreach (['success' => 'Verified', 'pending' => 'Pending', 'review' => 'Needs review', 'failed' => 'Failed', 'abandoned' => 'Abandoned', 'all' => 'All'] as $k => $l)<option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $l }}</option>@endforeach
        </select></div>
        <div class="field"><label for="f-method">Method</label><select class="select" id="f-method" name="method"><option value="">All methods</option>
            @foreach ($methods as $k => $l)<option value="{{ $k }}" @selected($filters['method'] === $k)>{{ $l }}</option>@endforeach
        </select></div>
        <div class="field"><label for="f-from">From</label><input class="input" id="f-from" type="date" name="from" value="{{ $filters['from'] }}"></div>
        <div class="field"><label for="f-to">To</label><input class="input" id="f-to" type="date" name="to" value="{{ $filters['to'] }}"></div>
        <div class="field grow"><label for="f-q">Donor, email or reference</label><input class="input" id="f-q" type="search" name="q" value="{{ $filters['q'] }}"></div>
        <button class="btn" type="submit">Filter</button>
    </form>

    <div class="grid grid--2" style="margin-bottom:16px">
        <section class="panel"><h3>By method (filtered, verified)</h3>
            <table class="table">@forelse ($byMethod as $m)<tr><td>{{ $methods[$m->method] ?? $m->method }}</td><td class="num">{{ $m->n }}</td><td class="num">{{ $naira($m->total) }}</td></tr>@empty<tr><td class="muted">—</td></tr>@endforelse</table>
        </section>
        <section class="panel"><h3>By date (filtered, verified)</h3>
            <table class="table">@forelse ($byDay as $day => $v)<tr><td>{{ $day }}</td><td class="num">{{ $v['n'] }}</td><td class="num">{{ $naira($v['total']) }}</td></tr>@empty<tr><td class="muted">—</td></tr>@endforelse</table>
        </section>
    </div>

    <div class="table-wrap" style="margin-bottom:8px">
        <table class="table">
            <thead><tr><th>Reference</th><th>Donor</th><th>Method</th><th>Status</th><th class="num">Amount</th><th>Paid / verified</th><th>Public</th><th>Receipt</th><th></th></tr></thead>
            <tbody>
            @forelse ($donations as $d)
                <tr>
                    <td class="small"><code>{{ $d->reference }}</code></td>
                    <td>{{ $d->displayName() }}@if ($d->is_anonymous && $d->donor_name) <span class="small muted">({{ $d->donor_name }})</span>@endif<div class="small muted">{{ $d->email }}</div>@if ($d->message)<div class="small clip" title="{{ $d->message }}">“{{ $d->message }}”</div>@endif</td>
                    <td class="small">{{ $d->methodLabel() }}@if ($d->channel)<br><span class="muted">{{ $d->channel }}</span>@endif @if ($d->recorder)<br><span class="muted">by {{ $d->recorder->name }}</span>@endif</td>
                    <td><span class="badge badge--{{ $d->status }}">{{ $d->status }}</span>@if ($d->admin_note)<div class="small muted">{{ $d->admin_note }}</div>@endif</td>
                    <td class="num"><strong>{{ $naira($d->amount_kobo) }}</strong></td>
                    <td class="small nowrap">{{ $d->paid_at?->format('j M Y, g:i A') ?? '—' }}</td>
                    <td class="small">{{ $d->acknowledge_publicly ? 'Name' : 'No' }}{{ $d->show_amount_publicly ? ' + amount' : '' }}</td>
                    <td class="small">{{ $d->receipt_sent_at ? 'Sent' : '—' }}</td>
                    <td class="actions">
                        @if ($d->method === 'paystack' && $d->status !== 'success')
                            <form method="POST" action="{{ route('admin.donations.verify', $d) }}">@csrf<button class="btn btn--ghost btn--sm" type="submit">Re-check with Paystack</button></form>
                        @endif
                        @if ($d->status === 'success' && $d->email)
                            <form method="POST" action="{{ route('admin.donations.receipt', $d) }}" data-confirm="Send the receipt to {{ $d->email }}?">@csrf<button class="btn btn--ghost btn--sm" type="submit">{{ $d->receipt_sent_at ? 'Re-send' : 'Send' }} receipt</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="muted">No donations match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $donations->links('admin.pagination') }}</div>

    <div class="grid grid--2" style="margin-top:28px">
        <section class="panel">
            <h2>Record an offline gift</h2>
            <p class="small muted">For bank transfers or cash/cheques confirmed against the beneficiary account. Only record funds you have actually seen received.</p>
            <form method="POST" action="{{ route('admin.donations.store') }}" class="form-grid">
                @csrf
                <div class="field"><label for="m-amount">Amount (₦)</label><input class="input" id="m-amount" name="amount" inputmode="decimal" required value="{{ old('amount') }}"></div>
                <div class="field"><label for="m-method">Method</label><select class="select" id="m-method" name="method">@foreach (['bank_transfer', 'cash', 'other'] as $k)<option value="{{ $k }}" @selected(old('method') === $k)>{{ $methods[$k] }}</option>@endforeach</select></div>
                <div class="field"><label for="m-paid">Date received</label><input class="input" id="m-paid" name="paid_at" type="datetime-local" required value="{{ old('paid_at') }}"></div>
                <div class="field"><label for="m-ref">Bank reference <span class="muted">(optional)</span></label><input class="input" id="m-ref" name="reference" value="{{ old('reference') }}"></div>
                <div class="field"><label for="m-name">Donor name</label><input class="input" id="m-name" name="donor_name" value="{{ old('donor_name') }}"></div>
                <div class="field"><label for="m-email">Donor email <span class="muted">(for receipt)</span></label><input class="input" id="m-email" name="email" type="email" value="{{ old('email') }}"></div>
                <div class="field"><label for="m-pledge">Fulfils pledge</label><select class="select" id="m-pledge" name="pledge_id"><option value="">—</option>@foreach ($openPledges as $p)<option value="{{ $p->id }}">{{ $p->name }} · {{ $naira($p->amount_kobo) }}</option>@endforeach</select></div>
                <div class="field"><label for="m-note">Internal note</label><input class="input" id="m-note" name="admin_note" value="{{ old('admin_note') }}"></div>
                <div class="field full"><label for="m-msg">Message to the celebrant</label><input class="input" id="m-msg" name="message" value="{{ old('message') }}"></div>
                <label class="check full"><input type="checkbox" name="anonymous" value="1"> Anonymous gift</label>
                <label class="check full"><input type="checkbox" name="acknowledge_publicly" value="1"> Donor consented to public acknowledgement of their name</label>
                <label class="check full"><input type="checkbox" name="show_amount_publicly" value="1"> Donor consented to showing the amount</label>
                <label class="check full"><input type="checkbox" name="send_receipt" value="1" checked> Email a receipt now</label>
                <label class="check full"><input type="checkbox" name="confirm_verified" value="1" required> I confirm these funds have been received in the beneficiary account.</label>
                <div class="field full"><button class="btn btn--gold" type="submit">Record verified gift</button></div>
            </form>
        </section>
        <section class="panel" id="pledges">
            <div class="actions" style="justify-content:space-between;margin-bottom:12px"><h2>Pledges</h2>
                <span class="actions"><a class="btn btn--ghost btn--sm" href="{{ route('admin.pledges.export', 'csv') }}">CSV</a><a class="btn btn--ghost btn--sm" href="{{ route('admin.pledges.export', 'xlsx') }}">Excel</a></span></div>
            <p class="small muted">Promises made with RSVPs. Not money received until fulfilled by a verified donation.</p>
            <table class="table">
                <thead><tr><th>Name</th><th class="num">Amount</th><th>Status</th></tr></thead>
                @forelse ($pledges as $p)
                    <tr><td>{{ $p->name }}<div class="small muted">{{ $p->email }}</div></td><td class="num">{{ $naira($p->amount_kobo) }}</td><td><span class="badge badge--{{ $p->status }}">{{ $p->status }}</span></td></tr>
                @empty
                    <tr><td colspan="3" class="muted">No pledges yet.</td></tr>
                @endforelse
            </table>
        </section>
    </div>
@endsection
