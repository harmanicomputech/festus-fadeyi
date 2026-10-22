<?php

namespace Tests;

use App\Models\Rsvp;
use App\Models\User;
use App\Services\RsvpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // A fixed moment before the RSVP deadline (Lagos time).
        $this->travelTo(Carbon::parse('2026-11-01 12:00:00', 'Africa/Lagos'));
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    protected function rsvpPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Dr',
            'full_name' => 'Test Guest',
            'email' => 'guest'.uniqid().'@example.com',
            'phone' => '0803 000 0000',
            'organisation' => 'Example Ltd',
            'attendance' => 'yes',
            'party_size' => 1,
            'dietary_needs' => '',
            'pledge_amount' => '',
            'message' => '',
            'privacy' => '1',
        ], $overrides);
    }

    protected function makeRsvp(array $overrides = []): Rsvp
    {
        [$rsvp] = app(RsvpService::class)->submit($this->rsvpPayload($overrides), null);

        return $rsvp->fresh();
    }

    protected function admin(string $role = 'admin'): User
    {
        return User::query()->create(['name' => ucfirst($role).' User', 'email' => $role.'@example.com', 'password' => 'Secret-Password-1', 'role' => $role]);
    }
}
