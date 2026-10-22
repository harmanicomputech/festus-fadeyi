<?php

/*
|--------------------------------------------------------------------------
| Dr. Festus Fadeyi @ 85 — event configuration and client content
|--------------------------------------------------------------------------
|
| Every client-supplied item lives here. A value of null means "awaiting
| the client": the website shows a clearly labelled placeholder rather
| than invented content. Replace a null with the approved text (or set
| the matching .env variable) and the page updates. No redesign needed.
|
| Images go in public/images/... and are referenced by their public path.
|
*/

return [

    // ---------------------------------------------------------------
    // Verified event details (from the approved brief)
    // ---------------------------------------------------------------
    'title' => 'Symposium in Honour of Dr. Festus Fadeyi at 85',
    'short_title' => 'Dr. Festus Fadeyi @ 85',
    'subtitle' => 'A Symposium in Honour & Book Presentation',
    'book_title' => 'Forged in His Fire',
    'celebrant' => 'Dr. Festus Fadeyi',
    'host' => 'Caritas Communications Limited',
    'dress_code' => 'Formal and Semi-formal',

    'timezone' => 'Africa/Lagos',
    'starts_at' => '2026-12-10 11:00:00',
    'ends_at' => '2026-12-10 13:00:00',
    'date_label' => 'Thursday, 10 December 2026',
    'time_label' => '11:00 AM – 1:00 PM',

    'venue' => [
        'name' => 'Sky Restaurant, Eko Hotels',
        'area' => 'Victoria Island, Lagos',
        'full' => 'Sky Restaurant, Eko Hotels, Victoria Island, Lagos',
        // Any Google Maps "Embed a map" src URL. The default needs no API key.
        // Replace with the verified pin once confirmed with the venue.
        'map_embed_url' => env('VENUE_MAP_EMBED_URL') ?: 'https://www.google.com/maps?q=Eko+Hotels+and+Suites,+Victoria+Island,+Lagos&output=embed',
        'map_link' => env('VENUE_MAP_LINK') ?: 'https://www.google.com/maps/search/?api=1&query=Eko+Hotels+and+Suites+Victoria+Island+Lagos',
        'parking_notes' => null,  // CLIENT TO SUPPLY
        'arrival_notes' => null,  // CLIENT TO SUPPLY
    ],

    'contact' => [
        'name' => 'Fisayo Ayodele',
        'email' => 'fisayo@caritaspr.com',
        'phone' => '09063684621',
        'phone_intl' => '+2349063684621',
    ],

    // ---------------------------------------------------------------
    // RSVP rules
    // ---------------------------------------------------------------
    'rsvp' => [
        'capacity' => (int) (env('EVENT_CAPACITY') ?: 150),
        // Seats one RSVP may hold, including the guest themself.
        'max_party_size' => (int) (env('RSVP_MAX_PARTY_SIZE') ?: 2),
        // New RSVPs close at the end of this day (Lagos time).
        'deadline' => env('RSVP_DEADLINE') ?: '2026-12-04 23:59:59',
        'deadline_label' => 'Friday, 4 December 2026',
        'titles' => ['Chief', 'Dr', 'Prof', 'HRH', 'Hon', 'Mr', 'Mrs', 'Ms', 'Other'],
    ],

    // ---------------------------------------------------------------
    // Imagery
    // ---------------------------------------------------------------
    'images' => [
        'portrait' => '/images/celebrant/portrait-01.jpg',
        'portrait_webp' => '/images/celebrant/portrait-01.webp',
        'portrait_alt' => 'Portrait of Dr. Festus Fadeyi',
        'book_cover' => null,     // CLIENT TO SUPPLY, e.g. '/images/book/forged-in-his-fire.jpg'
        'og_image' => '/images/celebrant/portrait-01.jpg',
    ],

    // ---------------------------------------------------------------
    // Section 2 — Welcome note (CLIENT TO SUPPLY)
    // ---------------------------------------------------------------
    'welcome' => [
        // DRAFT for the host's approval. Paragraphs separated by blank lines.
        'message' => 'It is our great honour to welcome you to this symposium celebrating the life and work of Dr. Festus Fadeyi as he marks his 85th birthday. Join us for a morning of reflection, the presentation of Forged in His Fire, and giving in his honour.',
        'signed_by' => 'Caritas Communications Limited',
    ],

    // ---------------------------------------------------------------
    // Section 3 — Approved biography (verbatim from the client)
    // ---------------------------------------------------------------
    'biography' => "Dr. Festus Fadeyi is a pioneering oil executive who spearheaded indigenous participation in Nigerian marginal field development as Managing Director of a local oil company in joint venture with the Federal Government. He obtained his Bachelor's degree in Accounting from California State University, Pomona in 1970, followed by an MBA in Behavioural Sciences from Pepperdine University and CPA certification in 1971. After completing his Ph.D in Business Administration from the University of Southern California in 1973, Dr. Fadeyi achieved success in U.S. manufacturing and retail before returning to Nigeria. He joined the oil sector in 1974 with Pan Ocean Oil Corporation, eventually assuming leadership of the company. As a Pathfinder Award recipient in the Nigerian Oil and Gas Hall of Fame, Dr. Fadeyi has successfully led aggressive exploration and production campaigns across the Niger Delta.",

    // Highlights restated only from the approved biography above.
    'milestones' => [
        ['year' => '1970', 'text' => "Bachelor's degree in Accounting, California State University, Pomona"],
        ['year' => '1971', 'text' => 'MBA in Behavioural Sciences, Pepperdine University, and CPA certification'],
        ['year' => '1973', 'text' => 'Ph.D in Business Administration, University of Southern California'],
        ['year' => '1974', 'text' => 'Joined the oil sector with Pan Ocean Oil Corporation, later assuming its leadership'],
        ['year' => null, 'text' => 'Pathfinder Award recipient, Nigerian Oil and Gas Hall of Fame'],
    ],

    // ---------------------------------------------------------------
    // Section 4 — The book
    // ---------------------------------------------------------------
    'book' => [
        'author' => 'Dr. Festus Fadeyi',
        'synopsis' => null,       // CLIENT TO SUPPLY (two to three lines)
        // Optional: only shown once the client confirms book sales also go to charity.
        'purchase_supports_charity' => false,
        'purchase_info' => null,  // Where to obtain the book (used in thank-you email).
    ],

    // ---------------------------------------------------------------
    // Section 5 — Charity (CLIENT TO SUPPLY)
    // ---------------------------------------------------------------
    'charity' => [
        'name' => env('CHARITY_NAME'),
        'logo' => null,           // e.g. '/images/charity/logo.png'
        'cause' => null,          // The designated charitable cause.
        'description' => null,
        'fund_use' => null,       // How the proceeds will be used.
        'bank' => [
            // Supplied by the client. .env values (if set) take precedence.
            'bank_name' => env('CHARITY_BANK_NAME') ?: 'Zenith Bank',
            'account_name' => env('CHARITY_ACCOUNT_NAME') ?: 'DR FESTUS FADEYI SYMPOSIUM',
            'account_number' => env('CHARITY_ACCOUNT_NUMBER') ?: '1229809568',
        ],
    ],

    'donations' => [
        'currency' => 'NGN',
        'min_amount' => (int) (env('DONATION_MIN_NAIRA') ?: 1000),
        'max_amount' => (int) (env('DONATION_MAX_NAIRA') ?: 50000000),
        'suggested' => [25000, 50000, 100000, 250000, 500000, 1000000],
        // Giving is online (Paystack) and bank transfer only. Set true to let
        // guests also pledge an amount on the RSVP form.
        'pledges_enabled' => false,
    ],

    // ---------------------------------------------------------------
    // Section 6 — Programme (CLIENT TO SUPPLY times and names)
    // ---------------------------------------------------------------
    // DRAFT running order within 11:00 AM – 1:00 PM — confirm with the client.
    'programme' => [
        ['category' => 'Opening and Welcome', 'time' => '11:00 AM', 'detail' => null],
        ['category' => 'Symposium Proceedings', 'time' => '11:10 AM', 'detail' => null],
        ['category' => 'Keynote Speakers', 'time' => '11:25 AM', 'detail' => null],
        ['category' => 'Book Presentation', 'time' => '11:55 AM', 'detail' => null],
        ['category' => 'Book Reviewers', 'time' => '12:10 PM', 'detail' => null],
        ['category' => 'Chairman of the Occasion', 'time' => '12:30 PM', 'detail' => null],
        ['category' => 'Charity Donation Segment', 'time' => '12:40 PM', 'detail' => null],
        ['category' => 'Closing Proceedings', 'time' => '12:50 PM', 'detail' => null],
    ],

    // ---------------------------------------------------------------
    // Gallery — shown only once photographs are added
    // Each: ['src' => '/images/gallery/x.jpg', 'alt' => '', 'caption' => null, 'group' => 'archive'|'event']
    // ---------------------------------------------------------------
    'gallery' => [],

    // ---------------------------------------------------------------
    // Email
    // ---------------------------------------------------------------
    'email' => [
        'reply_to' => env('MAIL_REPLY_TO_ADDRESS') ?: 'Invitation@Dr.FFat85.com',
        // Scheduled sends (Lagos time). The scheduler sends each campaign
        // once, inside a 24-hour window after its time.
        'schedule' => [
            'rsvp_reminder' => env('EMAIL_RSVP_REMINDER_AT') ?: '2026-11-26 10:00:00',
            'event_reminder' => env('EMAIL_EVENT_REMINDER_AT') ?: '2026-12-07 10:00:00',
            'day_of' => env('EMAIL_DAY_OF_AT') ?: '2026-12-10 07:00:00',
            'thank_you' => env('EMAIL_THANK_YOU_AT') ?: '2026-12-11 10:00:00',
        ],
        // Optional client-approved copy for each template; null uses the default draft copy.
        'copy' => [
            'invitation' => null,
            'event_reminder' => null,
            'day_of' => null,
            'thank_you' => null,
        ],
        // Shared photos link for the thank-you email, once available.
        'photos_url' => null,
    ],

];
