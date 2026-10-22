@extends('layouts.admin', ['title' => 'Emails'])
@section('content')
    <div class="page-head">
        <div><p class="eyebrow">Branded email</p><h1>Emails</h1>
            <p>From <strong>{{ $from['name'] }} &lt;{{ $from['address'] }}&gt;</strong>, replies to {{ $replyTo }}.
            Delivery: @if (in_array($mailer, ['log', 'array']))<span class="badge badge--pending">not configured — emails are logged, not delivered</span>@else<span class="badge badge--on">{{ $mailer }}</span>@endif</p></div>
    </div>

    <section class="panel" style="margin-bottom:20px">
        <h2>Templates</h2>
        <p class="small muted">Draft copy pending client approval. Approved copy can be set in <code>config/event.php</code> (<code>email.copy</code>).</p>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Template</th><th>Trigger</th><th class="num">Sent</th><th class="num">Logged only</th><th class="num">Failed</th><th></th></tr></thead>
            @php($triggers = ['invitation' => 'Manual, from Invitations', 'rsvp_confirmation' => 'Instantly on RSVP / update / waitlist promotion', 'donation_receipt' => 'Instantly when a payment is verified', 'rsvp_reminder' => 'Scheduled, ~2 weeks before', 'event_reminder' => 'Scheduled, 3 days before', 'day_of' => 'Scheduled, morning of the event', 'thank_you' => 'Scheduled, day after'])
            @foreach ($templates as $key => $label)
                <tr><td><strong>{{ $label }}</strong></td><td class="small">{{ $triggers[$key] }}</td>
                    <td class="num">{{ $stats[$key]['sent'] ?? 0 }}</td><td class="num">{{ $stats[$key]['logged'] ?? 0 }}</td><td class="num">{{ $stats[$key]['failed'] ?? 0 }}</td>
                    <td><a class="btn btn--ghost btn--sm" href="{{ route('admin.emails.preview', $key) }}" target="_blank">Preview</a></td></tr>
            @endforeach
        </table></div>
    </section>

    <section class="panel" style="margin-bottom:20px">
        <h2>Scheduled campaigns</h2>
        <p class="small muted">The scheduler sends each campaign automatically within 24 hours of its time (Lagos). Each guest receives each campaign at most once; guests who opted out are excluded.</p>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Campaign</th><th>Scheduled</th><th class="num">Eligible now</th><th class="num">Already received</th><th>Send now</th></tr></thead>
            @foreach ($campaigns as $c)
                <tr>
                    <td><strong>{{ $c['label'] }}</strong></td>
                    <td class="small nowrap">{{ $c['at']->format('D j M Y, g:i A') }} @if ($c['due'])<span class="badge badge--open">due</span>@endif</td>
                    <td class="num">{{ $c['eligible'] }}</td>
                    <td class="num">{{ $c['done'] }}</td>
                    <td>
                        <form class="actions" method="POST" action="{{ route('admin.emails.campaign', $c['key']) }}" data-confirm="Send '{{ $c['label'] }}' now to eligible recipients who have not yet received it?">
                            @csrf
                            <label class="check small"><input type="checkbox" name="confirm" value="1"> confirm</label>
                            <button class="btn btn--sm" type="submit">Send now</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </table></div>
    </section>

    <section class="panel">
        <h2>Recent email log</h2>
        <div class="table-wrap"><table class="table small">
            <thead><tr><th>When</th><th>Template</th><th>Recipient</th><th>Status</th><th>Error</th></tr></thead>
            @forelse ($log as $e)
                <tr><td class="nowrap">{{ $e->created_at->format('j M, g:i A') }}</td><td>{{ $templates[$e->template] ?? $e->template }}</td><td>{{ $e->recipient }}</td>
                    <td><span class="badge badge--{{ $e->status }}">{{ $e->status }}</span></td><td class="clip" title="{{ $e->error }}">{{ $e->error }}</td></tr>
            @empty
                <tr><td colspan="5" class="muted">No emails yet.</td></tr>
            @endforelse
        </table></div>
    </section>
@endsection
