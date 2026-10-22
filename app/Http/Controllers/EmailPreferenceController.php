<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Rsvp;

class EmailPreferenceController extends Controller
{
    /** Signed link in reminder emails. Transactional emails (receipts, confirmations) still send. */
    public function unsubscribe(string $type, int $id)
    {
        $model = match ($type) {
            'invitation' => Invitation::query()->find($id),
            'rsvp' => Rsvp::query()->find($id),
            default => null,
        };
        abort_unless($model, 404);

        $model->forceFill(['email_opt_out' => true])->save();
        if ($model instanceof Rsvp && $model->invitation) {
            $model->invitation->forceFill(['email_opt_out' => true])->save();
        }

        return view('landing.message', [
            'heading' => 'Your preference has been saved',
            'body' => 'You will no longer receive reminder emails about this event. Confirmations and receipts for anything you request will still be sent.',
        ]);
    }
}
