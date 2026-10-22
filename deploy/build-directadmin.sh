#!/usr/bin/env bash
# Build ff85-directadmin.zip: production vendor, public_html layout, fresh secrets.
# Usage: deploy/build-directadmin.sh [output-dir]
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${1:-$ROOT/build}"
STAGE="$(mktemp -d)"
APP="$STAGE/ff85-app"
WEB="$STAGE/public_html"
trap 'rm -rf "$STAGE"' EXIT

echo "→ Copying tracked files"
mkdir -p "$APP"
(cd "$ROOT" && git ls-files -z --cached --others --exclude-standard) | \
  (cd "$ROOT" && xargs -0 -I{} cp --parents -p "{}" "$APP/")

echo "→ Removing development-only files"
rm -rf "$APP/tests" "$APP/phpunit.xml" "$APP/.github" "$APP/deploy" "$APP/.editorconfig" "$APP/.gitattributes" "$APP/CLAUDE.md"

echo "→ Installing production dependencies"
(cd "$APP" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress --quiet)

echo "→ Trimming vendor (VCS metadata, package tests and docs)"
find "$APP/vendor" -type d -name .git -prune -exec rm -rf {} +
find "$APP/vendor" -mindepth 3 -maxdepth 3 -type d \( -name tests -o -name Tests -o -name test -o -name docs -o -name .github \) -prune -exec rm -rf {} +
rm -rf "$APP/vendor/laravel/framework/bin"

echo "→ Building public_html"
mv "$APP/public" "$WEB"
cp "$ROOT/deploy/directadmin/index.php" "$WEB/index.php"
cp "$ROOT/deploy/directadmin/.htaccess" "$WEB/.htaccess"
rm -f "$WEB/hot"

echo "→ Writing .env with fresh secrets"
rand() { php -r 'echo bin2hex(random_bytes(24));'; }
APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
sed -e "s#^APP_KEY=.*#APP_KEY=$APP_KEY#" \
    -e "s#^DB_CONNECTION=.*#DB_CONNECTION=mysql#" \
    -e "s#^DB_HOST=.*#DB_HOST=localhost#" \
    -e "s#^DB_PORT=.*#DB_PORT=3306#" \
    -e "s#^DB_DATABASE=.*#DB_DATABASE=#" \
    -e "s#^DB_USERNAME=.*#DB_USERNAME=#" \
    -e "s#^SESSION_DRIVER=.*#SESSION_DRIVER=file#" \
    -e "s#^CACHE_STORE=.*#CACHE_STORE=file#" \
    -e "s#^LOG_STACK=.*#LOG_STACK=daily#" \
    "$ROOT/.env.example" > "$APP/.env"
cat >> "$APP/.env" <<ENV

# --- Shared hosting (no shell access) ----------------------------------
# Web installer: https://your-domain/install?token=<INSTALL_TOKEN>
INSTALL_TOKEN=$(rand)
# Scheduled emails: call https://your-domain/cron/<CRON_TOKEN> every 5 minutes
CRON_TOKEN=$(rand)
ENV

echo "→ Clearing runtime folders"
for d in storage/logs storage/framework/sessions storage/framework/views storage/framework/cache/data storage/app/private; do
  mkdir -p "$APP/$d"
  find "$APP/$d" -type f ! -name '.gitignore' -delete
done
rm -f "$APP/storage/app/installed.lock"
find "$APP" -type d -exec chmod 755 {} +
find "$APP" "$WEB" -type f -exec chmod 644 {} +

cp "$ROOT/deploy/directadmin/INSTALL-DIRECTADMIN.txt" "$STAGE/"

echo "→ Zipping"
mkdir -p "$OUT"
ZIP="$OUT/ff85-directadmin.zip"
rm -f "$ZIP"
(cd "$STAGE" && zip -qrX "$ZIP" ff85-app public_html INSTALL-DIRECTADMIN.txt)
echo "✓ $ZIP ($(du -h "$ZIP" | cut -f1))"
