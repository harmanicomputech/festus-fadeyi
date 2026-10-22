<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** Read-only helpers over config('event'). */
class EventInfo
{
    public static function tz(): string
    {
        return config('event.timezone', 'Africa/Lagos');
    }

    public static function startsAt(): Carbon
    {
        return Carbon::parse(config('event.starts_at'), self::tz());
    }

    public static function endsAt(): Carbon
    {
        return Carbon::parse(config('event.ends_at'), self::tz());
    }

    public static function rsvpDeadline(): Carbon
    {
        return Carbon::parse(config('event.rsvp.deadline'), self::tz());
    }

    public static function rsvpOpen(): bool
    {
        return now(self::tz())->lessThanOrEqualTo(self::rsvpDeadline());
    }

    public static function eventOver(): bool
    {
        return now(self::tz())->greaterThan(self::endsAt());
    }

    public static function capacity(): int
    {
        return (int) config('event.rsvp.capacity');
    }

    public static function maxPartySize(): int
    {
        return max(1, (int) config('event.rsvp.max_party_size'));
    }

    public static function charityName(): ?string
    {
        return config('event.charity.name') ?: null;
    }

    /** Name for sentences, never inventing a charity. */
    public static function charityLabel(): string
    {
        return self::charityName() ?? 'the designated charity';
    }

    public static function bankDetailsConfigured(): bool
    {
        $bank = config('event.charity.bank');

        return filled($bank['bank_name'] ?? null) && filled($bank['account_name'] ?? null) && filled($bank['account_number'] ?? null);
    }

    public static function onlinePaymentsConfigured(): bool
    {
        return filled(config('services.paystack.secret_key')) && filled(config('services.paystack.public_key'));
    }

    /** Online giving is live once the Paystack keys are configured. */
    public static function donationsReady(): bool
    {
        return self::onlinePaymentsConfigured();
    }

    public static function naira(int|float $amountKobo): string
    {
        $naira = $amountKobo / 100;

        return '₦'.number_format($naira, fmod($naira, 1.0) == 0.0 ? 0 : 2);
    }

    // -----------------------------------------------------------------
    // Calendar
    // -----------------------------------------------------------------

    public static function calendarDescription(): string
    {
        return config('event.subtitle').' — presentation of “'.config('event.book_title').'”. Hosted by '.config('event.host').'. Dress code: '.config('event.dress_code').'. Details: '.url('/');
    }

    public static function googleCalendarUrl(): string
    {
        $fmt = 'Ymd\THis\Z';

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => config('event.title'),
            'dates' => self::startsAt()->utc()->format($fmt).'/'.self::endsAt()->utc()->format($fmt),
            'details' => self::calendarDescription(),
            'location' => config('event.venue.full'),
            'ctz' => self::tz(),
        ]);
    }

    public static function outlookCalendarUrl(): string
    {
        return 'https://outlook.live.com/calendar/0/deeplink/compose?'.http_build_query([
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
            'subject' => config('event.title'),
            'startdt' => self::startsAt()->utc()->format('Y-m-d\TH:i:s\Z'),
            'enddt' => self::endsAt()->utc()->format('Y-m-d\TH:i:s\Z'),
            'body' => self::calendarDescription(),
            'location' => config('event.venue.full'),
        ]);
    }

    public static function icsUrl(): string
    {
        return route('calendar.ics');
    }

    public static function ics(): string
    {
        $esc = fn (string $s) => addcslashes(str_replace(["\r\n", "\n"], '\\n', $s), ',;\\');
        $fmt = 'Ymd\THis\Z';
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Dr FF at 85//Event//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:ff85-symposium-20261210@'.(parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost'),
            'DTSTAMP:'.now()->utc()->format($fmt),
            'DTSTART:'.self::startsAt()->utc()->format($fmt),
            'DTEND:'.self::endsAt()->utc()->format($fmt),
            'SUMMARY:'.$esc(config('event.title')),
            'DESCRIPTION:'.$esc(self::calendarDescription()),
            'LOCATION:'.$esc(config('event.venue.full')),
            'URL:'.url('/'),
            'BEGIN:VALARM',
            'TRIGGER:-P1D',
            'ACTION:DISPLAY',
            'DESCRIPTION:'.$esc(config('event.title')),
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        // RFC 5545: fold lines longer than 75 octets.
        $folded = array_map(function (string $line) {
            $out = '';
            $limit = 75; // continuation lines start with a space, so they carry 74 octets
            while (strlen($line) > $limit) {
                $cut = $limit;
                while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                    $cut--; // don't split a UTF-8 sequence
                }
                $out .= substr($line, 0, $cut)."\r\n ";
                $line = substr($line, $cut);
                $limit = 74;
            }

            return $out.$line;
        }, $lines);

        return implode("\r\n", $folded)."\r\n";
    }
}
