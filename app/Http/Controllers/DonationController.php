<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Pledge;
use App\Services\DonationService;
use App\Services\Paystack;
use App\Support\EventInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DonationController extends Controller
{
    public function __construct(private DonationService $donations, private Paystack $paystack) {}

    public function show(Request $request)
    {
        $pledge = $this->pledge($request->query('pledge'));

        return view('donate.show', [
            'pledge' => $pledge,
            'ready' => EventInfo::donationsReady(),
            'bankReady' => EventInfo::bankDetailsConfigured(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(EventInfo::donationsReady(), 503, 'Online giving is awaiting configuration.');

        $min = config('event.donations.min_amount');
        $max = config('event.donations.max_amount');
        $request->merge(['amount' => preg_replace('/[^0-9]/', '', (string) $request->input('amount'))]);

        $data = $request->validate([
            'amount' => ['required', 'integer', "min:$min", "max:$max"],
            'anonymous' => ['nullable', 'boolean'],
            'donor_name' => ['exclude_if:anonymous,1', 'required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9 ()\-]{7,20}$/'],
            'message' => ['nullable', 'string', 'max:1000'],
            'acknowledge_publicly' => ['nullable', 'boolean'],
            'show_amount_publicly' => ['nullable', 'boolean'],
            'privacy' => ['accepted'],
            'pledge' => ['nullable', 'string', 'max:64'],
            'website' => ['nullable', 'max:0'],
        ], [
            'amount.min' => 'The minimum gift is ₦'.number_format($min).'.',
            'donor_name.required' => 'Please enter your name, or choose to give anonymously.',
            'privacy.accepted' => 'Please confirm you have read the privacy notice.',
        ]);

        $donation = $this->donations->createPending($data, $this->pledge($data['pledge'] ?? null));

        try {
            $url = $this->paystack->initialize($donation, route('donate.callback'));
        } catch (Throwable $e) {
            report($e);
            $donation->update(['status' => 'failed', 'admin_note' => 'Could not start checkout.']);

            return back()->withInput()->withErrors(['amount' => 'We could not connect to the payment provider. No payment has been taken. Please try again in a moment.']);
        }

        return redirect()->away($url);
    }

    /** Paystack redirects here. We never trust the redirect: verify server-side. */
    public function callback(Request $request)
    {
        $reference = (string) $request->query('reference', $request->query('trxref', ''));
        $donation = Donation::query()->where('reference', $reference)->first();
        abort_unless($donation, 404);

        try {
            $this->donations->verifyWithGateway($donation);
        } catch (Throwable $e) {
            report($e);
        }

        return redirect()->route('donate.status', $donation->reference);
    }

    public function status(string $reference)
    {
        $donation = Donation::query()->where('reference', $reference)->where('method', 'paystack')->firstOrFail();

        if ($donation->status === 'pending') {
            try {
                $this->donations->verifyWithGateway($donation);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return view('donate.status', ['donation' => $donation]);
    }

    public function webhook(Request $request)
    {
        $raw = $request->getContent();
        if (! $this->paystack->validWebhookSignature($raw, $request->header('x-paystack-signature'))) {
            Log::warning('Rejected Paystack webhook with invalid signature', ['ip' => $request->ip()]);

            return response('invalid signature', 401);
        }

        $event = json_decode($raw, true);
        $reference = $event['data']['reference'] ?? null;

        if (in_array($event['event'] ?? null, ['charge.success'], true) && $reference) {
            $donation = Donation::query()->where('reference', $reference)->first();
            if ($donation) {
                // The payload is signed, but we still confirm via the verify API.
                $this->donations->verifyWithGateway($donation);
            }
        }

        return response('ok', 200);
    }

    private function pledge(?string $token): ?Pledge
    {
        if (! $token || strlen($token) !== 40) {
            return null;
        }

        return Pledge::query()->where('pay_token', $token)->where('status', 'open')->first();
    }
}
