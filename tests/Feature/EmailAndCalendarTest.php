<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Models\Invitation;
use App\Services\Campaigns;
use App\Support\EventInfo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailAndCalendarTest extends TestCase
{
    public function test_emails_use_the_branded_sender(): void
    {
        config(['mail.from.address' => 'Invitation@Dr.FFat85.com', 'mail.from.name' => 'Dr. FF@85 Planning Committee']);
        Mail::fake();
        $this->makeRsvp(['email' => 'brand@example.com']);

        Mail::assertSent(\App\Mail\BrandedMail::class, function ($mail) {
            $env = $mail->envelope();

            return $mail->hasTo('brand@example.com')
                && $env->from->address === 'Invitation@Dr.FFat85.com'
                && $env->from->name === 'Dr. FF@85 Planning Committee'
                && $env->replyTo[0]->address === config('event.email.reply_to');
        });
    }

    public function test_rsvp_reminder_goes_only_to_invitees_who_have_not_replied(): void
    {
        $replied = Invitation::query()->create(['code' => 'AAAAAAAAA2', 'name' => 'Replied', 'email' => 'replied@example.com', 'invitation_sent_at' => now()]);
        $this->makeRsvp(['email' => 'replied@example.com', 'attendance' => 'no']);
        Invitation::query()->create(['code' => 'BBBBBBBBB2', 'name' => 'Silent', 'email' => 'silent@example.com', 'invitation_sent_at' => now()]);
        Invitation::query()->create(['code' => 'CCCCCCCCC2', 'name' => 'Opted out', 'email' => 'optout@example.com', 'invitation_sent_at' => now(), 'email_opt_out' => true]);
        Invitation::query()->create(['code' => 'DDDDDDDDD2', 'name' => 'Never invited', 'email' => 'never@example.com']);

        $recipients = app(Campaigns::class)->recipients('rsvp_reminder')->pluck('email')->all();
        $this->assertSame(['silent@example.com'], $recipients);
    }

    public function test_event_reminders_exclude_declined_and_never_double_send(): void
    {
        $this->makeRsvp(['email' => 'yes@example.com']);
        $this->makeRsvp(['email' => 'no@example.com', 'attendance' => 'no']);
        $this->makeRsvp(['email' => 'maybe@example.com', 'attendance' => 'maybe']);

        $first = app(Campaigns::class)->run('event_reminder');
        $second = app(Campaigns::class)->run('event_reminder');
        $this->assertSame(1, $first['sent']);
        $this->assertSame(0, $second['sent']);
        $this->assertSame(1, $second['skipped']);
        $this->assertSame(['yes@example.com'], EmailLog::query()->where('template', 'event_reminder')->pluck('recipient')->all());
    }

    public function test_scheduler_sends_each_campaign_inside_its_window_only(): void
    {
        $this->makeRsvp(['email' => 'g@example.com']);
        $this->artisan('ff85:send-scheduled')->assertSuccessful();
        $this->assertSame(0, EmailLog::query()->where('template', 'event_reminder')->count());

        $this->travelTo(Carbon::parse('2026-12-07 10:05', 'Africa/Lagos'));
        $this->artisan('ff85:send-scheduled')->assertSuccessful();
        $this->assertSame(1, EmailLog::query()->where('template', 'event_reminder')->count());
        $this->assertSame(0, EmailLog::query()->where('template', 'day_of')->count());

        $this->travelTo(Carbon::parse('2026-12-10 07:00', 'Africa/Lagos'));
        $this->artisan('ff85:send-scheduled')->assertSuccessful();
        $this->assertSame(1, EmailLog::query()->where('template', 'day_of')->count());
        $this->assertSame(1, EmailLog::query()->where('template', 'event_reminder')->count());
    }

    public function test_unsubscribe_link_must_be_signed_and_stops_reminders(): void
    {
        $r = $this->makeRsvp(['email' => 'unsub@example.com']);
        $this->get('/email/unsubscribe/rsvp/'.$r->id)->assertForbidden();
        $this->get(URL::signedRoute('email.unsubscribe', ['type' => 'rsvp', 'id' => $r->id]))->assertOk();
        $this->assertTrue($r->fresh()->email_opt_out);
        $this->assertSame(0, app(Campaigns::class)->recipients('event_reminder')->count());
    }

    public function test_calendar_links_use_correct_lagos_times(): void
    {
        // 11:00–13:00 West Africa Time (UTC+1) = 10:00–12:00 UTC
        $ics = $this->get('/calendar/event.ics')->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8')->getContent();
        $this->assertStringContainsString("DTSTART:20261210T100000Z\r\n", $ics);
        $this->assertStringContainsString("DTEND:20261210T120000Z\r\n", $ics);
        $this->assertStringContainsString('LOCATION:Sky Restaurant\, Eko Hotels\, Victoria Island\, Lagos', str_replace("\r\n ", '', $ics));
        foreach (explode("\r\n", $ics) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
        }

        $this->assertStringContainsString('dates=20261210T100000Z%2F20261210T120000Z', EventInfo::googleCalendarUrl());
        $this->assertStringContainsString('startdt=2026-12-10T10%3A00%3A00Z', EventInfo::outlookCalendarUrl());
    }
}
