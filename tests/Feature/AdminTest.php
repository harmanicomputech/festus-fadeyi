<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\Invitation;
use App\Models\Rsvp;
use App\Models\Setting;
use App\Services\Notifier;
use Tests\TestCase;

class AdminTest extends TestCase
{
    public function test_guests_cannot_reach_admin_pages_or_data(): void
    {
        foreach (['/admin', '/admin/guests', '/admin/guests/export/csv', '/admin/donations', '/admin/check-in', '/admin/messages', '/admin/reports'] as $url) {
            $this->get($url)->assertRedirect(route('admin.login'));
        }
        $this->postJson('/admin/check-in/lookup', ['code' => 'x'])->assertUnauthorized();
    }

    public function test_checkin_staff_can_only_use_the_check_in_desk(): void
    {
        $staff = $this->admin('checkin');
        $this->actingAs($staff)->get('/admin/check-in')->assertOk();
        $this->actingAs($staff)->get('/admin/guests')->assertRedirect(route('admin.checkin'));
        $this->actingAs($staff)->get('/admin/guests/export/csv')->assertRedirect(route('admin.checkin'));
    }

    public function test_admin_login_and_logout(): void
    {
        $this->admin();
        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/admin/login', ['email' => 'ADMIN@example.com', 'password' => 'Secret-Password-1'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
        $this->post('/admin/logout')->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_dashboard_and_guest_lists_show_rsvps_by_status(): void
    {
        config(['event.rsvp.capacity' => 1]);
        $this->makeRsvp(['full_name' => 'Accepted Person']);
        $this->makeRsvp(['full_name' => 'Waiting Person']);
        $this->makeRsvp(['full_name' => 'Declined Person', 'attendance' => 'no']);
        $this->makeRsvp(['full_name' => 'Unsure Person', 'attendance' => 'maybe']);
        Invitation::query()->create(['code' => 'PENDING234', 'name' => 'Silent Invitee', 'email' => 'silent@example.com']);

        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Accepted Person')->assertSee('Launch checklist');
        $this->actingAs($admin)->get('/admin/guests?status=confirmed')->assertSee('Accepted Person')->assertDontSee('Waiting Person');
        $this->actingAs($admin)->get('/admin/guests?status=waitlisted')->assertSee('Waiting Person');
        $this->actingAs($admin)->get('/admin/guests?status=declined')->assertSee('Declined Person');
        $this->actingAs($admin)->get('/admin/guests?status=maybe')->assertSee('Unsure Person');
        $this->actingAs($admin)->get('/admin/guests?status=pending')->assertSee('Silent Invitee');
        $this->actingAs($admin)->get('/admin/guests?q=unsure')->assertSee('Unsure Person')->assertDontSee('Accepted Person');
        $this->actingAs($admin)->get('/admin/guests/'.Rsvp::query()->first()->id)->assertOk();
    }

    public function test_exports_download_csv_and_excel_and_neutralise_formulas(): void
    {
        $this->makeRsvp(['full_name' => '=HYPERLINK("http://evil")', 'message' => '+cmd']);
        $admin = $this->admin();

        $csv = $this->actingAs($admin)->get('/admin/guests/export/csv');
        $csv->assertOk();
        $content = file_get_contents($csv->baseResponse->getFile()->getPathname());
        $this->assertStringContainsString('Full name', $content);
        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringContainsString("'+cmd", $content);

        $xlsx = $this->actingAs($admin)->get('/admin/guests/export/xlsx');
        $xlsx->assertOk();
        $this->assertSame('PK', substr(file_get_contents($xlsx->baseResponse->getFile()->getPathname()), 0, 2));

        $this->actingAs($admin)->get('/admin/donations/export/xlsx')->assertOk();
        $this->actingAs($admin)->get('/admin/pledges/export/csv')->assertOk();
        $this->actingAs($admin)->get('/admin/reports?download=xlsx')->assertOk();
        $this->actingAs($admin)->get('/admin/reports')->assertOk()->assertSee('Final guest');
    }

    public function test_admin_can_manage_invitations_and_invite_only_mode(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/invitations', ['title' => 'Chief', 'name' => 'Ade Bello', 'email' => 'ade@example.com'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/invitations/import', ['rows' => "Dr, Ngozi Okafor, ngozi@example.com, 0803\nNo Title Person, nt@example.com\nbad line\nChief, Ade Again, ade@example.com"]);
        $this->assertSame(3, Invitation::query()->count());
        $this->assertSame(['ade@example.com', 'ngozi@example.com', 'nt@example.com'], Invitation::query()->orderBy('email')->pluck('email')->all());

        $this->actingAs($admin)->post('/admin/invitations/send', ['mode' => 'unsent']);
        $this->assertSame(3, Invitation::query()->whereNotNull('invitation_sent_at')->count());
        $this->assertDatabaseCount('email_logs', 3);

        $this->actingAs($admin)->post('/admin/invitations/settings', ['invite_only' => 1]);
        $this->assertTrue(Setting::inviteOnly());
    }

    public function test_admin_status_override_and_messages_page(): void
    {
        config(['event.rsvp.capacity' => 1]);
        $this->makeRsvp();
        $waiting = $this->makeRsvp(['message' => 'Happy 85th birthday, sir!']);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/guests/'.$waiting->id.'/status', ['status' => 'confirmed'])->assertSessionHas('status');
        $this->assertSame(Rsvp::STATUS_CONFIRMED, $waiting->fresh()->status);
        $this->actingAs($admin)->get('/admin/messages')->assertSee('Happy 85th birthday, sir!');
    }

    public function test_email_previews_render_for_all_seven_templates(): void
    {
        $admin = $this->admin();
        foreach (array_keys(Notifier::TEMPLATES) as $t) {
            $this->actingAs($admin)->get('/admin/emails/preview/'.$t)->assertOk()->assertSee('Dr. Festus Fadeyi');
        }
        $this->actingAs($admin)->get('/admin/emails')->assertOk();
    }

    public function test_every_admin_page_renders_with_data(): void
    {
        config(['event.rsvp.capacity' => 1]);
        $this->makeRsvp(['message' => 'Congratulations', 'pledge_amount' => '20000']);
        $this->makeRsvp(['attendance' => 'yes']);
        Invitation::query()->create(['code' => 'RENDERTEST', 'name' => 'Render Invitee', 'email' => 'render@example.com']);
        Donation::query()->create(['reference' => 'X1', 'amount_kobo' => 100000, 'method' => 'bank_transfer', 'status' => 'success', 'paid_at' => now(), 'donor_name' => 'Offline Donor', 'message' => 'Well done']);
        $admin = $this->admin();

        foreach (['/admin', '/admin/guests', '/admin/guests?status=pending', '/admin/guests?status=checked_in', '/admin/invitations', '/admin/donations', '/admin/donations?status=all&from=2026-01-01&to=2026-12-31', '/admin/messages', '/admin/emails', '/admin/reports', '/admin/check-in'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        $this->actingAs($admin)->get('/admin/donations/venue-qr.png')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($admin)->get('/admin/messages')->assertSee('Congratulations')->assertSee('Well done');
    }
}
