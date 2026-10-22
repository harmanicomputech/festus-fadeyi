@extends('emails.layout')
@section('preheader')A gentle reminder to reply by {{ $event['rsvp']['deadline_label'] }}.@endsection
@section('body')
    @include('emails._heading', ['eyebrow' => 'A gentle reminder', 'title' => 'We hope to <em style="color:#7D5F24;">welcome you</em>'])
    <p style="margin:0 0 16px;">Dear {{ $name }},</p>
    <p style="margin:0 0 16px;">We would be honoured by your presence at the Symposium in Honour of Dr. Festus Fadeyi at 85 and the presentation of <em>{{ $event['book_title'] }}</em>. We have not yet received your reply, and kindly ask that you let us know by <strong>{{ $event['rsvp']['deadline_label'] }}</strong>, so that we may finalise seating, catering and book copies.</p>
    @include('emails._details')
    @include('emails._button', ['href' => $rsvpUrl, 'label' => 'Reply now'])
    <p style="margin:22px 0 0;color:#5B6072;font-size:13px;">If you have already replied, thank you — please disregard this note.</p>
@endsection
