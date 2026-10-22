# Client checklist: items still required

Nothing below has been invented. Until each item is supplied, the website shows a clearly labelled placeholder, or the feature reports itself as *awaiting configuration*. The admin **Overview → Launch checklist** panel shows live status.

## Content and assets (edit `config/event.php`; put images in `public/images/...`)

| Item | Where it appears | Config key |
|---|---|---|
| Book cover (high-resolution, ideally 1200×1800 JPG) | Hero, The Book | `images.book_cover` → e.g. `/images/book/forged-in-his-fire.jpg` |
| Author's name | The Book | `book.author` |
| Approved 2–3 line synopsis | The Book | `book.synopsis` |
| Welcome message from the host or family, and how it is signed | Welcome | `welcome.message`, `welcome.signed_by` |
| Charity: name, logo, cause, description, how proceeds will be used | Give, emails, receipts | `charity.*` (`CHARITY_NAME` in `.env`); logo → `/images/charity/` |
| Dedicated bank account: bank, account name, account number | Give, donate page, event reminder | `.env`: `CHARITY_BANK_NAME`, `CHARITY_ACCOUNT_NAME`, `CHARITY_ACCOUNT_NUMBER` |
| Final programme: timings and details for each segment | Programme, event reminder | `programme` |
| Parking information and arrival notes | Venue, event reminder, day-of note | `venue.parking_notes`, `venue.arrival_notes` |
| Verified venue map pin (optional) | Venue | `.env`: `VENUE_MAP_EMBED_URL`, `VENUE_MAP_LINK` |
| Archive photographs (more to come, per the client) and, after the event, event photographs | Gallery | `gallery` (files → `/images/gallery/`) |
| Additional portraits of Dr. Fadeyi | Optional: hero/about can be swapped | `images.portrait*` |
| Whether book sales proceeds also go to the charity, and where to obtain the book | The Book, thank-you email | `book.purchase_supports_charity`, `book.purchase_info` |
| Link to event photos (after the event) | Thank-you email | `email.photos_url` |
| Approved email copy (optional; draft copy is used otherwise) | Emails | `email.copy.*` |
| Review and approval of the privacy notice | `/privacy` | `resources/views/landing/privacy.blade.php` |

The official portrait supplied with the brief is already in use (`public/images/celebrant/portrait-01.*`).

## Credentials and accounts

| Item | Notes |
|---|---|
| **Domain** `ffat85.com` | `Dr.FFat85.com` is the subdomain `dr` of `ffat85.com`. Register `ffat85.com` (or confirm ownership) and point `dr.ffat85.com` at the server. See `EMAIL-DNS.md`. |
| **Email delivery platform** (Brevo recommended; Zoho or Mailchimp Transactional also work) | Account, verified sender `Invitation@Dr.FFat85.com`, SMTP key → `.env` `MAIL_*` |
| **Mailbox** for `Invitation@Dr.FFat85.com` (and optionally `rsvp@`) | Needed to *receive* replies. The sending platform does not host mailboxes (e.g. Zoho Mail, Google Workspace). |
| **Paystack business account** (approved for live payments) | `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY` |
| **Charity settlement account as a Paystack subaccount** | `PAYSTACK_SUBACCOUNT_CODE`, so funds settle directly into the charity's account |
| **Hosting** with HTTPS | See `DEPLOYMENT.md` |
| **Admin users** | Names and emails of committee members (full admin) and welcome-desk staff (check-in only) |

## Decisions to confirm

- **Seats per RSVP.** "Number of guests, capped at two" is implemented as *up to 2 seats per reply, including the guest*. To allow the guest plus two companions, set `RSVP_MAX_PARTY_SIZE=3`.
- **"Maybe" replies** do not hold a seat. Guests are asked to change to "Yes" before the deadline.
- **Invite-only mode** is off by default and can be switched on in Admin → Invitations.
- **Thank-you email** goes to checked-in guests if check-in was used, otherwise to all confirmed guests.
- **Email schedule (Lagos time).** RSVP reminder 26 Nov 10:00; event reminder 7 Dec 10:00; day-of note 10 Dec 07:00; thank-you 11 Dec 10:00. Adjustable via `.env`.
