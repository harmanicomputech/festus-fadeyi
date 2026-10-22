# Dr. Festus Fadeyi @ 85 — Event Website

Official website for the **Symposium in Honour of Dr. Festus Fadeyi at 85 & Book Presentation of *Forged in His Fire***
Thursday, 10 December 2026 · 11:00 AM – 1:00 PM · Sky Restaurant, Eko Hotels, Victoria Island, Lagos
Hosted by Caritas Communications Limited.

A Laravel 13 application with:

- **Public landing page.** One animated scrolling page: hero, welcome, celebrant, book, give, programme, venue, RSVP, gallery (shown once photographs are added) and footer. Motion uses GSAP, ScrollTrigger and Lenis (bundled in `public/vendor`) and switches off for visitors who prefer reduced motion.
- **RSVP.** Validation, duplicate handling, a 150-seat capacity with automatic waitlist and promotion, closure on 4 December, optional invite-only mode, secure update/cancel links, and a unique QR code for each confirmed guest.
- **Donations.** Paystack checkout verified server-side through the callback and a signed webhook, so a browser redirect alone never marks a gift as paid. Giving is online and by bank transfer; admins record bank transfers once seen in the account. Optional pledges (off by default) are never counted as money received. Transfers can be recorded, receipts are emailed, and donors are acknowledged publicly only with their consent.
- **Admin dashboard.** Guests by status, search, CSV/Excel exports, invitations, donations by donor, method and date, goodwill messages, and the final report.
- **Check-in desk.** Camera QR scanning, manual entry and name search, with repeat scans flagged rather than counted. There is a separate `checkin` role for welcome-desk staff.
- **Seven branded emails.** All sent from `Invitation@Dr.FFat85.com` as *Dr. FF@85 Planning Committee*, with scheduled reminders that never double-send.
- **Calendar links.** Google, Outlook and Apple (.ics), in Lagos time.

## Quick start (local)

Requirements: PHP 8.3+ (with `pdo_pgsql`, `gd`, `zip`, `mbstring`), Composer and PostgreSQL 14+.

```bash
composer install
cp .env.example .env            # then set APP_ENV=local, APP_DEBUG=true, APP_URL, DB_* values
php artisan key:generate
php artisan migrate
php artisan ff85:create-admin you@example.com --name="Your Name"     # prompts for a password
php artisan serve               # http://localhost:8000  ·  admin at /admin
```

No Node/npm build step is needed: styles and scripts are plain files in `public/css` and `public/js`.

Run the tests:

```bash
php artisan test                # SQLite in-memory
DB_CONNECTION=pgsql DB_DATABASE=ff85_test DB_USERNAME=... DB_PASSWORD=... php artisan test   # PostgreSQL
```

## Where things live

| What | Where |
|---|---|
| All event details and **client-supplied content** (welcome note, author, synopsis, charity, programme, parking, gallery, email copy) | `config/event.php` |
| Credentials and environment settings | `.env` (see `.env.example`) |
| Images | `public/images/{celebrant,book,charity,gallery}` |
| Landing page sections | `resources/views/landing/sections/*.blade.php` |
| Email templates | `resources/views/emails/*.blade.php` |
| Design system | `public/css/site.css` (public), `public/css/admin.css` (admin) |
| Business logic | `app/Services` (RSVP and capacity, donations, Paystack, emails, campaigns, QR, exports) |

A value of `null` in `config/event.php` means *awaiting the client*: the site shows a clearly labelled placeholder instead of invented content.

## Shared hosting (DirectAdmin / cPanel, no SSH)

`deploy/build-directadmin.sh` produces `build/ff85-directadmin.zip`: the app with production
dependencies, a `public_html/` web root, a pre-filled `.env` with fresh secrets, and
`INSTALL-DIRECTADMIN.txt`. Setup is done in the browser via the one-time installer at
`/install?token=<INSTALL_TOKEN>`; reminder emails run from a cron job calling `/cron/<CRON_TOKEN>`.

## Documentation

- [docs/CLIENT-CHECKLIST.md](docs/CLIENT-CHECKLIST.md): assets, content and credentials still needed from the client
- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md): server setup, HTTPS, cron, Paystack, go-live checklist
- [docs/EMAIL-DNS.md](docs/EMAIL-DNS.md): sending domain, SPF/DKIM/DMARC records, mailbox
- [docs/OPERATIONS.md](docs/OPERATIONS.md): day-to-day use, event-day check-in, backups, exports, data handover (NDPA)
