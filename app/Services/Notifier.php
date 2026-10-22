<?php

namespace App\Services;

use App\Mail\BrandedMail;
use App\Models\Donation;
use App\Models\EmailLog;
use App\Models\Invitation;
use App\Models\Rsvp;
use App\Support\EventInfo;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Sends the seven branded emails and records each attempt in email_logs.
 * A send never throws into the guest's request: failures are logged and
 * visible in the admin dashboard.
 */
class Notifier
{
    public const TEMPLATES = [
        'invitation' => 'Invitation',
        'rsvp_confirmation' => 'RSVP confirmation',
        'donation_receipt' => 'Donation receipt',
        'rsvp_reminder' => 'RSVP reminder',
        'event_reminder' => 'Event reminder',
        'day_of' => 'Day-of note',
        'thank_you' => 'Thank-you',
    ];

    public function __construct(private QrCodes $qr) {}

    public function invitation(Invitation $invitation): bool
    {
        $ok = $this->deliver('invitation', $invitation->email,
            'You are invited: '.config('event.title'),
            ['invitation' => $invitation, 'name' => $invitation->salutation(), 'rsvpUrl' => $invitation->link(),
                'unsubscribeUrl' => $this->unsubscribeUrl('invitation', $invitation->id)],
            null, ['invitation_id' => $invitation->id]);

        if ($ok) {
            $invitation->forceFill(['invitation_sent_at' => now()])->save();
        }

        return $ok;
    }

    public function rsvpConfirmation(Rsvp $rsvp, bool $duplicate = false, bool $updated = false, ?string $previousStatus = null): bool
    {
        $subject = match (true) {
            $duplicate => 'Your RSVP details — '.config('event.short_title'),
            $rsvp->status === Rsvp::STATUS_CONFIRMED && $previousStatus === Rsvp::STATUS_WAITLISTED => 'Good news — your seat is confirmed',
            $rsvp->status === Rsvp::STATUS_CONFIRMED => $updated ? 'Your updated RSVP is confirmed' : 'Your RSVP is confirmed — '.config('event.short_title'),
            $rsvp->status === Rsvp::STATUS_WAITLISTED => 'You are on the waitlist — '.config('event.short_title'),
            $rsvp->status === Rsvp::STATUS_MAYBE => 'Thank you for your response — '.config('event.short_title'),
            default => 'Thank you for letting us know — '.config('event.short_title'),
        };

        $attachments = $rsvp->isConfirmed()
            ? ['check-in-code.png' => $this->qr->png($rsvp->qr_token)]
            : [];

        return $this->deliver('rsvp_confirmation', $rsvp->email, $subject, [
            'rsvp' => $rsvp,
            'name' => $rsvp->salutation(),
            'duplicate' => $duplicate,
            'updated' => $updated,
            'promoted' => $previousStatus === Rsvp::STATUS_WAITLISTED && $rsvp->isConfirmed(),
            'manageUrl' => $rsvp->manageUrl(),
            'pledge' => $rsvp->pledge()->where('status', 'open')->first(),
        ], null, ['rsvp_id' => $rsvp->id], $attachments);
    }

    public function donationReceipt(Donation $donation): bool
    {
        if (! $donation->email || $donation->status !== 'success' || $donation->receipt_sent_at) {
            return false;
        }

        $ok = $this->deliver('donation_receipt', $donation->email,
            'Thank you for your gift — receipt '.$donation->reference,
            ['donation' => $donation, 'name' => $donation->is_anonymous ? null : $donation->donor_name],
            'donation_receipt:'.$donation->id, ['donation_id' => $donation->id]);

        if ($ok) {
            $donation->forceFill(['receipt_sent_at' => now()])->save();
        }

        return $ok;
    }

    public function rsvpReminder(Invitation $invitation): bool
    {
        return $this->deliver('rsvp_reminder', $invitation->email,
            'A gentle reminder: kindly RSVP by '.config('event.rsvp.deadline_label'),
            ['name' => $invitation->salutation(), 'rsvpUrl' => $invitation->link(),
                'unsubscribeUrl' => $this->unsubscribeUrl('invitation', $invitation->id)],
            'rsvp_reminder:inv:'.$invitation->id, ['invitation_id' => $invitation->id]);
    }

    public function eventReminder(Rsvp $rsvp): bool
    {
        return $this->guestCampaign('event_reminder', $rsvp, 'In three days: '.config('event.title'));
    }

    public function dayOf(Rsvp $rsvp): bool
    {
        return $this->guestCampaign('day_of', $rsvp, 'Welcome — we look forward to receiving you today');
    }

    public function thankYou(Rsvp $rsvp): bool
    {
        return $this->guestCampaign('thank_you', $rsvp, 'With heartfelt gratitude — '.config('event.short_title'));
    }

    private function guestCampaign(string $template, Rsvp $rsvp, string $subject): bool
    {
        $attachments = $template !== 'thank_you' && $rsvp->isConfirmed()
            ? ['check-in-code.png' => $this->qr->png($rsvp->qr_token)]
            : [];

        return $this->deliver($template, $rsvp->email, $subject, [
            'rsvp' => $rsvp,
            'name' => $rsvp->salutation(),
            'manageUrl' => $rsvp->manageUrl(),
            'unsubscribeUrl' => $this->unsubscribeUrl('rsvp', $rsvp->id),
        ], $template.':rsvp:'.$rsvp->id, ['rsvp_id' => $rsvp->id], $attachments);
    }

    public function unsubscribeUrl(string $type, int $id): string
    {
        return URL::signedRoute('email.unsubscribe', ['type' => $type, 'id' => $id]);
    }

    /** Shared data for every template. */
    public static function commonData(): array
    {
        return [
            'event' => config('event'),
            'siteUrl' => url('/'),
            'donateUrl' => route('donate.show'),
            'googleCalendarUrl' => EventInfo::googleCalendarUrl(),
            'outlookCalendarUrl' => EventInfo::outlookCalendarUrl(),
            'icsUrl' => EventInfo::icsUrl(),
            'charityLabel' => EventInfo::charityLabel(),
            'charityName' => EventInfo::charityName(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $relations
     * @param  array<string, string>  $attachments
     */
    private function deliver(string $template, string $to, string $subject, array $data, ?string $dedupeKey, array $relations, array $attachments = []): bool
    {
        if ($dedupeKey && EmailLog::query()->where('dedupe_key', $dedupeKey)->whereIn('status', ['sent', 'logged'])->exists()) {
            return false;
        }

        $status = in_array(config('mail.default'), ['log', 'array'], true) ? 'logged' : 'sent';
        $error = null;

        try {
            Mail::to($to)->send(new BrandedMail($subject, $template, $data + self::commonData(), $attachments));
        } catch (Throwable $e) {
            report($e);
            $status = 'failed';
            $error = mb_substr($e->getMessage(), 0, 1000);
        }

        $attributes = ['template' => $template, 'recipient' => $to, 'subject' => $subject, 'status' => $status, 'error' => $error] + $relations;

        if ($dedupeKey) {
            EmailLog::query()->updateOrCreate(['dedupe_key' => $dedupeKey], $attributes);
        } else {
            EmailLog::query()->create($attributes);
        }

        return $status !== 'failed';
    }
}
