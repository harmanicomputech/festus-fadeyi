<?php

namespace App\Http\Controllers;

use App\Http\Requests\RsvpRequest;
use App\Models\Invitation;
use App\Models\Rsvp;
use App\Models\Setting;
use App\Services\RsvpService;
use App\Support\EventInfo;
use Illuminate\Http\Request;

class RsvpController extends Controller
{
    public function __construct(private RsvpService $rsvps) {}

    public function store(RsvpRequest $request)
    {
        $back = redirect()->to(route('home').'#rsvp');

        if (! EventInfo::rsvpOpen()) {
            return $back->with('rsvp_notice', 'RSVPs closed on '.config('event.rsvp.deadline_label').'. Kindly contact the planning committee.');
        }

        $data = $request->validated();
        $invitation = Invitation::findByCode($data['invite_code'] ?? $request->session()->get('invite_code'));

        if (Setting::inviteOnly() && ! $invitation) {
            return $back->withInput()->withErrors(['invite_code' => 'This celebration is by invitation. Please enter the invitation code from your email.']);
        }

        [$rsvp, $created, $token] = $this->rsvps->submit($data, $invitation);

        if (! $created) {
            return $back->with('rsvp_notice', 'We already have an RSVP for this email address. We have emailed a secure link so you can view or update it.');
        }

        $request->session()->forget('invite_code');

        return redirect()->route('rsvp.manage', $token)->with('just_submitted', true);
    }

    public function manage(string $token)
    {
        $rsvp = $this->find($token);

        return view('rsvp.manage', [
            'rsvp' => $rsvp,
            'token' => $token,
            'pledge' => $rsvp->pledge()->first(),
            'rsvpOpen' => EventInfo::rsvpOpen(),
        ]);
    }

    public function update(RsvpRequest $request, string $token)
    {
        $rsvp = $this->find($token);
        $this->rsvps->update($rsvp, $request->validated());

        return redirect()->route('rsvp.manage', $token)->with('updated', true);
    }

    public function cancel(Request $request, string $token)
    {
        $rsvp = $this->find($token);
        $this->rsvps->cancel($rsvp);

        return redirect()->route('rsvp.manage', $token)->with('updated', true);
    }

    private function find(string $token): Rsvp
    {
        return Rsvp::findByManageToken($token) ?? abort(404);
    }
}
