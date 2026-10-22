<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SharedHostingTest extends TestCase
{
    protected function tearDown(): void
    {
        @unlink(storage_path('app/installed.lock'));
        parent::tearDown();
    }

    public function test_installer_is_hidden_without_a_valid_token(): void
    {
        config(['app.install_token' => null]);
        $this->get('/install')->assertNotFound();

        config(['app.install_token' => str_repeat('a', 32)]);
        $this->get('/install')->assertNotFound();
        $this->get('/install?token=wrong')->assertNotFound();
        $this->get('/install?token='.str_repeat('a', 32))->assertOk()->assertSee('Server check');
    }

    public function test_installer_creates_admin_then_locks_itself(): void
    {
        $token = str_repeat('b', 32);
        config(['app.install_token' => $token]);

        $this->post('/install/finish', ['token' => $token])->assertSessionHas('error');
        $this->post('/install/migrate', ['token' => $token])->assertSessionHas('status');
        $this->post('/install/admin', ['token' => $token, 'name' => 'Fisayo', 'email' => 'Fisayo@Example.com', 'password' => 'Strong-Password-1', 'password_confirmation' => 'Strong-Password-1'])
            ->assertSessionHasNoErrors();
        $this->assertSame('admin', User::query()->where('email', 'fisayo@example.com')->value('role'));

        $this->post('/install/finish', ['token' => $token])->assertRedirect(route('admin.login'));
        $this->assertFileExists(storage_path('app/installed.lock'));
        $this->get('/install?token='.$token)->assertNotFound();
        $this->post('/install/admin', ['token' => $token, 'name' => 'X', 'email' => 'x@example.com', 'password' => 'Strong-Password-1', 'password_confirmation' => 'Strong-Password-1'])->assertNotFound();
    }

    public function test_cron_url_requires_token_and_sends_due_campaigns(): void
    {
        config(['app.cron_token' => str_repeat('c', 32)]);
        $this->get('/cron/wrong-token-wrong-token')->assertNotFound();

        $this->makeRsvp(['email' => 'cron@example.com']);
        $this->travelTo(Carbon::parse('2026-12-07 10:10', 'Africa/Lagos'));
        $this->get('/cron/'.str_repeat('c', 32))->assertOk()->assertSee('event_reminder: 1 sent');
        $this->get('/cron/'.str_repeat('c', 32))->assertOk();
        $this->assertSame(1, EmailLog::query()->where('template', 'event_reminder')->count());
    }

    public function test_admin_can_manage_users_but_not_lock_themselves_out(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->post('/admin/users', ['name' => 'Desk', 'email' => 'desk@example.com', 'role' => 'checkin', 'password' => 'Strong-Password-1', 'password_confirmation' => 'Strong-Password-1'])->assertSessionHasNoErrors();
        $desk = User::query()->where('email', 'desk@example.com')->firstOrFail();
        $this->assertSame('checkin', $desk->role);

        $this->actingAs($admin)->put('/admin/users/'.$admin->id, ['role' => 'checkin'])->assertSessionHas('error');
        $this->actingAs($admin)->delete('/admin/users/'.$admin->id)->assertSessionHas('error');
        $this->actingAs($admin)->delete('/admin/users/'.$desk->id);
        $this->assertNull(User::query()->find($desk->id));
        $this->actingAs($this->admin('checkin'))->get('/admin/users')->assertRedirect(route('admin.checkin'));
    }

    public function test_site_shows_holding_page_before_installation(): void
    {
        config(['app.install_token' => str_repeat('d', 32)]);
        // Simulate the database tables not existing yet.
        Route::get('/__missing-tables', fn () => throw new QueryException('mysql', 'select * from donations', [], new Exception('Base table not found')));

        $this->get('/__missing-tables')->assertStatus(503)->assertSee('being prepared');

        // Once installed, real database errors are not hidden behind the holding page.
        file_put_contents(storage_path('app/installed.lock'), 'test');
        $this->get('/__missing-tables')->assertStatus(500);
    }
}
