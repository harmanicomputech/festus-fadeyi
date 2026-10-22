<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\EmailLog;
use App\Models\Invitation;
use App\Models\Pledge;
use App\Models\Rsvp;
use App\Services\Exporter;
use App\Services\Notifier;
use App\Services\RsvpService;
use App\Support\EventInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuestController extends Controller
{
    private const FILTERS = ['all', 'confirmed', 'waitlisted', 'maybe', 'declined', 'pending', 'checked_in'];

    public function index(Request $request)
    {
        $filter = in_array($request->query('status'), self::FILTERS, true) ? $request->query('status') : 'all';
        $q = trim((string) $request->query('q'));

        $counts = Rsvp::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $counts['all'] = $counts->sum();
        $counts['checked_in'] = Rsvp::query()->whereNotNull('checked_in_at')->count();
        $counts['pending'] = $this->pendingQuery()->count();

        if ($filter === 'pending') {
            $invitations = $this->pendingQuery()->when($q, fn ($b) => $this->searchInvitations($b, $q))->orderBy('name')->paginate(50)->withQueryString();

            return view('admin.guests.index', compact('filter', 'q', 'counts', 'invitations') + ['rsvps' => null]);
        }

        $rsvps = $this->rsvpQuery($filter, $q)->with('pledge')->latest()->paginate(50)->withQueryString();

        return view('admin.guests.index', compact('filter', 'q', 'counts', 'rsvps') + ['invitations' => null]);
    }

    public function show(Rsvp $rsvp)
    {
        $rsvp->load(['invitation', 'checkedInBy']);

        return view('admin.guests.show', [
            'rsvp' => $rsvp,
            'pledges' => Pledge::query()->where('rsvp_id', $rsvp->id)->with('donation')->get(),
            'emails' => EmailLog::query()->where('rsvp_id', $rsvp->id)->latest()->get(),
            'checkIns' => CheckIn::query()->with('user')->where('rsvp_id', $rsvp->id)->latest()->get(),
        ]);
    }

    public function status(Request $request, Rsvp $rsvp, RsvpService $rsvps)
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Rsvp::STATUS_LABELS))]]);
        $rsvps->adminSetStatus($rsvp, $data['status']);

        $note = $data['status'] === Rsvp::STATUS_CONFIRMED && $rsvps->seatsUsed() > EventInfo::capacity()
            ? ' Note: confirmed seats now exceed capacity.' : '';

        return back()->with('status', 'Status updated to '.Rsvp::STATUS_LABELS[$data['status']].'.'.$note);
    }

    public function resend(Rsvp $rsvp, Notifier $notifier)
    {
        $ok = $notifier->rsvpConfirmation($rsvp);

        return back()->with($ok ? 'status' : 'error', $ok ? 'Confirmation email re-sent to '.$rsvp->email.'.' : 'The email could not be sent. See the email log below.');
    }

    public function export(Request $request, string $format, Exporter $exporter)
    {
        $filter = in_array($request->query('status'), self::FILTERS, true) ? $request->query('status') : 'all';
        $headers = ['Status', 'Title', 'Full name', 'Email', 'Phone', 'Organisation / designation', 'Attending', 'Seats', 'Dietary / accessibility', 'Pledge (NGN)', 'Pledge status', 'Message to the celebrant', 'Invitation code', 'Checked in at', 'Replied at', 'Last updated'];

        $rows = function () use ($filter) {
            if ($filter !== 'pending') {
                foreach ($this->rsvpQuery($filter, '')->with(['pledge', 'invitation'])->orderBy('full_name')->cursor() as $r) {
                    yield [
                        $r->statusLabel(), $r->title, $r->full_name, $r->email, $r->phone, $r->organisation, ucfirst($r->attendance),
                        $r->attendance === 'no' ? 0 : $r->party_size, $r->dietary_needs,
                        $r->pledge ? $r->pledge->amount_kobo / 100 : null, $r->pledge?->status, $r->message,
                        $r->invitation?->code, $r->checked_in_at, $r->created_at, $r->updated_at,
                    ];
                }
            }
            if (in_array($filter, ['all', 'pending'], true)) {
                foreach ($this->pendingQuery()->orderBy('name')->cursor() as $i) {
                    yield ['Pending (no reply)', $i->title, $i->name, $i->email, $i->phone, null, null, null, null, null, null, null, $i->code, null, null, $i->updated_at];
                }
            }
        };

        return $exporter->download('guests-'.$filter, $format, ['Guests' => [$headers, $rows()]]);
    }

    private function rsvpQuery(string $filter, string $q): Builder
    {
        return Rsvp::query()
            ->when(in_array($filter, ['confirmed', 'waitlisted', 'maybe', 'declined'], true), fn ($b) => $b->where('status', $filter))
            ->when($filter === 'checked_in', fn ($b) => $b->whereNotNull('checked_in_at'))
            ->when($q !== '', function ($b) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], mb_strtolower($q)).'%';
                $digits = preg_replace('/\D/', '', $q);
                $b->where(fn ($w) => $w->whereRaw('lower(full_name) like ?', [$like])
                    ->orWhereRaw('lower(email) like ?', [$like])
                    ->orWhereRaw('lower(coalesce(organisation, \'\')) like ?', [$like])
                    ->when(strlen($digits) >= 4, fn ($w) => $w->orWhereRaw("replace(replace(phone, ' ', ''), '-', '') like ?", ['%'.$digits.'%'])));
            });
    }

    private function pendingQuery(): Builder
    {
        return Invitation::query()->whereDoesntHave('rsvp')->whereNotIn('email', Rsvp::query()->select('email'));
    }

    private function searchInvitations(Builder $b, string $q): Builder
    {
        $like = '%'.mb_strtolower($q).'%';

        return $b->where(fn ($w) => $w->whereRaw('lower(name) like ?', [$like])->orWhereRaw('lower(email) like ?', [$like]));
    }
}
