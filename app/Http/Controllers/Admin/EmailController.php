<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BrandedMail;
use App\Models\Donation;
use App\Models\EmailLog;
use App\Models\Invitation;
use App\Models\Rsvp;
use App\Services\Campaigns;
use App\Services\Notifier;
use App\Services\QrCodes;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function index(Campaigns $campaigns)
    {
        $stats = EmailLog::query()->selectRaw('template, status, count(*) as n')->groupBy('template', 'status')->get()
            ->groupBy('template')->map(fn ($g) => $g->pluck('n', 'status'));

        $campaignRows = collect(Campaigns::CAMPAIGNS)->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
            'at' => $campaigns->scheduledAt($key),
            'due' => $campaigns->isDue($key),
            'eligible' => $campaigns->recipients($key)->count(),
            'done' => EmailLog::query()->where('template', $key)->whereIn('status', ['sent', 'logged'])->count(),
        ]);

        return view('admin.emails', [
            'templates' => Notifier::TEMPLATES,
            'stats' => $stats,
            'campaigns' => $campaignRows,
            'log' => EmailLog::query()->latest()->limit(60)->get(),
            'mailer' => config('mail.default'),
            'from' => config('mail.from'),
            'replyTo' => config('event.email.reply_to'),
        ]);
    }

    /** Render a template with sample data so the client can approve the design and copy. */
    public function preview(string $template, QrCodes $qr)
    {
        abort_unless(array_key_exists($template, Notifier::TEMPLATES), 404);

        $rsvp = new Rsvp(['title' => 'Chief', 'full_name' => 'Sample Guest', 'email' => 'guest@example.com', 'party_size' => 2, 'attendance' => 'yes', 'status' => Rsvp::STATUS_CONFIRMED]);
        $rsvp->qr_token = str_repeat('S', 40);
        $donation = new Donation(['reference' => 'FF85-SAMPLE-0001', 'amount_kobo' => 10000000, 'method' => 'paystack', 'donor_name' => 'Sample Donor', 'status' => 'success']);
        $donation->paid_at = now();
        $donation->created_at = now();

        $data = Notifier::commonData() + [
            'name' => 'Chief Sample Guest',
            'rsvp' => $rsvp,
            'donation' => $donation,
            'invitation' => new Invitation(['name' => 'Sample Guest']),
            'rsvpUrl' => url('/i/SAMPLECODE'),
            'manageUrl' => url('/rsvp/sample-preview-link'),
            'unsubscribeUrl' => url('/email/unsubscribe/sample'),
            'duplicate' => false, 'updated' => false, 'promoted' => false, 'pledge' => null,
            'qrSrc' => 'data:image/png;base64,'.base64_encode($qr->png($rsvp->qr_token, 5)),
        ];

        return (new BrandedMail('[Preview] '.Notifier::TEMPLATES[$template], $template, $data))->render();
    }

    public function campaign(Request $request, string $campaign, Campaigns $campaigns)
    {
        abort_unless(array_key_exists($campaign, Campaigns::CAMPAIGNS), 404);
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Please tick the confirmation box before sending.']);

        $r = $campaigns->run($campaign);

        return back()->with($r['failed'] ? 'error' : 'status', sprintf('%s: %d processed, %d already received it, %d failed.', Campaigns::CAMPAIGNS[$campaign], $r['sent'], $r['skipped'], $r['failed']));
    }
}
