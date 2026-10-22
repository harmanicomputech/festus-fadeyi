<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Rsvp;
use App\Services\QrCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckInController extends Controller
{
    public function index()
    {
        return view('admin.checkin', [
            'checkedIn' => Rsvp::query()->whereNotNull('checked_in_at')->count(),
            'confirmed' => Rsvp::query()->confirmed()->count(),
        ]);
    }

    /** Validate a scanned or typed code against the stored guest record. */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:200']]);
        $token = QrCodes::tokenFromScan($request->input('code'));
        $rsvp = $token ? Rsvp::query()->with('checkedInBy')->where('qr_token', $token)->first() : null;

        if (! $rsvp) {
            CheckIn::query()->create(['user_id' => $request->user()->id, 'result' => 'invalid']);

            return response()->json(['result' => 'invalid', 'message' => 'This code is not recognised. Please check the guest’s name using search.']);
        }

        return response()->json($this->describe($rsvp));
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json(['guests' => []]);
        }
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], mb_strtolower($q)).'%';
        $digits = preg_replace('/\D/', '', $q);

        $guests = Rsvp::query()->with('checkedInBy')
            ->whereIn('status', [Rsvp::STATUS_CONFIRMED, Rsvp::STATUS_WAITLISTED, Rsvp::STATUS_MAYBE])
            ->where(fn ($w) => $w->whereRaw('lower(full_name) like ?', [$like])->orWhereRaw('lower(email) like ?', [$like])
                ->when(strlen($digits) >= 4, fn ($w) => $w->orWhereRaw("replace(replace(phone, ' ', ''), '-', '') like ?", ['%'.$digits.'%'])))
            ->orderBy('full_name')->limit(10)->get();

        return response()->json(['guests' => $guests->map(fn ($r) => $this->describe($r))->values()]);
    }

    /** Record arrival once. A repeat scan is reported, never counted as a new arrival. */
    public function admit(Request $request, Rsvp $rsvp): JsonResponse
    {
        $result = DB::transaction(function () use ($request, $rsvp) {
            $locked = Rsvp::query()->whereKey($rsvp->id)->lockForUpdate()->first();

            if (! $locked->isConfirmed()) {
                CheckIn::query()->create(['rsvp_id' => $locked->id, 'user_id' => $request->user()->id, 'result' => 'not_confirmed']);

                return 'not_confirmed';
            }
            if ($locked->checked_in_at) {
                CheckIn::query()->create(['rsvp_id' => $locked->id, 'user_id' => $request->user()->id, 'result' => 'repeat']);

                return 'repeat';
            }

            $locked->forceFill(['checked_in_at' => now(), 'checked_in_by' => $request->user()->id])->save();
            CheckIn::query()->create(['rsvp_id' => $locked->id, 'user_id' => $request->user()->id, 'result' => 'admitted']);

            return 'admitted';
        });

        $rsvp->refresh()->load('checkedInBy');

        return response()->json(['admit' => $result] + $this->describe($rsvp) + [
            'totals' => ['checked_in' => Rsvp::query()->whereNotNull('checked_in_at')->count(), 'confirmed' => Rsvp::query()->confirmed()->count()],
        ]);
    }

    private function describe(Rsvp $rsvp): array
    {
        $result = match (true) {
            ! $rsvp->isConfirmed() => 'not_confirmed',
            (bool) $rsvp->checked_in_at => 'already',
            default => 'valid',
        };

        return [
            'result' => $result,
            'guest' => [
                'id' => $rsvp->id,
                'name' => $rsvp->salutation(),
                'organisation' => $rsvp->organisation,
                'seats' => $rsvp->party_size,
                'dietary' => $rsvp->dietary_needs,
                'status' => $rsvp->statusLabel(),
                'checked_in_at' => $rsvp->checked_in_at?->timezone(config('event.timezone'))->format('g:i A, j M'),
                'checked_in_by' => $rsvp->checkedInBy?->name,
                'admit_url' => route('admin.checkin.admit', $rsvp),
            ],
        ];
    }
}
