<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Invitation;
use App\Models\Pledge;
use App\Models\Rsvp;
use App\Services\Exporter;
use App\Support\EventInfo;
use Illuminate\Http\Request;

/** Final guest and donation report (printable, and as a multi-sheet Excel workbook). */
class ReportController extends Controller
{
    public function index(Request $request, Exporter $exporter)
    {
        $summary = $this->summary();

        if ($request->query('download') === 'xlsx') {
            $summaryRows = collect($summary)->map(fn ($v, $k) => [$k, $v])->values();
            $guestRows = function () {
                foreach (Rsvp::query()->with(['pledge', 'invitation'])->orderBy('status')->orderBy('full_name')->cursor() as $r) {
                    yield [$r->statusLabel(), $r->salutation(), $r->email, $r->phone, $r->organisation, $r->attendance === 'no' ? 0 : $r->party_size, $r->dietary_needs, $r->checked_in_at, $r->pledge ? $r->pledge->amount_kobo / 100 : null, $r->message];
                }
            };

            return $exporter->download('final-report', 'xlsx', [
                'Summary' => [['Measure', 'Value'], $summaryRows],
                'Guests' => [['Status', 'Guest', 'Email', 'Phone', 'Organisation', 'Seats', 'Dietary / accessibility', 'Checked in at', 'Pledge (NGN)', 'Message'], $guestRows()],
                'Donations' => [DonationController::DONATION_HEADERS, DonationController::donationRows(Donation::query()->with('recorder')->orderBy('paid_at'))],
                'Pledges' => [DonationController::PLEDGE_HEADERS, DonationController::pledgeRows()],
            ]);
        }

        return view('admin.reports', [
            'summary' => $summary,
            'byMethod' => Donation::query()->successful()->selectRaw('method, count(*) as n, sum(amount_kobo) as total')->groupBy('method')->get(),
        ]);
    }

    private function summary(): array
    {
        $n = fn ($status) => Rsvp::query()->where('status', $status)->count();
        $seats = fn ($status) => (int) Rsvp::query()->where('status', $status)->sum('party_size');
        $verified = (int) Donation::query()->successful()->sum('amount_kobo');
        $pledged = (int) Pledge::query()->where('status', '!=', 'cancelled')->sum('amount_kobo');
        $fulfilled = (int) Pledge::query()->where('status', 'fulfilled')->sum('amount_kobo');

        return [
            'Invitations issued' => Invitation::query()->count(),
            'Replies received' => Rsvp::query()->count(),
            'Accepted (replies / seats)' => $n('confirmed').' / '.$seats('confirmed'),
            'Maybe' => $n('maybe'),
            'Declined' => $n('declined'),
            'Waitlisted (replies / seats)' => $n('waitlisted').' / '.$seats('waitlisted'),
            'No reply yet' => Invitation::query()->whereDoesntHave('rsvp')->whereNotIn('email', Rsvp::query()->select('email'))->count(),
            'Seat capacity' => EventInfo::capacity(),
            'Checked in (guests / seats)' => Rsvp::query()->whereNotNull('checked_in_at')->count().' / '.(int) Rsvp::query()->whereNotNull('checked_in_at')->sum('party_size'),
            'Verified donations (count)' => Donation::query()->successful()->count(),
            'Verified donations (NGN)' => number_format($verified / 100, 2),
            'Pledged (NGN, excl. cancelled)' => number_format($pledged / 100, 2),
            'Pledges fulfilled (NGN)' => number_format($fulfilled / 100, 2),
            'Pledges outstanding (NGN)' => number_format(Pledge::query()->where('status', 'open')->sum('amount_kobo') / 100, 2),
            'Beneficiary' => EventInfo::charityLabel(),
            'Report generated' => now(EventInfo::tz())->format('j F Y, g:i A').' (Lagos)',
        ];
    }
}
