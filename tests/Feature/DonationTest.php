<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\EmailLog;
use App\Models\Pledge;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DonationTest extends TestCase
{
    private function configure(): void
    {
        config([
            'services.paystack.secret_key' => 'sk_test_secret',
            'services.paystack.public_key' => 'pk_test_public',
            'event.charity.name' => 'Test Beneficiary Foundation',
        ]);
    }

    private array $verifyData = [];

    private bool $faked = false;

    /** Paystack stand-in; the verify response can change between calls. */
    private function fakeGateway(array $verifyData): void
    {
        $this->verifyData = $verifyData;
        if ($this->faked) {
            return;
        }
        $this->faked = true;
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/transaction/initialize')) {
                return Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc123']]);
            }
            if (str_contains($request->url(), '/transaction/verify/')) {
                return $this->verifyData ? Http::response(['status' => true, 'data' => $this->verifyData]) : Http::response(['status' => false, 'message' => 'Transaction reference not found'], 400);
            }

            return Http::response([], 404);
        });
    }

    private function give(array $overrides = []): Donation
    {
        $this->post('/donate', array_merge([
            'amount' => '50,000', 'donor_name' => 'Generous Giver', 'email' => 'giver@example.com',
            'message' => 'Congratulations!', 'privacy' => '1',
        ], $overrides))->assertRedirect('https://checkout.paystack.com/abc123');

        return Donation::query()->latest('id')->firstOrFail();
    }

    public function test_donation_flow_is_marked_awaiting_configuration_without_credentials(): void
    {
        $this->get('/donate')->assertOk()->assertSee('Online giving is awaiting configuration');
        $this->post('/donate', ['amount' => 5000, 'email' => 'a@example.com', 'donor_name' => 'X', 'privacy' => 1])->assertStatus(503);
        $this->assertSame(0, Donation::query()->count());
    }

    public function test_verified_payment_is_recorded_and_receipted(): void
    {
        $this->configure();
        $this->fakeGateway([]);
        $donation = $this->give();
        $this->assertSame('pending', $donation->status);
        $this->assertSame(5000000, $donation->amount_kobo);

        $this->fakeGateway(['status' => 'success', 'reference' => $donation->reference, 'amount' => 5000000, 'currency' => 'NGN', 'channel' => 'card', 'paid_at' => '2026-11-01T11:00:00Z']);
        $this->get('/donate/callback?reference='.$donation->reference)->assertRedirect(route('donate.status', $donation->reference));

        $donation->refresh();
        $this->assertSame('success', $donation->status);
        $this->assertNotNull($donation->verified_at);
        $this->assertSame('card', $donation->channel);
        $this->assertNotNull($donation->receipt_sent_at);
        $this->assertSame(1, EmailLog::query()->where('template', 'donation_receipt')->count());
        $this->get(route('donate.status', $donation->reference))->assertSee('Payment confirmed');

        // Re-verifying (webhook + callback) never sends a second receipt.
        $this->get('/donate/callback?reference='.$donation->reference);
        $this->assertSame(1, EmailLog::query()->where('template', 'donation_receipt')->count());
    }

    public function test_redirect_alone_never_marks_payment_as_paid(): void
    {
        $this->configure();
        $this->fakeGateway([]);
        $donation = $this->give();

        $this->fakeGateway(['status' => 'abandoned', 'reference' => $donation->reference, 'amount' => 5000000, 'currency' => 'NGN']);
        $this->get('/donate/callback?reference='.$donation->reference.'&status=success');
        $this->assertSame('pending', $donation->fresh()->status);
        $this->assertSame(0, EmailLog::query()->where('template', 'donation_receipt')->count());
    }

    public function test_amount_mismatch_is_flagged_for_review_not_accepted(): void
    {
        $this->configure();
        $this->fakeGateway([]);
        $donation = $this->give();

        $this->fakeGateway(['status' => 'success', 'reference' => $donation->reference, 'amount' => 100, 'currency' => 'NGN']);
        $this->get('/donate/callback?reference='.$donation->reference);
        $this->assertSame('review', $donation->fresh()->status);
        $this->assertSame(0, Donation::query()->successful()->count());
    }

    public function test_failed_payment_is_recorded_as_failed(): void
    {
        $this->configure();
        $this->fakeGateway([]);
        $donation = $this->give();
        $this->fakeGateway(['status' => 'failed', 'reference' => $donation->reference, 'amount' => 5000000, 'currency' => 'NGN']);
        $this->get('/donate/callback?reference='.$donation->reference);
        $this->assertSame('failed', $donation->fresh()->status);
    }

    public function test_webhook_requires_valid_signature_and_still_verifies_with_api(): void
    {
        $this->configure();
        $this->fakeGateway([]);
        $donation = $this->give();
        $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => $donation->reference, 'status' => 'success', 'amount' => 5000000]]);

        $this->call('POST', '/webhooks/paystack', [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => 'forged', 'CONTENT_TYPE' => 'application/json'], $body)->assertStatus(401);
        $this->assertSame('pending', $donation->fresh()->status);

        $this->fakeGateway(['status' => 'success', 'reference' => $donation->reference, 'amount' => 5000000, 'currency' => 'NGN']);
        $sig = hash_hmac('sha512', $body, 'sk_test_secret');
        $this->call('POST', '/webhooks/paystack', [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->assertSame('success', $donation->fresh()->status);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/transaction/verify/'.$donation->reference));
    }

    public function test_anonymous_gift_hides_name_and_cannot_be_publicly_acknowledged(): void
    {
        $this->configure();
        $this->fakeGateway([]);
        $donation = $this->give(['anonymous' => '1', 'donor_name' => 'Hidden', 'acknowledge_publicly' => '1', 'show_amount_publicly' => '1']);
        $this->assertNull($donation->donor_name);
        $this->assertTrue($donation->is_anonymous);
        $this->assertFalse($donation->acknowledge_publicly);
        $this->assertFalse($donation->show_amount_publicly);
    }

    public function test_paying_a_pledge_fulfils_it(): void
    {
        $this->configure();
        config(['event.donations.pledges_enabled' => true]);
        $this->post('/rsvp', $this->rsvpPayload(['pledge_amount' => '50000']));
        $pledge = Pledge::query()->firstOrFail();
        $this->get(route('donate.show', ['pledge' => $pledge->pay_token]))->assertSee('Redeeming the pledge');

        $this->fakeGateway([]);
        $donation = $this->give(['pledge' => $pledge->pay_token]);
        $this->assertSame($pledge->id, $donation->pledge_id);
        $this->fakeGateway(['status' => 'success', 'reference' => $donation->reference, 'amount' => 5000000, 'currency' => 'NGN']);
        $this->get('/donate/callback?reference='.$donation->reference);

        $this->assertSame('fulfilled', $pledge->fresh()->status);
        $this->assertSame($donation->id, $pledge->fresh()->donation_id);
    }

    public function test_subaccount_settlement_is_sent_to_paystack_when_configured(): void
    {
        $this->configure();
        config(['services.paystack.subaccount' => 'ACCT_charity']);
        $this->fakeGateway([]);
        $this->give();
        Http::assertSent(fn ($r) => str_contains($r->url(), 'initialize') && $r['subaccount'] === 'ACCT_charity' && $r['amount'] === 5000000);
    }

    public function test_admin_records_verified_bank_transfer_with_receipt(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/donations', [
            'amount' => '120000', 'method' => 'bank_transfer', 'paid_at' => '2026-10-30T10:00', 'donor_name' => 'Bank Giver',
            'email' => 'bank@example.com', 'send_receipt' => '1', 'confirm_verified' => '1',
        ])->assertSessionHasNoErrors();

        $d = Donation::query()->firstOrFail();
        $this->assertSame('success', $d->status);
        $this->assertSame(12000000, $d->amount_kobo);
        $this->assertSame($admin->id, $d->recorded_by);
        $this->assertNotNull($d->receipt_sent_at);

        $this->actingAs($admin)->get('/admin/donations?method=bank_transfer')->assertSee('Bank Giver');
        $this->actingAs($admin)->get('/admin/donations?method=paystack')->assertDontSee('Bank Giver');
    }
}
