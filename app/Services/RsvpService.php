<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\Pledge;
use App\Models\Rsvp;
use App\Models\Setting;
use App\Support\EventInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RsvpService
{
    public function __construct(private Notifier $notifier) {}

    public function seatsUsed(?int $excludingRsvpId = null): int
    {
        return (int) Rsvp::query()
            ->confirmed()
            ->when($excludingRsvpId, fn ($q) => $q->whereKeyNot($excludingRsvpId))
            ->sum('party_size');
    }

    public function seatsRemaining(): int
    {
        return max(0, EventInfo::capacity() - $this->seatsUsed());
    }

    /**
     * Create a new RSVP. Returns [Rsvp, bool $created]. When an RSVP already
     * exists for the email address, nothing is changed and the existing
     * record is returned so the guest can be sent their manage link.
     *
     * @param  array<string, mixed>  $data  validated input
     * @return array{0: Rsvp, 1: bool, 2: string|null}
     */
    public function submit(array $data, ?Invitation $invitation): array
    {
        $email = Invitation::normaliseEmail($data['email']);
        $invitation ??= Invitation::query()->where('email', $email)->first();

        $manageToken = null;

        [$rsvp, $created] = DB::transaction(function () use ($data, $email, $invitation, &$manageToken) {
            $this->lock();

            $existing = Rsvp::query()->where('email', $email)->first()
                ?? ($invitation ? Rsvp::query()->where('invitation_id', $invitation->id)->first() : null);
            if ($existing) {
                return [$existing, false];
            }

            $rsvp = new Rsvp;
            $rsvp->fill($this->attributes($data));
            $rsvp->email = $email;
            $rsvp->invitation_id = $invitation?->id;
            $rsvp->qr_token = Rsvp::newQrToken();
            $rsvp->privacy_accepted_at = now();
            $manageToken = $rsvp->issueManageToken();
            $this->applyStatus($rsvp, null);
            $rsvp->save();

            $this->syncPledge($rsvp, $data['pledge_amount'] ?? null);

            return [$rsvp, true];
        });

        if ($created) {
            $this->notifier->rsvpConfirmation($rsvp);
        } else {
            $this->notifier->rsvpConfirmation($rsvp, duplicate: true);
        }

        return [$rsvp, $created, $manageToken];
    }

    /** Update from the guest's secure manage link. */
    public function update(Rsvp $rsvp, array $data): Rsvp
    {
        $before = $rsvp->status;

        DB::transaction(function () use ($rsvp, $data) {
            $this->lock();
            $rsvp->refresh();
            $oldStatus = $rsvp->status;
            $oldParty = $rsvp->party_size;

            $rsvp->fill($this->attributes($data));

            if (! EventInfo::rsvpOpen()) {
                $gainsSeat = $rsvp->attendance === 'yes' && $oldStatus !== Rsvp::STATUS_CONFIRMED;
                $growsParty = $oldStatus === Rsvp::STATUS_CONFIRMED && $rsvp->party_size > $oldParty;
                if ($gainsSeat || $growsParty) {
                    throw ValidationException::withMessages([
                        'attendance' => 'RSVPs closed on '.config('event.rsvp.deadline_label').'. You can still reduce your party or let us know you can no longer attend.',
                    ]);
                }
            }

            if ($oldStatus === Rsvp::STATUS_CONFIRMED && $rsvp->attendance === 'yes') {
                $free = EventInfo::capacity() - $this->seatsUsed($rsvp->id);
                if ($rsvp->party_size > $free) {
                    throw ValidationException::withMessages([
                        'party_size' => 'We are sorry — there are not enough seats remaining to add another guest. Your existing reservation is unchanged.',
                    ]);
                }
            } else {
                $this->applyStatus($rsvp, $oldStatus);
            }

            $rsvp->cancelled_at = $rsvp->attendance === 'no' ? ($rsvp->cancelled_at ?? now()) : null;
            $rsvp->save();

            $this->syncPledge($rsvp, $data['pledge_amount'] ?? null);
        });

        $this->notifier->rsvpConfirmation($rsvp, updated: true, previousStatus: $before);
        $this->promoteWaitlist();

        return $rsvp;
    }

    public function cancel(Rsvp $rsvp): Rsvp
    {
        DB::transaction(function () use ($rsvp) {
            $this->lock();
            $rsvp->refresh();
            $rsvp->attendance = 'no';
            $rsvp->status = Rsvp::STATUS_DECLINED;
            $rsvp->cancelled_at = now();
            $rsvp->save();
        });

        $this->notifier->rsvpConfirmation($rsvp, updated: true);
        $this->promoteWaitlist();

        return $rsvp;
    }

    /** Admin override: confirm a waitlisted guest even if it exceeds capacity. */
    public function adminSetStatus(Rsvp $rsvp, string $status): Rsvp
    {
        DB::transaction(function () use ($rsvp, $status) {
            $this->lock();
            $rsvp->refresh();
            $rsvp->status = $status;
            $rsvp->attendance = match ($status) {
                Rsvp::STATUS_DECLINED => 'no',
                Rsvp::STATUS_MAYBE => 'maybe',
                default => 'yes',
            };
            if ($status === Rsvp::STATUS_CONFIRMED) {
                $rsvp->confirmed_at ??= now();
            }
            if ($status === Rsvp::STATUS_WAITLISTED) {
                $rsvp->waitlisted_at ??= now();
            }
            $rsvp->save();
        });

        if ($status !== Rsvp::STATUS_DECLINED) {
            $this->notifier->rsvpConfirmation($rsvp, updated: true);
        }
        $this->promoteWaitlist();

        return $rsvp;
    }

    /**
     * Move waitlisted guests into freed seats, earliest first. A party
     * that does not fit is skipped so smaller parties behind it can be seated.
     *
     * @return list<Rsvp>
     */
    public function promoteWaitlist(): array
    {
        if (EventInfo::eventOver()) {
            return [];
        }

        $promoted = DB::transaction(function () {
            $this->lock();
            $free = EventInfo::capacity() - $this->seatsUsed();
            $promoted = [];

            $waiting = Rsvp::query()
                ->where('status', Rsvp::STATUS_WAITLISTED)
                ->orderBy('waitlisted_at')->orderBy('id')
                ->get();

            foreach ($waiting as $rsvp) {
                if ($free <= 0) {
                    break;
                }
                if ($rsvp->party_size <= $free) {
                    $rsvp->status = Rsvp::STATUS_CONFIRMED;
                    $rsvp->confirmed_at = now();
                    $rsvp->save();
                    $free -= $rsvp->party_size;
                    $promoted[] = $rsvp;
                }
            }

            return $promoted;
        });

        foreach ($promoted as $rsvp) {
            $this->notifier->rsvpConfirmation($rsvp, updated: true, previousStatus: Rsvp::STATUS_WAITLISTED);
        }

        return $promoted;
    }

    private function applyStatus(Rsvp $rsvp, ?string $oldStatus): void
    {
        if ($rsvp->attendance === 'no') {
            $rsvp->status = Rsvp::STATUS_DECLINED;

            return;
        }
        if ($rsvp->attendance === 'maybe') {
            $rsvp->status = Rsvp::STATUS_MAYBE;

            return;
        }

        $free = EventInfo::capacity() - $this->seatsUsed($rsvp->id);
        if ($oldStatus === Rsvp::STATUS_WAITLISTED && $this->hasEarlierWaitlist($rsvp)) {
            $free = 0; // keep the queue fair
        }

        if ($rsvp->party_size <= $free) {
            $rsvp->status = Rsvp::STATUS_CONFIRMED;
            $rsvp->confirmed_at ??= now();
        } else {
            $rsvp->status = Rsvp::STATUS_WAITLISTED;
            $rsvp->waitlisted_at = $oldStatus === Rsvp::STATUS_WAITLISTED ? ($rsvp->waitlisted_at ?? now()) : now();
        }
    }

    private function hasEarlierWaitlist(Rsvp $rsvp): bool
    {
        return Rsvp::query()
            ->where('status', Rsvp::STATUS_WAITLISTED)
            ->whereKeyNot($rsvp->id)
            ->where('waitlisted_at', '<', $rsvp->waitlisted_at ?? now())
            ->where('party_size', '<=', $rsvp->party_size)
            ->exists();
    }

    /** @param array<string, mixed> $data */
    private function attributes(array $data): array
    {
        $attending = $data['attendance'];

        return [
            'title' => $data['title'],
            'full_name' => trim($data['full_name']),
            'phone' => trim($data['phone']),
            'organisation' => $data['organisation'] ?? null,
            'attendance' => $attending,
            'party_size' => $attending === 'no' ? 1 : (int) ($data['party_size'] ?? 1),
            'dietary_needs' => $data['dietary_needs'] ?? null,
            'message' => $data['message'] ?? null,
        ];
    }

    private function syncPledge(Rsvp $rsvp, mixed $amountNaira): void
    {
        $amountNaira = (int) preg_replace('/[^0-9]/', '', (string) $amountNaira) ?: null;
        $pledge = Pledge::query()->where('rsvp_id', $rsvp->id)->where('status', 'open')->first();

        if (! $amountNaira) {
            return; // leaving the field blank never cancels an existing pledge
        }

        if ($pledge) {
            $pledge->update(['amount_kobo' => $amountNaira * 100, 'name' => $rsvp->salutation(), 'email' => $rsvp->email]);

            return;
        }

        Pledge::query()->create([
            'rsvp_id' => $rsvp->id,
            'name' => $rsvp->salutation(),
            'email' => $rsvp->email,
            'amount_kobo' => $amountNaira * 100,
            'status' => 'open',
            'pay_token' => Pledge::newPayToken(),
        ]);
    }

    /** Serialise seat allocation across concurrent requests. */
    private function lock(): void
    {
        Setting::query()->firstOrCreate(['key' => 'capacity_lock'], ['value' => '1']);
        Setting::query()->whereKey('capacity_lock')->lockForUpdate()->first();
    }
}
