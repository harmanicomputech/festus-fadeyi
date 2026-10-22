<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Invitation;
use App\Models\Setting;
use App\Services\RsvpService;
use App\Support\EventInfo;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function show(Request $request, RsvpService $rsvps)
    {
        $invitation = Invitation::findByCode($request->session()->get('invite_code'));

        // Only donors who explicitly consented are acknowledged; amounts only with separate consent.
        $acknowledgements = Donation::query()->successful()
            ->where('acknowledge_publicly', true)->where('is_anonymous', false)->whereNotNull('donor_name')
            ->orderBy('paid_at')->get(['donor_name', 'amount_kobo', 'show_amount_publicly']);

        return view('landing.index', [
            'invitation' => $invitation,
            'inviteOnly' => Setting::inviteOnly(),
            'rsvpOpen' => EventInfo::rsvpOpen(),
            'seatsFull' => $rsvps->seatsRemaining() <= 0,
            'acknowledgements' => $acknowledgements,
            'donationsReady' => EventInfo::donationsReady(),
        ]);
    }

    public function privacy()
    {
        return view('landing.privacy');
    }
}
