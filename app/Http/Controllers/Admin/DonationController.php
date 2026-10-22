<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Pledge;
use App\Services\DonationService;
use App\Services\Exporter;
use App\Services\Notifier;
use App\Services\QrCodes;
use App\Support\EventInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Throwable;

class DonationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $base = $this->query($filters);

        $byMethod = (clone $base)->reorder()->successful()->selectRaw('method, count(*) as n, sum(amount_kobo) as total')->groupBy('method')->get();
        $byDay = (clone $base)->reorder()->successful()->get(['paid_at', 'amount_kobo'])
            ->groupBy(fn ($d) => $d->paid_at?->timezone(EventInfo::tz())->format('Y-m-d') ?? 'Unknown')
            ->map(fn ($g) => ['n' => $g->count(), 'total' => $g->sum('amount_kobo')])->sortKeysDesc();

        return view('admin.donations', [
            'donations' => $base->with('recorder')->paginate(50)->withQueryString(),
            'filters' => $filters,
            'byMethod' => $byMethod,
            'byDay' => $byDay,
            'verifiedTotal' => (int) Donation::query()->successful()->sum('amount_kobo'),
            'verifiedCount' => Donation::query()->successful()->count(),
            'pledges' => Pledge::query()->with(['rsvp', 'donation'])->latest()->get(),
            'methods' => Donation::METHOD_LABELS,
            'openPledges' => Pledge::query()->where('status', 'open')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, DonationService $donations)
    {
        $request->merge(['amount' => preg_replace('/[^0-9.]/', '', (string) $request->input('amount'))]);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:'.config('event.donations.max_amount')],
            'method' => ['required', Rule::in(['bank_transfer'])],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
            'reference' => ['nullable', 'string', 'max:64', 'unique:donations,reference'],
            'anonymous' => ['nullable', 'boolean'],
            'donor_name' => ['exclude_if:anonymous,1', 'required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'message' => ['nullable', 'string', 'max:1000'],
            'acknowledge_publicly' => ['nullable', 'boolean'],
            'show_amount_publicly' => ['nullable', 'boolean'],
            'pledge_id' => ['nullable', 'integer', Rule::exists('pledges', 'id')->where('status', 'open')],
            'admin_note' => ['nullable', 'string', 'max:500'],
            'send_receipt' => ['nullable', 'boolean'],
            'confirm_verified' => ['accepted'],
        ], ['confirm_verified.accepted' => 'Please confirm the funds have been seen in the beneficiary account.']);

        $donation = $donations->recordManual($data + ['reference' => $data['reference'] ?? null], $request->user());

        return back()->with('status', 'Recorded '.EventInfo::naira($donation->amount_kobo).' ('.$donation->methodLabel().'), reference '.$donation->reference.'.');
    }

    public function verify(Donation $donation, DonationService $donations)
    {
        try {
            $donation = $donations->verifyWithGateway($donation);
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Could not reach Paystack: '.$e->getMessage());
        }

        return back()->with('status', 'Paystack reports this payment as: '.($donation->gateway_status ?? 'unknown').'. Recorded status: '.$donation->status.'.');
    }

    public function receipt(Donation $donation, Notifier $notifier)
    {
        abort_unless($donation->status === 'success' && $donation->email, 422, 'Receipts are only sent for verified gifts with an email address.');
        $ok = $notifier->donationReceipt($donation, resend: true);

        return back()->with($ok ? 'status' : 'error', $ok ? 'Receipt sent to '.$donation->email.'.' : 'The receipt could not be sent; see the email log.');
    }

    public function export(Request $request, string $format, Exporter $exporter)
    {
        $filters = $this->filters($request);

        return $exporter->download('donations', $format, ['Donations' => [self::DONATION_HEADERS, self::donationRows($this->query($filters)->with('recorder'))]]);
    }

    public function exportPledges(string $format, Exporter $exporter)
    {
        return $exporter->download('pledges', $format, ['Pledges' => [self::PLEDGE_HEADERS, self::pledgeRows()]]);
    }

    /** Printable donation QR for table cards and the programme. */
    public function venueQr(QrCodes $qr)
    {
        return response($qr->urlPng(route('donate.show')), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="donate-qr-code.png"',
        ]);
    }

    public const DONATION_HEADERS = ['Reference', 'Status', 'Amount (NGN)', 'Method', 'Channel', 'Donor', 'Anonymous', 'Email', 'Phone', 'Public acknowledgement', 'Show amount publicly', 'Message', 'Pledge ID', 'Paid at', 'Verified at', 'Recorded by', 'Receipt sent', 'Note', 'Created'];

    public const PLEDGE_HEADERS = ['Pledge ID', 'Name', 'Email', 'Amount (NGN)', 'Status', 'RSVP status', 'Fulfilled by donation', 'Created'];

    public static function donationRows(Builder $query): \Generator
    {
        foreach ($query->cursor() as $d) {
            yield [
                $d->reference, $d->status, $d->amount_kobo / 100, $d->methodLabel(), $d->channel, $d->donor_name ?? '', $d->is_anonymous ? 'Yes' : 'No',
                $d->email, $d->phone, $d->acknowledge_publicly ? 'Yes' : 'No', $d->show_amount_publicly ? 'Yes' : 'No', $d->message, $d->pledge_id,
                $d->paid_at?->timezone(EventInfo::tz()), $d->verified_at?->timezone(EventInfo::tz()), $d->recorder?->email, $d->receipt_sent_at ? 'Yes' : 'No', $d->admin_note, $d->created_at,
            ];
        }
    }

    public static function pledgeRows(): \Generator
    {
        foreach (Pledge::query()->with(['rsvp', 'donation'])->orderBy('name')->cursor() as $p) {
            yield [$p->id, $p->name, $p->email, $p->amount_kobo / 100, $p->status, $p->rsvp?->statusLabel(), $p->donation?->reference, $p->created_at];
        }
    }

    private function filters(Request $request): array
    {
        return [
            'status' => in_array($request->query('status'), ['all', 'success', 'pending', 'failed', 'abandoned', 'review'], true) ? $request->query('status') : 'success',
            'method' => array_key_exists((string) $request->query('method'), Donation::METHOD_LABELS) ? $request->query('method') : null,
            'from' => $this->date($request->query('from')),
            'to' => $this->date($request->query('to')),
            'q' => trim((string) $request->query('q')),
        ];
    }

    private function date(mixed $v): ?string
    {
        try {
            return $v ? Carbon::parse($v)->format('Y-m-d') : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function query(array $f): Builder
    {
        $tz = EventInfo::tz();

        return Donation::query()
            ->when($f['status'] !== 'all', fn ($b) => $b->where('status', $f['status']))
            ->when($f['method'], fn ($b) => $b->where('method', $f['method']))
            ->when($f['from'], fn ($b) => $b->where('created_at', '>=', Carbon::parse($f['from'], $tz)->startOfDay()))
            ->when($f['to'], fn ($b) => $b->where('created_at', '<=', Carbon::parse($f['to'], $tz)->endOfDay()))
            ->when($f['q'] !== '', function ($b) use ($f) {
                $like = '%'.mb_strtolower($f['q']).'%';
                $b->where(fn ($w) => $w->whereRaw('lower(coalesce(donor_name, \'\')) like ?', [$like])
                    ->orWhereRaw('lower(coalesce(email, \'\')) like ?', [$like])
                    ->orWhereRaw('lower(reference) like ?', [$like]));
            })
            ->latest();
    }
}
