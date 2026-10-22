<?php

namespace Tests\Feature;

use App\Models\Donation;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_sections_render_in_order_without_speakers(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('id="speakers"', $html);
        $this->assertStringNotContainsString('id="gallery"', $html); // hidden until photographs are added

        $order = ['id="top"', 'id="welcome"', 'id="celebrant"', 'id="book"', 'id="give"', 'id="programme"', 'id="venue"', 'id="rsvp"', 'id="contact"'];
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
            ->assertSee('By Dr. Festus Fadeyi')->assertDontSee('Synopsis — coming soon')->assertSee('Read full biography')
            ->assertSee('Cover artwork')
            ->assertDontSee('Lorem');
    }

    public function test_giving_is_online_and_bank_transfer_only(): void
    {
        $this->get('/')->assertSee('Give online')->assertSee('Donate now')->assertSee(route('donate.show'))
            ->assertSee('Bank transfer')->assertSee('1229809568')->assertSee('Zenith Bank')->assertSee('DR FESTUS FADEYI SYMPOSIUM')
            ->assertDontSee('At the venue')->assertDontSee('Pledge with your RSVP')->assertDontSee('name="pledge_amount"', false);
    }

    public function test_welcome_note_from_the_host_is_shown(): void
    {
        $this->get('/')->assertSee('It is our great honour to welcome you')->assertSee('Caritas Communications Limited');
    }

    public function test_programme_shows_timings_and_footer_omits_host(): void
    {
        $html = $this->get('/')->assertSee('11:00 AM')->assertSee('12:50 PM')->assertDontSee('Timings — coming soon')->assertDontSee('marquee')->getContent();
        $footer = substr($html, strpos($html, '<footer'));
        $this->assertStringNotContainsString('Caritas Communications Limited', $footer);
    }

    public function test_gallery_appears_once_photographs_are_added(): void
    {
        config(['event.gallery' => [['src' => '/images/gallery/a.jpg', 'alt' => 'Archive photo', 'group' => 'archive']]]);
        $this->get('/')->assertSee('id="gallery"', false)->assertSee('Archive photo');
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
