<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Rsvp;

/** Goodwill messages to the celebrant, for the family. */
class MessageController extends Controller
{
    public function index()
    {
        $fromRsvps = Rsvp::query()->whereNotNull('message')->where('message', '!=', '')->get()
            ->map(fn ($r) => ['name' => $r->salutation(), 'organisation' => $r->organisation, 'message' => $r->message, 'source' => 'RSVP', 'at' => $r->updated_at]);
        $fromDonations = Donation::query()->successful()->whereNotNull('message')->where('message', '!=', '')->get()
            ->map(fn ($d) => ['name' => $d->displayName(), 'organisation' => null, 'message' => $d->message, 'source' => 'Donation', 'at' => $d->paid_at ?? $d->created_at]);

        return view('admin.messages', ['messages' => $fromRsvps->concat($fromDonations)->sortBy('at')->values()]);
    }
}
