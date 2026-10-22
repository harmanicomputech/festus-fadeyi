# Project notes for AI assistants

Laravel 13 event site for "Symposium in Honour of Dr. Festus Fadeyi at 85". Read README.md first.

- Fixed scope: do not add features beyond the client brief (docs/CLIENT-CHECKLIST.md lists open items).
- Never invent client content (biography facts, charity details, bank accounts). The speakers section was removed at the client's request. Leave `null` in `config/event.php` so placeholders show.
- Business rules live in `app/Services` (RsvpService: capacity/waitlist under a row lock; DonationService: only Paystack verify API marks payments successful; Notifier: all email + dedupe).
- No frontend build step: `public/css/*.css`, `public/js/*.js`. CSP forbids inline scripts.
- One sans-serif family (Plus Jakarta Sans) everywhere. Motion: public/js/site.js with GSAP/ScrollTrigger/Lenis in public/vendor; public/js/boot.js gates motion styles.
- Run `php artisan test` (SQLite) and against PostgreSQL before committing.
