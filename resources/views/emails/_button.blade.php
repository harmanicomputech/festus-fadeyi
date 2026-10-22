{{-- Bulletproof button. Params: $href, $label, $variant ('gold'|'navy'|'outline') --}}
@php
    $variant = $variant ?? 'gold';
    $bg = ['gold' => '#C7A45B', 'navy' => '#0B1730', 'outline' => '#FFFFFF'][$variant];
    $fg = ['gold' => '#0B1730', 'navy' => '#F8F5ED', 'outline' => '#0B1730'][$variant];
    $bd = ['gold' => '#C7A45B', 'navy' => '#0B1730', 'outline' => '#0B1730'][$variant];
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="display:inline-table;margin:4px 6px 4px 0;"><tr>
    <td align="center" bgcolor="{{ $bg }}" style="background:{{ $bg }};border:1px solid {{ $bd }};">
        <a href="{{ $href }}" target="_blank" style="display:inline-block;padding:14px 26px;font-family:'Plus Jakarta Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:12px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:{{ $fg }};text-decoration:none;">{{ $label }}</a>
    </td>
</tr></table>
