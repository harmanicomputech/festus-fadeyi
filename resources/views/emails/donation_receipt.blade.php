@extends('emails.layout')
@section('preheader')Receipt {{ $donation->reference }} — thank you for your gift of {{ \App\Support\EventInfo::naira($donation->amount_kobo) }}.@endsection
@section('body')
    @include('emails._heading', ['eyebrow' => 'Donation receipt', 'title' => 'With heartfelt <em style="color:#7D5F24;">thanks</em>'])
    <p style="margin:0 0 16px;">{{ $name ? 'Dear '.$name.',' : 'Dear friend,' }}</p>
    <p style="margin:0 0 16px;">Thank you for your generous gift in honour of Dr. Festus Fadeyi’s 85th birthday. Your donation has been received and verified.</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0 24px;border-top:1px solid #C7A45B;border-bottom:1px solid #C7A45B;font-size:14px;">
        @foreach ([
            'Amount' => \App\Support\EventInfo::naira($donation->amount_kobo),
            'Date' => ($donation->paid_at ?? $donation->created_at)->timezone(config('event.timezone'))->format('j F Y, g:i A'),
            'Reference' => $donation->reference,
            'Beneficiary' => $charityName ?? 'The designated charity',
            'Method' => $donation->methodLabel(),
            'Donor' => $donation->displayName(),
        ] as $label => $value)
            <tr>
                <td style="padding:11px 0;border-bottom:1px solid #F1EBDD;color:#5B6072;width:38%;">{{ $label }}</td>
                <td style="padding:11px 0;border-bottom:1px solid #F1EBDD;color:#0B1730;font-weight:600;">{{ $value }}</td>
            </tr>
        @endforeach
    </table>
    <p style="margin:0 0 16px;">Your generosity will make a meaningful difference. Please keep this email as your receipt.</p>
    <p style="margin:0;color:#5B6072;font-size:13px;">Questions about your gift? Contact {{ $event['contact']['name'] }} at <a href="mailto:{{ $event['contact']['email'] }}" style="color:#7D5F24;">{{ $event['contact']['email'] }}</a>, quoting your reference.</p>
@endsection
