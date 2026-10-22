<?php

namespace App\Services;

use App\Models\Donation;
use App\Support\EventInfo;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Thin client for the Paystack Transactions API. */
class Paystack
{
    private function client(): PendingRequest
    {
        $secret = config('services.paystack.secret_key');
        if (! $secret) {
            throw new RuntimeException('Paystack is not configured.');
        }

        return Http::baseUrl(rtrim(config('services.paystack.base_url'), '/'))
            ->withToken($secret)
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 500, throw: false);
    }

    /** Start a hosted checkout; returns the authorization URL to redirect to. */
    public function initialize(Donation $donation, string $callbackUrl): string
    {
        $payload = [
            'email' => $donation->email,
            'amount' => $donation->amount_kobo,
            'currency' => 'NGN',
            'reference' => $donation->reference,
            'callback_url' => $callbackUrl,
            'metadata' => [
                'donation_id' => $donation->id,
                'custom_fields' => [
                    ['display_name' => 'Event', 'variable_name' => 'event', 'value' => config('event.short_title')],
                    ['display_name' => 'Beneficiary', 'variable_name' => 'beneficiary', 'value' => EventInfo::charityLabel()],
                ],
            ],
        ];

        if ($sub = config('services.paystack.subaccount')) {
            $payload['subaccount'] = $sub;
            $payload['bearer'] = config('services.paystack.bearer', 'subaccount');
        }

        $response = $this->client()->post('/transaction/initialize', $payload);

        if (! $response->successful() || ! $response->json('status') || ! $response->json('data.authorization_url')) {
            throw new RuntimeException('Paystack initialise failed: '.($response->json('message') ?? $response->status()));
        }

        return $response->json('data.authorization_url');
    }

    /**
     * Server-side verification. Returns the transaction data, or null if
     * Paystack could not be reached or does not know the reference.
     *
     * @return array<string, mixed>|null
     */
    public function verify(string $reference): ?array
    {
        $response = $this->client()->get('/transaction/verify/'.rawurlencode($reference));

        if (! $response->successful() || ! $response->json('status')) {
            return null;
        }

        return $response->json('data');
    }

    public function validWebhookSignature(string $rawBody, ?string $signature): bool
    {
        $secret = config('services.paystack.secret_key');

        return $secret && $signature && hash_equals(hash_hmac('sha512', $rawBody, $secret), $signature);
    }
}
