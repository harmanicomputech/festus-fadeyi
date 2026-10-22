# Project notes for AI assistants

Laravel 13 event site for "Symposium in Honour of Dr. Festus Fadeyi at 85". Read README.md first.

- Fixed scope: do not add features beyond the client brief (docs/CLIENT-CHECKLIST.md lists open items).
- Never invent client content (biography facts, speakers, charity details, bank accounts). Leave `null` in `config/event.php` so placeholders show.
- Business rules live in `app/Services` (RsvpService: capacity/waitlist under a row lock; DonationService: only Paystack verify API marks payments successful; Notifier: all email + dedupe).
- No frontend build step: `public/css/*.css`, `public/js/*.js`. CSP forbids inline scripts.
- Cormorant's default figures are old-style; lining figures are enforced globally in CSS.
- Run `php artisan test` (SQLite) and against PostgreSQL before committing.
