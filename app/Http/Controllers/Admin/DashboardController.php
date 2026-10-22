<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\EmailLog;
use App\Models\Invitation;
use App\Models\Pledge;
use App\Models\Rsvp;
use App\Models\Setting;
use App\Services\RsvpService;
use App\Support\EventInfo;

class DashboardController extends Controller
{
    public function index(RsvpService $rsvps)
    {
        $byStatus = Rsvp::query()->selectRaw('status, count(*) as n, sum(party_size) as seats')->groupBy('status')->get()->keyBy('status');

        return view('admin.dashboard', [
            'byStatus' => $byStatus,
            'seatsUsed' => $rsvps->seatsUsed(),
            'capacity' => EventInfo::capacity(),
            'pendingInvitations' => Invitation::query()->whereDoesntHave('rsvp')->whereNotIn('email', Rsvp::query()->select('email'))->count(),
            'invitations' => Invitation::query()->count(),
            'checkedIn' => Rsvp::query()->whereNotNull('checked_in_at')->count(),
            'checkedInSeats' => (int) Rsvp::query()->whereNotNull('checked_in_at')->sum('party_size'),
            'donationTotal' => (int) Donation::query()->successful()->sum('amount_kobo'),
            'donationCount' => Donation::query()->successful()->count(),
            'pledgeOpen' => (int) Pledge::query()->where('status', 'open')->sum('amount_kobo'),
            'pledgeOpenCount' => Pledge::query()->where('status', 'open')->count(),
            'emailFailures' => EmailLog::query()->where('status', 'failed')->count(),
            'recentRsvps' => Rsvp::query()->latest()->limit(8)->get(),
            'recentDonations' => Donation::query()->successful()->latest('paid_at')->limit(6)->get(),
            'inviteOnly' => Setting::inviteOnly(),
            'rsvpOpen' => EventInfo::rsvpOpen(),
            'setup' => [
                'Email delivery' => ! in_array(config('mail.default'), ['log', 'array'], true),
                'Payment gateway (Paystack)' => EventInfo::onlinePaymentsConfigured(),
                'Charity name' => EventInfo::charityName() !== null,
                'Charity bank details' => EventInfo::bankDetailsConfigured(),
                'Welcome message' => filled(config('event.welcome.message')),
                'Book author & synopsis' => filled(config('event.book.author')) && filled(config('event.book.synopsis')),
                'Book cover' => filled(config('event.images.book_cover')),
                'Programme timings' => collect(config('event.programme'))->every(fn ($i) => filled($i['time'])),
                'Parking & arrival notes' => filled(config('event.venue.parking_notes')) && filled(config('event.venue.arrival_notes')),
            ],
        ]);
    }
}
