<?php

namespace Tests\Feature;

use App\Models\Donation;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_all_eleven_sections_render_in_the_specified_order(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $order = ['id="top"', 'id="welcome"', 'id="celebrant"', 'id="book"', 'id="give"', 'id="programme"', 'id="speakers"', 'id="venue"', 'id="rsvp"', 'id="gallery"', 'id="contact"'];
        $positions = array_map(fn ($needle) => strpos($html, $needle), $order);
        $this->assertNotContains(false, $positions, 'A section is missing');
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'Sections are out of order');
    }

    public function test_hero_contains_verified_event_details_and_ctas(): void
    {
        $this->get('/')
            ->assertSee('Symposium in Honour of')
            ->assertSee('A Symposium in Honour &amp; Book Presentation', false)
            ->assertSee('Forged in His Fire')
            ->assertSee('Thursday, 10 December 2026')
            ->assertSee('11:00 AM – 1:00 PM')
            ->assertSee('Sky Restaurant, Eko Hotels, Victoria Island, Lagos')
            ->assertSee('Caritas Communications Limited')
            ->assertSee('href="#rsvp"', false)
            ->assertSee('href="#give"', false);
    }

    public function test_missing_client_content_is_shown_as_labelled_placeholders(): void
    {
        $this->get('/')
            ->assertSee('Welcome message from the host or family')
            ->assertSee('Author’s name to be confirmed')
            ->assertSee('Charity to be announced')
            ->assertSee('Dedicated bank account details')
            ->assertSee('Online giving opens shortly')
            ->assertSee('Cover artwork')
            ->assertDontSee('Lorem');
    }

    public function test_footer_has_contact_privacy_and_calendar_links(): void
    {
        $this->get('/')
            ->assertSee('Fisayo Ayodele')
            ->assertSee('fisayo@caritaspr.com')
            ->assertSee('09063684621')
            ->assertSee(route('privacy'))
            ->assertSee(route('calendar.ics'))
            ->assertSee(route('donate.show'));
    }

    public function test_donors_are_acknowledged_only_with_consent_and_amounts_only_with_separate_consent(): void
    {
        $base = ['currency' => 'NGN', 'method' => 'paystack', 'status' => 'success', 'paid_at' => now(), 'verified_at' => now()];
        Donation::query()->create(['reference' => 'R1', 'amount_kobo' => 5000000, 'donor_name' => 'Consenting Donor', 'acknowledge_publicly' => true, 'show_amount_publicly' => false] + $base);
        Donation::query()->create(['reference' => 'R2', 'amount_kobo' => 7700000, 'donor_name' => 'Amount Donor', 'acknowledge_publicly' => true, 'show_amount_publicly' => true] + $base);
        Donation::query()->create(['reference' => 'R3', 'amount_kobo' => 9900000, 'donor_name' => 'Private Donor', 'acknowledge_publicly' => false] + $base);
        Donation::query()->create(['reference' => 'R4', 'amount_kobo' => 1234500, 'donor_name' => 'Pending Donor', 'acknowledge_publicly' => true, 'status' => 'pending'] + $base);

        $this->get('/')
            ->assertSee('Consenting Donor')
            ->assertDontSee('₦50,000')
            ->assertSee('Amount Donor')
            ->assertSee('₦77,000')
            ->assertDontSee('Private Donor')
            ->assertDontSee('Pending Donor');
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_privacy_page_renders(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('Nigeria Data Protection Act');
    }
}
