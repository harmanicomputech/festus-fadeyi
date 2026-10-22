<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\EmailLog;
use App\Models\Invitation;
use App\Models\Pledge;
use App\Models\Rsvp;
use App\Models\Setting;
use App\Services\RsvpService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RsvpTest extends TestCase
{
    public function test_guest_can_rsvp_and_is_confirmed_with_qr_and_email(): void
    {
        $response = $this->post('/rsvp', $this->rsvpPayload(['email' => 'Ada@Example.com', 'party_size' => 2]));

        $rsvp = Rsvp::query()->firstOrFail();
        $this->assertSame('ada@example.com', $rsvp->email);
        $this->assertSame(Rsvp::STATUS_CONFIRMED, $rsvp->status);
        $this->assertSame(2, $rsvp->party_size);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{40}$/', $rsvp->qr_token);
        $response->assertRedirect(route('rsvp.manage', $rsvp->plainManageToken()));

        $this->assertDatabaseHas('email_logs', ['template' => 'rsvp_confirmation', 'recipient' => 'ada@example.com', 'rsvp_id' => $rsvp->id]);
        $this->get(route('rsvp.manage', $rsvp->plainManageToken()))->assertOk()->assertSee('Your place is confirmed')->assertSee($rsvp->qrUrl());
        $this->get($rsvp->qrUrl())->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_validation_rejects_bad_input(): void
    {
        $this->post('/rsvp', $this->rsvpPayload(['title' => 'Sir', 'email' => 'not-an-email', 'phone' => 'abc', 'party_size' => 3, 'privacy' => null]))
            ->assertSessionHasErrors(['title', 'email', 'phone', 'party_size', 'privacy']);
        $this->post('/rsvp', $this->rsvpPayload(['attendance' => 'perhaps']))->assertSessionHasErrors('attendance');
        $this->assertSame(0, Rsvp::query()->count());
    }

    public function test_declining_does_not_require_party_size(): void
    {
        $this->post('/rsvp', $this->rsvpPayload(['attendance' => 'no', 'party_size' => null]))->assertSessionHasNoErrors();
        $this->assertSame(Rsvp::STATUS_DECLINED, Rsvp::query()->first()->status);
    }

    public function test_honeypot_rejects_bots(): void
    {
        $this->post('/rsvp', $this->rsvpPayload(['website' => 'http://spam']))->assertSessionHasErrors('website');
        $this->assertSame(0, Rsvp::query()->count());
    }

    public function test_duplicate_rsvp_is_not_created_and_manage_link_is_re_sent(): void
    {
        $this->post('/rsvp', $this->rsvpPayload(['email' => 'dup@example.com', 'full_name' => 'First']));
        $this->post('/rsvp', $this->rsvpPayload(['email' => 'DUP@example.com', 'full_name' => 'Second', 'attendance' => 'no']))
            ->assertRedirect(route('home').'#rsvp')
            ->assertSessionHas('rsvp_notice');

        $this->assertSame(1, Rsvp::query()->count());
        $this->assertSame('First', Rsvp::query()->first()->full_name);
        $this->assertSame(2, EmailLog::query()->where('template', 'rsvp_confirmation')->count());
    }

    public function test_capacity_waitlist_and_automatic_promotion(): void
    {
        config(['event.rsvp.capacity' => 3]);

        $a = $this->makeRsvp(['party_size' => 2]);
        $b = $this->makeRsvp(['party_size' => 2]);
        $c = $this->makeRsvp(['party_size' => 1]);
        $this->assertSame(Rsvp::STATUS_CONFIRMED, $a->status);
        $this->assertSame(Rsvp::STATUS_WAITLISTED, $b->status);
        $this->assertSame(Rsvp::STATUS_CONFIRMED, $c->status); // a single seat still fits
        $this->assertSame(3, app(RsvpService::class)->seatsUsed());

        $d = $this->makeRsvp(['party_size' => 1]);
        $this->assertSame(Rsvp::STATUS_WAITLISTED, $d->status);

        // A cancels: B (earliest, needs 2) is promoted, D stays waitlisted.
        $this->post(route('rsvp.cancel', $a->plainManageToken()))->assertRedirect();
        $this->assertSame(Rsvp::STATUS_DECLINED, $a->fresh()->status);
        $this->assertSame(Rsvp::STATUS_CONFIRMED, $b->fresh()->status);
        $this->assertSame(Rsvp::STATUS_WAITLISTED, $d->fresh()->status);
        $this->assertSame(3, app(RsvpService::class)->seatsUsed());
        $this->assertTrue(EmailLog::query()->where('rsvp_id', $b->id)->where('subject', 'Good news — your seat is confirmed')->exists());
    }

    public function test_confirmed_guest_cannot_grow_party_beyond_capacity(): void
    {
        config(['event.rsvp.capacity' => 2]);
        $a = $this->makeRsvp(['party_size' => 1]);
        $this->makeRsvp(['party_size' => 1]);

        $this->put(route('rsvp.update', $a->plainManageToken()), $this->rsvpPayload(['email' => null, 'party_size' => 2, 'privacy' => null]))
            ->assertSessionHasErrors('party_size');
        $this->assertSame(1, $a->fresh()->party_size);
        $this->assertSame(Rsvp::STATUS_CONFIRMED, $a->fresh()->status);
    }

    public function test_guest_can_update_reply_through_secure_link(): void
    {
        $r = $this->makeRsvp(['attendance' => 'maybe']);
        $this->assertSame(Rsvp::STATUS_MAYBE, $r->status);

        $this->put(route('rsvp.update', $r->plainManageToken()), $this->rsvpPayload(['email' => null, 'privacy' => null, 'attendance' => 'yes', 'party_size' => 2, 'full_name' => 'Updated Name']))
            ->assertSessionHasNoErrors();
        $r->refresh();
        $this->assertSame(Rsvp::STATUS_CONFIRMED, $r->status);
        $this->assertSame('Updated Name', $r->full_name);

        $this->get('/rsvp/'.str_repeat('x', 48))->assertNotFound();
    }

    public function test_rsvps_close_after_the_deadline(): void
    {
        $maybe = $this->makeRsvp(['attendance' => 'maybe']);
        $yes = $this->makeRsvp(['attendance' => 'yes']);

        $this->travelTo(Carbon::parse('2026-12-05 00:00:01', 'Africa/Lagos'));

        $this->post('/rsvp', $this->rsvpPayload())->assertSessionHas('rsvp_notice');
        $this->assertSame(2, Rsvp::query()->count());
        $this->get('/')->assertSee('RSVPs closed on');

        // Cannot newly claim a seat after the deadline...
        $this->put(route('rsvp.update', $maybe->plainManageToken()), $this->rsvpPayload(['email' => null, 'privacy' => null, 'attendance' => 'yes']))
            ->assertSessionHasErrors('attendance');
        // ...but can still cancel.
        $this->post(route('rsvp.cancel', $yes->plainManageToken()));
        $this->assertSame(Rsvp::STATUS_DECLINED, $yes->fresh()->status);
    }

    public function test_deadline_boundary_is_end_of_4_december_lagos_time(): void
    {
        $this->travelTo(Carbon::parse('2026-12-04 23:59:00', 'Africa/Lagos'));
        $this->post('/rsvp', $this->rsvpPayload())->assertRedirect();
        $this->assertSame(1, Rsvp::query()->count());
    }

    public function test_invite_only_mode_requires_a_valid_code(): void
    {
        Setting::put('invite_only', '1');
        $inv = Invitation::query()->create(['code' => 'ABCDEFGH23', 'name' => 'Invited Guest', 'email' => 'invited@example.com']);

        $this->post('/rsvp', $this->rsvpPayload())->assertSessionHasErrors('invite_code');
        $this->post('/rsvp', $this->rsvpPayload(['invite_code' => 'WRONG']))->assertSessionHasErrors('invite_code');

        $this->get('/i/ABCDEFGH23')->assertRedirect(route('home').'#rsvp');
        $this->get('/')->assertSee('An invitation for Invited Guest');
        $this->post('/rsvp', $this->rsvpPayload(['email' => 'invited@example.com']))->assertRedirect();

        $this->assertSame($inv->id, Rsvp::query()->first()->invitation_id);
    }

    public function test_pledge_is_recorded_separately_and_never_counted_as_received(): void
    {
        $this->post('/rsvp', $this->rsvpPayload(['pledge_amount' => '250,000']))->assertSessionHasNoErrors();
        $r = Rsvp::query()->firstOrFail();
        $pledge = Pledge::query()->firstOrFail();
        $this->assertSame($r->id, $pledge->rsvp_id);
        $this->assertSame(25000000, $pledge->amount_kobo);
        $this->assertSame('open', $pledge->status);
        $this->assertSame(0, Donation::query()->count());
    }

    public function test_manage_page_does_not_leak_other_guests(): void
    {
        $a = $this->makeRsvp(['full_name' => 'Alpha Person']);
        $this->makeRsvp(['full_name' => 'Beta Person']);
        $this->get(route('rsvp.manage', $a->plainManageToken()))->assertSee('Alpha Person')->assertDontSee('Beta Person');
    }

    public function test_qr_image_is_only_served_for_confirmed_guests(): void
    {
        $declined = $this->makeRsvp(['attendance' => 'no']);
        $this->get(route('qr.show', $declined->qr_token))->assertNotFound();
        $this->get('/qr/'.str_repeat('Z', 40).'.png')->assertNotFound();
    }
}
