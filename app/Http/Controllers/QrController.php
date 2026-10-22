<?php

namespace App\Http\Controllers;

use App\Models\Rsvp;
use App\Services\QrCodes;

class QrController extends Controller
{
    /** Guest check-in QR image. The URL token is the only data encoded. */
    public function show(string $token, QrCodes $qr)
    {
        $rsvp = Rsvp::query()->where('qr_token', $token)->first();
        abort_unless($rsvp && $rsvp->isConfirmed(), 404);

        return response($qr->png($rsvp->qr_token), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=3600',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
