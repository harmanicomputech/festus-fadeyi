<?php

namespace App\Http\Controllers;

use App\Models\Invitation;

class InvitationController extends Controller
{
    /** Personal invitation link: remember the code and open the RSVP form. */
    public function show(string $code)
    {
        $invitation = Invitation::findByCode($code);
        if (! $invitation) {
            return redirect()->to(route('home').'#rsvp')->with('rsvp_notice', 'We could not recognise that invitation link. Please check the link in your email, or contact the planning committee.');
        }

        if ($invitation->rsvp) {
            return redirect()->to(route('home').'#rsvp')->with('rsvp_notice', 'We already have your RSVP — thank you. Use the secure link in your confirmation email to view or change it.');
        }

        session(['invite_code' => $invitation->code]);

        return redirect()->to(route('home').'#rsvp');
    }
}
