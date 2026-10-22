<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Rsvp;
use App\Models\Setting;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InvitationController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $invitations = Invitation::query()->with('rsvp')
            ->when($q, fn ($b) => $b->where(fn ($w) => $w->whereRaw('lower(name) like ?', ['%'.mb_strtolower($q).'%'])->orWhereRaw('lower(email) like ?', ['%'.mb_strtolower($q).'%'])))
            ->orderBy('name')->paginate(100)->withQueryString();

        $rsvpEmails = Rsvp::query()->whereIn('email', $invitations->pluck('email'))->pluck('status', 'email');

        return view('admin.invitations', [
            'invitations' => $invitations,
            'rsvpEmails' => $rsvpEmails,
            'q' => $q,
            'inviteOnly' => Setting::inviteOnly(),
            'unsent' => Invitation::query()->whereNull('invitation_sent_at')->where('email_opt_out', false)->whereDoesntHave('rsvp')->count(),
            'titles' => config('event.rsvp.titles'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $this->create($data);

        return back()->with('status', 'Invitation added for '.$data['name'].'.');
    }

    /** Paste one guest per line: Title, Full name, Email, Phone (title and phone optional). */
    public function import(Request $request)
    {
        $request->validate(['rows' => ['required', 'string', 'max:200000']]);
        $added = 0;
        $skipped = [];

        foreach (preg_split('/\r\n|\r|\n/', trim($request->input('rows'))) as $n => $line) {
            if (trim($line) === '') {
                continue;
            }
            $cols = array_map('trim', str_getcsv($line, str_contains($line, "\t") ? "\t" : ','));
            if (count($cols) < 2) {
                $skipped[] = 'Line '.($n + 1).': expected at least a name and an email';

                continue;
            }
            // Accept "Name, Email[, Phone]" or "Title, Name, Email[, Phone]"
            $hasTitle = in_array($cols[0], config('event.rsvp.titles'), true) || ! filter_var($cols[1], FILTER_VALIDATE_EMAIL);
            $row = $hasTitle
                ? ['title' => $cols[0] ?: null, 'name' => $cols[1] ?? '', 'email' => $cols[2] ?? '', 'phone' => $cols[3] ?? null]
                : ['title' => null, 'name' => $cols[0], 'email' => $cols[1], 'phone' => $cols[2] ?? null];
            if ($row['title'] && ! in_array($row['title'], config('event.rsvp.titles'), true)) {
                $row['title'] = 'Other';
            }

            $v = Validator::make($row, $this->rules());
            if ($v->fails()) {
                $skipped[] = 'Line '.($n + 1).' ('.($row['email'] ?: $row['name']).'): '.$v->errors()->first();

                continue;
            }
            $this->create($v->validated());
            $added++;
        }

        return back()->with('status', "$added invitation(s) added.")->with('import_skipped', $skipped);
    }

    public function send(Request $request, Notifier $notifier)
    {
        $data = $request->validate([
            'ids' => ['array'],
            'ids.*' => ['integer'],
            'mode' => ['required', Rule::in(['unsent', 'selected'])],
        ]);

        $query = Invitation::query()->whereDoesntHave('rsvp')->where('email_opt_out', false);
        $query = $data['mode'] === 'unsent'
            ? $query->whereNull('invitation_sent_at')
            : $query->whereIn('id', $data['ids'] ?? []);

        $sent = $failed = 0;
        foreach ($query->get() as $invitation) {
            $notifier->invitation($invitation) ? $sent++ : $failed++;
        }

        return back()->with($failed ? 'error' : 'status', "$sent invitation email(s) processed".($failed ? ", $failed failed — see the email log." : '.'));
    }

    public function settings(Request $request)
    {
        Setting::put('invite_only', $request->boolean('invite_only') ? '1' : null);

        return back()->with('status', $request->boolean('invite_only') ? 'Invite-only mode is on: RSVPs require a personal invitation link or code.' : 'Invite-only mode is off: anyone with the website link may RSVP.');
    }

    public function destroy(Invitation $invitation)
    {
        if ($invitation->rsvp) {
            return back()->with('error', 'This guest has already replied; the invitation cannot be removed.');
        }
        $invitation->delete();

        return back()->with('status', 'Invitation removed.');
    }

    private function rules(): array
    {
        return [
            'title' => ['nullable', Rule::in(config('event.rsvp.titles'))],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', function ($attr, $value, $fail) {
                if (Invitation::query()->where('email', Invitation::normaliseEmail($value))->exists()) {
                    $fail('An invitation already exists for this email.');
                }
            }],
            'phone' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function create(array $data): Invitation
    {
        return Invitation::query()->create([
            'code' => Invitation::newCode(),
            'title' => $data['title'] ?? null,
            'name' => trim($data['name']),
            'email' => Invitation::normaliseEmail($data['email']),
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
