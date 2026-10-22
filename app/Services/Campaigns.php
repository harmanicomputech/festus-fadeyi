<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\Invitation;
use App\Models\Rsvp;
use App\Support\EventInfo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Scheduled emails. Each campaign sends at most once per recipient
 * (enforced by Notifier dedupe keys), only to the intended audience,
 * and never to anyone who has opted out of reminders.
 */
class Campaigns
{
    public const CAMPAIGNS = [
        'rsvp_reminder' => 'RSVP reminder — invitees who have not replied',
        'event_reminder' => 'Event reminder — confirmed guests',
        'day_of' => 'Day-of note — confirmed guests',
        'thank_you' => 'Thank-you — attendees',
    ];

    public function __construct(private Notifier $notifier) {}

    public function scheduledAt(string $campaign): Carbon
    {
        return Carbon::parse(config("event.email.schedule.$campaign"), EventInfo::tz());
    }

    /** Due inside a 24-hour window after the scheduled time. */
    public function isDue(string $campaign, ?Carbon $now = null): bool
    {
        $now ??= now(EventInfo::tz());
        $at = $this->scheduledAt($campaign);

        return $now->greaterThanOrEqualTo($at) && $now->lessThan($at->copy()->addDay());
    }

    public function recipients(string $campaign): Collection
    {
        return match ($campaign) {
            // Invited, invitation actually sent, no reply yet, not opted out.
            'rsvp_reminder' => Invitation::query()
                ->whereDoesntHave('rsvp')
                ->whereNotIn('email', Rsvp::query()->select('email'))
                ->whereNotNull('invitation_sent_at')
                ->where('email_opt_out', false)
                ->get(),
            'event_reminder', 'day_of' => Rsvp::query()->confirmed()->where('email_opt_out', false)->get(),
            // Prefer guests actually checked in; fall back to confirmed guests if check-in was not used.
            'thank_you' => Rsvp::query()->whereNotNull('checked_in_at')->exists()
                ? Rsvp::query()->whereNotNull('checked_in_at')->where('email_opt_out', false)->get()
                : Rsvp::query()->confirmed()->where('email_opt_out', false)->get(),
            default => collect(),
        };
    }

    /** @return array{sent: int, skipped: int, failed: int} */
    public function run(string $campaign): array
    {
        $result = ['sent' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($this->recipients($campaign) as $recipient) {
            $alreadyDone = EmailLog::query()
                ->where('dedupe_key', $this->dedupeKey($campaign, $recipient))
                ->whereIn('status', ['sent', 'logged'])->exists();
            if ($alreadyDone) {
                $result['skipped']++;

                continue;
            }

            $ok = match ($campaign) {
                'rsvp_reminder' => $this->notifier->rsvpReminder($recipient),
                'event_reminder' => $this->notifier->eventReminder($recipient),
                'day_of' => $this->notifier->dayOf($recipient),
                'thank_you' => $this->notifier->thankYou($recipient),
            };
            $result[$ok ? 'sent' : 'failed']++;
        }

        return $result;
    }

    public function dedupeKey(string $campaign, Invitation|Rsvp $recipient): string
    {
        return $recipient instanceof Invitation
            ? "$campaign:inv:{$recipient->id}"
            : "$campaign:rsvp:{$recipient->id}";
    }
}
