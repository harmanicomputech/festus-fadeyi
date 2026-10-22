@extends('layouts.site', ['title' => 'Privacy notice'])

@section('content')
<section class="page grain" style="background:var(--ivory)">
    <div class="container">
        <article class="prose">
            <span class="eyebrow">Privacy notice</span>
            <h1 class="display" style="margin:1rem 0 1.5rem">How we look after <em>your information</em></h1>
            <p class="lede">This notice explains how {{ config('event.host') }} (“we”) handles personal data collected through this website for the {{ config('event.title') }}, in line with the Nigeria Data Protection Act 2023.</p>

            <h2>What we collect</h2>
            <ul>
                <li><strong>RSVP:</strong> title, name, email, phone (for WhatsApp/SMS reminders), organisation (optional), attendance, number of seats, dietary or accessibility needs (optional), pledge amount (optional) and a goodwill message (optional).</li>
                <li><strong>Donations:</strong> amount, name (unless you give anonymously), email for your receipt, optional phone and message, and your acknowledgement choices. Card and bank details are entered on Paystack’s checkout and never reach this website.</li>
                <li><strong>Check-in:</strong> the time you are welcomed at the venue.</li>
            </ul>

            <h2>Why we use it</h2>
            <p>Only to organise this event: to confirm your attendance, plan seating, catering and book copies, send event emails, welcome you at check-in, process and receipt donations, and report giving to the beneficiary charity. Your goodwill message is shared with the celebrant’s family. We rely on your consent and on the arrangement you enter into by replying or giving. We do not use your data for any other purpose, and we never sell it.</p>

            <h2>Who can see it</h2>
            <p>Authorised members of the planning committee, using a secured dashboard. Your QR code contains only a random reference, never your personal details. Donor names and amounts are shown publicly only where you have explicitly agreed.</p>

            <h2>Service providers</h2>
            <p>Our email delivery provider sends event emails on our behalf, and Paystack processes payments. Both act on our instructions and are bound to protect your data.</p>

            <h2>How long we keep it</h2>
            <p>Only as long as needed for the event and its final reporting, after which records are handed over to the host for safekeeping or deleted.</p>

            <h2>Your rights</h2>
            <p>You may ask to access, correct or delete your data, withdraw consent, or object to its use. You can update or cancel your RSVP from the link in your confirmation email, and stop reminder emails using the link at the foot of each reminder. You also have the right to complain to the Nigeria Data Protection Commission.</p>

            <h2>Contact</h2>
            <p>{{ config('event.contact.name') }}, {{ config('event.host') }} — <a class="link-gold" href="mailto:{{ config('event.contact.email') }}">{{ config('event.contact.email') }}</a>, {{ config('event.contact.phone') }}.</p>
            <p class="fine-print">This notice should be reviewed and approved by the host before launch.</p>
        </article>
    </div>
</section>
@endsection
