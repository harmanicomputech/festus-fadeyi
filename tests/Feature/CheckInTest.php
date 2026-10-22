<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Services\QrCodes;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    public function test_valid_code_admits_once_and_repeat_scans_are_not_new_arrivals(): void
    {
        $rsvp = $this->makeRsvp(['full_name' => 'Arriving Guest', 'party_size' => 2]);
        $staff = $this->admin('checkin');

        $this->actingAs($staff)->postJson('/admin/check-in/lookup', ['code' => QrCodes::PREFIX.$rsvp->qr_token])
            ->assertOk()->assertJson(['result' => 'valid', 'guest' => ['name' => 'Dr Arriving Guest', 'seats' => 2]]);

        $this->actingAs($staff)->postJson(route('admin.checkin.admit', $rsvp))->assertJson(['admit' => 'admitted']);
        $first = $rsvp->fresh()->checked_in_at;
        $this->assertNotNull($first);
        $this->assertSame($staff->id, $rsvp->fresh()->checked_in_by);

        $this->travel(5)->minutes();
        $this->actingAs($staff)->postJson(route('admin.checkin.admit', $rsvp))->assertJson(['admit' => 'repeat']);
        $this->assertEquals($first, $rsvp->fresh()->checked_in_at);
        $this->actingAs($staff)->postJson('/admin/check-in/lookup', ['code' => $rsvp->qr_token])->assertJson(['result' => 'already']);
        $this->assertSame(1, CheckIn::query()->where('result', 'admitted')->count());
    }

    public function test_invalid_and_unconfirmed_codes_are_refused(): void
    {
        $staff = $this->admin('checkin');
        $this->actingAs($staff)->postJson('/admin/check-in/lookup', ['code' => 'FF85:'.str_repeat('Q', 40)])->assertJson(['result' => 'invalid']);
        $this->actingAs($staff)->postJson('/admin/check-in/lookup', ['code' => 'https://evil.example/x'])->assertJson(['result' => 'invalid']);

        config(['event.rsvp.capacity' => 0]);
        $waitlisted = $this->makeRsvp();
        $this->actingAs($staff)->postJson('/admin/check-in/lookup', ['code' => $waitlisted->qr_token])->assertJson(['result' => 'not_confirmed']);
        $this->actingAs($staff)->postJson(route('admin.checkin.admit', $waitlisted))->assertJson(['admit' => 'not_confirmed']);
        $this->assertNull($waitlisted->fresh()->checked_in_at);
    }

    public function test_qr_payload_contains_no_personal_data(): void
    {
        $rsvp = $this->makeRsvp(['full_name' => 'Private Person', 'email' => 'private@example.com']);
        $payload = QrCodes::PREFIX.$rsvp->qr_token;
        $this->assertStringNotContainsStringIgnoringCase('private', $payload);
        $this->assertMatchesRegularExpression('/^FF85:[A-Za-z0-9]{40}$/', $payload);
    }

    public function test_search_finds_guests_for_staff(): void
    {
        $this->makeRsvp(['full_name' => 'Searchable Guest']);
        $this->actingAs($this->admin('checkin'))->getJson('/admin/check-in/search?q=search')
            ->assertJsonPath('guests.0.guest.name', 'Dr Searchable Guest');
    }
}
