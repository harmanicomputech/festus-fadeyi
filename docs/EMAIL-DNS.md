# Branded email: domain, DNS and deliverability

All emails are sent **from `Invitation@Dr.FFat85.com`** with the display name **Dr. FF@85 Planning Committee**. Replies go to `MAIL_REPLY_TO_ADDRESS` (the same address by default, or `rsvp@Dr.FFat85.com` if the client prefers).

> No email platform can *guarantee* inbox placement. Correct authentication (SPF, DKIM, DMARC), a warmed-up domain, consistent sending and low complaint rates make it much more likely.

## 1. Understand the domain

DNS names are case-insensitive and dot-separated, so **`Dr.FFat85.com` = `dr.ffat85.com`**: the subdomain `dr` of the registrable domain **`ffat85.com`**.

- Register (or confirm ownership of) **`ffat85.com`**.
- The website lives at `https://dr.ffat85.com`. Point an `A`/`CNAME` record for `dr` at the server.
- The sending domain for email is **`dr.ffat85.com`**, so the records below go on that subdomain.

If the client would rather use a simpler domain (for example `drffat85.com`), decide this **before** DNS setup and update `MAIL_FROM_ADDRESS`, `MAIL_REPLY_TO_ADDRESS` and `APP_URL`.

## 2. Sending platform (Brevo shown)

1. Create a Brevo account for the committee (not a personal account).
2. **Senders, Domains & Dedicated IPs → Domains → Add domain**: `dr.ffat85.com`.
3. Brevo then shows the exact records to add. Copy them from the dashboard: values change per account and over time.
4. **SMTP & API → SMTP**: create an SMTP key, then set in `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=<your Brevo SMTP login>
MAIL_PASSWORD=<SMTP key>
MAIL_FROM_ADDRESS="Invitation@Dr.FFat85.com"
MAIL_FROM_NAME="Dr. FF@85 Planning Committee"
MAIL_REPLY_TO_ADDRESS="Invitation@Dr.FFat85.com"
```

Zoho ZeptoMail and Mailchimp Transactional (Mandrill) also work: use their SMTP host, port and credentials instead.

## 3. DNS records for the domain owner

Typical records for Brevo. **Always use the exact values shown in your provider's dashboard.**

| Purpose | Type | Host / name | Value (example) |
|---|---|---|---|
| Domain verification | TXT | `dr.ffat85.com` | `brevo-code:xxxxxxxxxxxxxxxxxxxx` |
| SPF | TXT | `dr.ffat85.com` | `v=spf1 include:spf.brevo.com ~all` (add the mailbox provider too, e.g. `include:zoho.com`) |
| DKIM | CNAME | `brevo1._domainkey.dr.ffat85.com` | `b1.dr-ffat85-com.dkim.brevo.com` |
| DKIM | CNAME | `brevo2._domainkey.dr.ffat85.com` | `b2.dr-ffat85-com.dkim.brevo.com` |
| DMARC | TXT | `_dmarc.dr.ffat85.com` | `v=DMARC1; p=none; rua=mailto:dmarc-reports@dr.ffat85.com; adkim=r; aspf=r` |
| Mailbox (receiving) | MX | `dr.ffat85.com` | From the mailbox provider (e.g. Zoho Mail `mx.zoho.com` priority 10, `mx2.zoho.com` 20, `mx3.zoho.com` 50) |

Notes:
- Only **one** SPF TXT record may exist per name. Merge every `include:` into a single record.
- Start DMARC at `p=none`. After a week of clean reports, consider `p=quarantine`.
- DNS changes can take up to 48 hours. Verify in the provider dashboard, then send a test to Gmail and Outlook and check the headers for `spf=pass`, `dkim=pass` and `dmarc=pass` (Gmail: *Show original*).

## 4. Mailbox for replies

Brevo sends mail but does not host mailboxes. To *receive* replies at `Invitation@Dr.FFat85.com` (and `rsvp@`), create the mailbox with a mail host such as Zoho Mail or Google Workspace and add its MX records (above). Until then, replies will bounce. Do not send invitations before the mailbox works.

## 5. How the app sends

- Emails are sent at the moment of the action (RSVP, payment verification), or by the scheduler for reminders. Every attempt is recorded in **Admin → Emails** with status `sent`, `failed` or `logged`.
- With `MAIL_MAILER=log` (the default until configured), emails are written to `storage/logs` and marked **logged (not delivered)**. They are never shown as sent.
- Scheduled campaigns send each email at most once per guest, never to declined guests (or, for the RSVP reminder, to anyone who has replied), and never to guests who used the *Stop reminder emails* link.
- Each email includes a plain-text alternative, a privacy link and contact details, which helps deliverability.

## 6. Before sending invitations

1. Send each template to yourself from **Admin → Emails → Preview** and check the content against client-approved copy.
2. Create a test invitation for a committee member and send it. Check inbox placement in Gmail, Outlook/Hotmail and Yahoo.
3. Send invitations in the morning, Lagos time, ideally in batches of a few dozen.
