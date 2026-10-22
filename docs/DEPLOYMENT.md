# Deployment

> **DirectAdmin / cPanel without shell access?** Use the ready-made package instead of the steps below:
> run `deploy/build-directadmin.sh` (or use the supplied `ff85-directadmin.zip`) and follow
> `INSTALL-DIRECTADMIN.txt` inside it. It includes all PHP libraries, a browser-based installer
> (`/install?token=…`) that creates the database tables and the first admin, an **Admin → Users** page
> for further accounts, and a cron URL (`/cron/{CRON_TOKEN}`) for scheduled emails, so no
> `php artisan` commands are needed. Tested on MariaDB 10.11 and PostgreSQL 16.

The app is a standard Laravel 13 application. Recommended production setup:

- **Server:** Ubuntu 24.04 VPS (1–2 vCPU, 2 GB RAM is ample). A region close to Nigeria (e.g. Lagos, Johannesburg or Europe-West) helps latency. Laravel Forge or Ploi can automate everything below.
- **Stack:** Nginx, PHP 8.3-FPM (`pdo_pgsql`, `gd`, `zip`, `mbstring`, `intl`), PostgreSQL 16, Composer.
- **HTTPS:** Let's Encrypt (Certbot) or Cloudflare. HTTPS is required for the check-in camera, secure cookies and payments.
- **CDN (recommended for Nigerian mobile networks):** Cloudflare in front of the site, caching `/css`, `/js` and `/images`.

> Shared cPanel hosting can work (PHP 8.3, cron access, MySQL 8 or PostgreSQL), but a VPS is more reliable for event day. The test suite runs on PostgreSQL and SQLite; MySQL 8 is expected to work but has not been tested.

## 1. First deploy

```bash
# as the deploy user, in /var/www/ff85
git clone <repo> . && git checkout <release-branch>
composer install --no-dev --optimize-autoloader
cp .env.example .env && nano .env        # fill in every value; APP_ENV=production, APP_DEBUG=false
php artisan key:generate
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan ff85:create-admin fisayo@caritaspr.com --name="Fisayo Ayodele"            # full admin
php artisan ff85:create-admin desk1@example.com --name="Welcome Desk 1" --role=checkin  # check-in only
sudo chown -R www-data:www-data storage bootstrap/cache
```

Generate `APP_KEY` once and **back it up securely**. It encrypts stored RSVP links; if it is lost, existing guests' update links stop working.

## 2. Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name dr.ffat85.com;
    root /var/www/ff85/public;
    index index.php;

    # ssl_certificate ... (Certbot fills these in)

    client_max_body_size 2m;
    gzip on; gzip_types text/css application/javascript image/svg+xml;

    location ~* \.(?:css|js|webp|jpg|jpeg|png|svg|woff2)$ {
        expires 30d; add_header Cache-Control "public, max-age=2592000"; try_files $uri =404;
    }
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known) { deny all; }
}
server { listen 80; server_name dr.ffat85.com; return 301 https://$host$request_uri; }
```

If a proxy or CDN sits in front, set `TRUSTED_PROXIES` in `.env` (e.g. `*` behind Cloudflare with origin firewalling) so client IPs, and therefore rate limiting, are correct.

CSS and JS URLs carry a `?v=<file modified time>` suffix, so long cache lifetimes are safe.

## 3. Scheduler (required for reminder emails)

```cron
* * * * * cd /var/www/ff85 && php artisan schedule:run >> /dev/null 2>&1
```

This runs `ff85:send-scheduled` every five minutes. Each campaign sends inside a 24-hour window after its scheduled time and never twice to the same guest. Check it with `php artisan schedule:list`, or trigger a campaign manually from **Admin → Emails**.

No queue worker is needed: emails send synchronously, and failures are logged rather than shown to guests.

## 4. Paystack

1. Create, or use the client's, **Paystack business account** and complete compliance for live payments.
2. **Settlement directly to the charity:** in Paystack, go to **Settings → Subaccounts → Create**, enter the charity's bank account, and copy the `ACCT_…` code to `PAYSTACK_SUBACCOUNT_CODE`. `PAYSTACK_FEE_BEARER` controls who bears fees (`subaccount` or `account`). Alternatively, the Paystack account can belong to the charity itself, with its settlement account set to the dedicated account.
3. **API keys:** Settings → API Keys & Webhooks. Copy the keys into `PAYSTACK_PUBLIC_KEY` and `PAYSTACK_SECRET_KEY`. Use **test keys on staging first**.
4. **Webhook URL:** set `https://dr.ffat85.com/webhooks/paystack`. The app checks the `x-paystack-signature` HMAC and then re-verifies each transaction through the Verify API. The callback URL is sent automatically with each payment.
5. Test on staging with Paystack test cards. Confirm that the donation shows as *Verified* in Admin → Donations and that the receipt arrives.

The Donate buttons are always shown. Until both Paystack keys are set, the donate page explains that online payment is being connected and shows the bank transfer details instead.

## 5. Email

See [EMAIL-DNS.md](EMAIL-DNS.md). Set `MAIL_MAILER=smtp` and the provider's credentials only after the domain is verified.

## 6. Go-live checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://dr.ffat85.com`, `SESSION_SECURE_COOKIE=true`
- [ ] HTTPS works and HTTP redirects to it
- [ ] `php artisan migrate --force` run; admin and check-in users created
- [ ] Client content filled in (`docs/CLIENT-CHECKLIST.md`); Launch checklist on the dashboard reviewed
- [ ] Email domain verified (SPF/DKIM/DMARC pass); test emails received; mailbox receives replies
- [ ] Paystack live keys, subaccount and webhook set; one small live donation tested end to end
- [ ] Cron running (`schedule:list`)
- [ ] Nightly database backup running and a restore tested (`OPERATIONS.md`)
- [ ] Invitations imported (Admin → Invitations); decide on invite-only mode
- [ ] Check-in tested on the welcome-desk phones or tablets, over HTTPS, with the camera allowed
- [ ] Optional: donation-page QR downloaded (Admin → Donations → *Donation page QR*) for printed materials

## 7. Updating content later

Edit `config/event.php` or add images, deploy, then run `php artisan config:cache && php artisan view:cache`. For example, to add post-event photos to the gallery, add entries with `'group' => 'event'`.
