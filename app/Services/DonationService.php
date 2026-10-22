<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\Pledge;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DonationService
{
    public function __construct(private Paystack $paystack, private Notifier $notifier) {}

    /** @param array<string, mixed> $data validated input */
    public function createPending(array $data, ?Pledge $pledge): Donation
    {
        return Donation::query()->create([
            'reference' => Donation::newReference(),
            'amount_kobo' => (int) round($data['amount'] * 100),
            'currency' => 'NGN',
            'donor_name' => ($data['anonymous'] ?? false) ? null : (trim($data['donor_name'] ?? '') ?: null),
            'is_anonymous' => (bool) ($data['anonymous'] ?? false),
            'email' => strtolower(trim($data['email'])),
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
            'acknowledge_publicly' => ! ($data['anonymous'] ?? false) && (bool) ($data['acknowledge_publicly'] ?? false),
            'show_amount_publicly' => ! ($data['anonymous'] ?? false) && (bool) ($data['acknowledge_publicly'] ?? false) && (bool) ($data['show_amount_publicly'] ?? false),
            'method' => 'paystack',
            'status' => 'pending',
            'pledge_id' => $pledge?->id,
            'rsvp_id' => $pledge?->rsvp_id,
            'privacy_accepted_at' => now(),
        ]);
    }

    /**
     * Ask Paystack for the authoritative status of a transaction and record
     * it. Safe to call repeatedly (callback, webhook, admin re-check).
     */
    public function verifyWithGateway(Donation $donation): Donation
    {
        if ($donation->status === 'success' || $donation->method !== 'paystack') {
            return $donation;
        }

        $data = $this->paystack->verify($donation->reference);
        if ($data === null) {
            return $donation; // unreachable or unknown: leave pending
        }

        $gatewayStatus = (string) ($data['status'] ?? '');
        $amountMatches = (int) ($data['amount'] ?? -1) === $donation->amount_kobo;
        $currencyMatches = strtoupper((string) ($data['currency'] ?? '')) === $donation->currency;
        $referenceMatches = ($data['reference'] ?? null) === $donation->reference;

        $becameSuccessful = DB::transaction(function () use ($donation, $data, $gatewayStatus, $amountMatches, $currencyMatches, $referenceMatches) {
            $locked = Donation::query()->whereKey($donation->id)->lockForUpdate()->first();
            if ($locked->status === 'success') {
                return false;
            }

            $locked->gateway_status = $gatewayStatus;
            $locked->gateway_payload = Arr::only($data, ['id', 'status', 'reference', 'amount', 'currency', 'channel', 'gateway_response', 'paid_at', 'fees', 'subaccount']);
            $locked->channel = $data['channel'] ?? null;

            if ($gatewayStatus === 'success' && $amountMatches && $currencyMatches && $referenceMatches) {
                $locked->status = 'success';
                $locked->paid_at = isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : now();
                $locked->verified_at = now();
                $locked->save();
                $this->fulfilPledge($locked);

                return true;
            }

            if ($gatewayStatus === 'success') {
                // Paid, but not what we asked for: never auto-accept. Flag for review.
                $locked->status = 'review';
                $locked->admin_note = 'Gateway reported success but amount/currency/reference did not match. Check the Paystack dashboard.';
            } elseif (in_array($gatewayStatus, ['failed', 'reversed'], true)) {
                $locked->status = 'failed';
            } elseif ($gatewayStatus === 'abandoned' && $locked->created_at->lt(now()->subHour())) {
                $locked->status = 'abandoned';
            }
            $locked->save();

            return false;
        });

        $donation->refresh();
        if ($becameSuccessful) {
            $this->notifier->donationReceipt($donation);
        }

        return $donation;
    }

    /**
     * Record a gift an administrator has confirmed outside the gateway
     * (e.g. a bank transfer seen on the account statement).
     *
     * @param  array<string, mixed>  $data
     */
    public function recordManual(array $data, User $admin): Donation
    {
        $donation = DB::transaction(function () use ($data, $admin) {
            $donation = Donation::query()->create([
                'reference' => $data['reference'] ?: Donation::newReference(),
                'amount_kobo' => (int) round($data['amount'] * 100),
                'currency' => 'NGN',
                'donor_name' => ($data['anonymous'] ?? false) ? null : ($data['donor_name'] ?? null),
                'is_anonymous' => (bool) ($data['anonymous'] ?? false),
                'email' => isset($data['email']) ? strtolower(trim($data['email'])) : null,
                'phone' => $data['phone'] ?? null,
                'message' => $data['message'] ?? null,
                'acknowledge_publicly' => ! ($data['anonymous'] ?? false) && (bool) ($data['acknowledge_publicly'] ?? false),
                'show_amount_publicly' => ! ($data['anonymous'] ?? false) && (bool) ($data['acknowledge_publicly'] ?? false) && (bool) ($data['show_amount_publicly'] ?? false),
                'method' => $data['method'],
                'status' => 'success',
                'paid_at' => Carbon::parse($data['paid_at'], config('event.timezone')),
                'verified_at' => now(),
                'recorded_by' => $admin->id,
                'admin_note' => $data['admin_note'] ?? null,
                'pledge_id' => $data['pledge_id'] ?? null,
            ]);
            $this->fulfilPledge($donation);

            return $donation;
        });

        if (! empty($data['send_receipt'])) {
            $this->notifier->donationReceipt($donation);
        }

        return $donation;
    }

    private function fulfilPledge(Donation $donation): void
    {
        if (! $donation->pledge_id) {
            return;
        }

        Pledge::query()->whereKey($donation->pledge_id)->where('status', 'open')
            ->update(['status' => 'fulfilled', 'donation_id' => $donation->id]);
    }
}
