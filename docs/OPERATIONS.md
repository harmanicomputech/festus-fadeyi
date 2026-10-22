# Operations guide

## Roles

- **admin**: the full dashboard (guests, invitations, donations, messages, emails, reports, check-in).
- **checkin**: welcome-desk staff; can only use **Check-in**.

Create users with `php artisan ff85:create-admin <email> --role=admin|checkin`. Running it again for the same email resets that user's password.

## Before the event

1. **Invitations:** Admin → Invitations. Paste the guest list (Title, Full name, Email, Phone), check it, then **Send to all unsent**. Each guest gets a personal RSVP link.
2. **Invite-only mode:** switch it on if the RSVP form must only accept invited guests.
3. **Monitor:** the Overview shows accepted, maybe, declined, waitlisted, no-reply, seats used and donations. Guests can be filtered and exported to CSV/Excel at any time.
4. **Waitlist:** when an accepted guest declines or reduces their party, freed seats go automatically to the earliest waitlisted guests who fit, and those guests are emailed. An admin can also confirm a guest manually from their guest page; this may exceed capacity, and the dashboard warns when it does.
5. **Offline gifts:** record bank transfers once the funds are seen in the account (Admin → Donations → *Record an offline gift*). Pledges are listed separately and are **never** counted as received.

## Event day: welcome desk

1. Open `https://dr.ffat85.com/admin/check-in` on each phone or tablet and sign in with a check-in account.
2. Tap **Start camera** and allow camera access. HTTPS is required.
3. Scan the guest's QR code (from their email or phone):
   - **Valid invitation** (green): tap **Confirm arrival**.
   - **Already checked in** (amber): shows when and by whom. It is not counted again.
   - **Not a confirmed guest** or **Code not recognised** (red): use the name search, or refer to the event manager.
4. No QR code? Search by name, email or phone and select the guest.
5. The arrived counter updates live. Every scan is logged.

Have a printed guest list (Admin → Guests → Accepted → Excel) as a fallback.

## After the event

1. Add event photos to the gallery and set `email.photos_url` (and `book.purchase_info`) before the thank-you email (11 Dec, 10:00).
2. **Final report:** Admin → Reports → *Download full report (Excel)*. It contains Summary, Guests, Donations and Pledges sheets.
3. Re-check any donations in *Pending* or *Needs review* (Admin → Donations → *Re-check with Paystack*) and reconcile them with the Paystack dashboard and the bank statement.

## Backups

Run a nightly PostgreSQL dump with off-site, encrypted storage:

```bash
# /etc/cron.d/ff85-backup
15 2 * * * postgres pg_dump -Fc ff85 | gpg --batch --yes --encrypt -r backups@caritaspr.com > /var/backups/ff85/ff85-$(date +\%F).dump.gpg && find /var/backups/ff85 -mtime +14 -delete
```

Copy `/var/backups/ff85` off the server daily (object storage or another host). **Test a restore** before go-live:

```bash
gpg -d ff85-YYYY-MM-DD.dump.gpg > ff85.dump && pg_restore --clean --if-exists -d ff85_restore ff85.dump
```

Also keep a secure copy of `.env`, especially `APP_KEY`.

## Data protection (Nigeria Data Protection Act 2023)

- **Collection:** only the fields in the brief are collected. A privacy notice appears on the RSVP and donation forms, and `/privacy` gives the full notice.
- **Purpose limitation:** the data is used only for this event. Do not import it into other mailing lists.
- **Access:** admin pages require sign-in; check-in staff see only check-in. Guest and donor data never appear publicly. Donor names and amounts appear only with explicit consent.
- **Security:** HTTPS, hashed passwords, encrypted session cookies, CSRF protection, rate limiting on forms, logins and look-ups, a strict Content-Security-Policy, encrypted RSVP management tokens, and QR codes that contain only a random token.
- **Requests:** to correct or delete a guest's data on request, an admin can edit the guest's status. For deletion, remove the record directly in the database, and record the request.
- **Handover and retention:** after the final report is accepted (target 18 Dec 2026), export everything, hand the files and all accounts (domain, email platform, Paystack) to the client, then delete the production database and backups according to the agreed retention period.

## Troubleshooting

| Symptom | Check |
|---|---|
| Emails show *logged* instead of *sent* | `MAIL_MAILER` is still `log`. Configure SMTP (`EMAIL-DNS.md`). |
| Emails *failed* | Admin → Emails shows the error. Check the SMTP credentials and domain verification. |
| Donate page says *awaiting configuration* | Set the Paystack keys **and** `CHARITY_NAME`, then `php artisan config:cache`. |
| A donation is stuck on *Pending* | The guest may have abandoned checkout. Use *Re-check with Paystack*, and check that the webhook URL is set. |
| Camera won't start | The page must be HTTPS. Allow camera permission in the browser settings. |
| Reminders not sent | Is cron running? Run `php artisan schedule:list`, or `php artisan ff85:send-scheduled --campaign=event_reminder` to run one now. |
