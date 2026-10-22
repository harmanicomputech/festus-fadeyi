{{--
    Branded email layout: navy, gold, ivory. Table-based with inline styles
    for Gmail, Outlook and Apple Mail. Web fonts load where supported and
    fall back to Helvetica / Arial elsewhere.
--}}
@php
    $serif = "'Plus Jakarta Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif";
    $sans = $serif;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light only">
    <title>{{ $subjectLine }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; padding: 0; background: #F8F5ED; }
        a { color: #7D5F24; }
        @media (max-width: 620px) {
            .container { width: 100% !important; }
            .px { padding-left: 24px !important; padding-right: 24px !important; }
            .h1 { font-size: 30px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#F8F5ED;">
    {{-- Preheader --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">@yield('preheader')&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F8F5ED;">
        <tr>
            <td align="center" style="padding:28px 12px;">
                <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background:#FFFFFF;border:1px solid #E8DFCB;">
                    {{-- Header --}}
                    <tr>
                        <td align="center" style="background:#0B1730;padding:36px 32px 30px;border-bottom:3px solid #C7A45B;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                                <td align="center" style="width:46px;height:46px;border:1px solid #C7A45B;font-family:{!! $serif !!};font-size:20px;color:#C7A45B;letter-spacing:1px;">85</td>
                            </tr></table>
                            <p style="margin:16px 0 4px;font-family:{!! $sans !!};font-size:10px;letter-spacing:3px;text-transform:uppercase;color:#C7A45B;">{{ $event['subtitle'] }}</p>
                            <p style="margin:0;font-family:{!! $serif !!};font-size:24px;line-height:1.25;font-weight:300;letter-spacing:-0.5px;color:#F8F5ED;">Dr. Festus Fadeyi <span style="color:#C7A45B;font-weight:700;">at 85</span></p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td class="px" style="padding:40px 48px 12px;font-family:{!! $sans !!};font-size:15px;line-height:1.7;color:#202531;">
                            @yield('body')
                        </td>
                    </tr>

                    {{-- Ornament --}}
                    <tr>
                        <td align="center" style="padding:12px 48px 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                                <td style="width:80px;border-top:1px solid #C7A45B;font-size:0;line-height:0;">&nbsp;</td>
                                <td style="padding:0 10px;color:#C7A45B;font-size:12px;line-height:1;">&#9670;</td>
                                <td style="width:80px;border-top:1px solid #C7A45B;font-size:0;line-height:0;">&nbsp;</td>
                            </tr></table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td class="px" align="center" style="background:#070F21;padding:28px 40px;font-family:{!! $sans !!};font-size:12px;line-height:1.7;color:#B9BFCC;">
                            <p style="margin:0 0 6px;color:#F8F5ED;font-family:{!! $serif !!};font-size:16px;">{{ $event['title'] }}</p>
                            <p style="margin:0 0 10px;">{{ $event['date_label'] }} &middot; {{ $event['time_label'] }}<br>{{ $event['venue']['full'] }}</p>
                            <p style="margin:0 0 10px;">Hosted by {{ $event['host'] }}<br>
                                Enquiries: {{ $event['contact']['name'] }} &middot; <a href="mailto:{{ $event['contact']['email'] }}" style="color:#C7A45B;">{{ $event['contact']['email'] }}</a> &middot; {{ $event['contact']['phone'] }}</p>
                            <p style="margin:0;color:#8E95A6;font-size:11px;">You are receiving this email in connection with this event only. <a href="{{ route('privacy') }}" style="color:#8E95A6;">Privacy notice</a>@isset($unsubscribeUrl) &middot; <a href="{{ $unsubscribeUrl }}" style="color:#8E95A6;">Stop reminder emails</a>@endisset</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
