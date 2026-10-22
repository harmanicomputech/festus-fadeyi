@extends('emails.layout')
@section('preheader')Thank you for celebrating Dr. Festus Fadeyi at 85.@endsection
@section('body')
    @include('emails._heading', ['eyebrow' => 'With gratitude', 'title' => 'Thank you for <em style="color:#7D5F24;">celebrating with us</em>'])
    <p style="margin:0 0 16px;">Dear {{ $name }},</p>
    @if ($event['email']['copy']['thank_you'])
        @include('emails._copy', ['copy' => $event['email']['copy']['thank_you']])
    @else
        <p style="margin:0 0 16px;">Thank you for joining us in honouring Dr. Festus Fadeyi at 85 and for sharing in the presentation of <em>{{ $event['book_title'] }}</em>. Your presence made the occasion all the more special.</p>
    @endif
    @if ($event['email']['photos_url'])
        <p style="margin:0 0 16px;">Photographs from the celebration are now available.</p>
        @include('emails._button', ['href' => $event['email']['photos_url'], 'label' => 'View the photographs', 'variant' => 'navy'])
    @endif
    @if ($event['book']['purchase_info'])
        <p style="margin:22px 0 8px;font-family:'Plus Jakarta Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:22px;color:#0B1730;">Obtaining the book</p>
        <p style="margin:0 0 16px;">{{ $event['book']['purchase_info'] }}</p>
    @endif
    <p style="margin:22px 0 16px;">Giving remains open for those who wish to support {{ $charityLabel }}.</p>
    @include('emails._button', ['href' => $donateUrl, 'label' => 'Donate'])
@endsection
