#!/usr/bin/env bash
# First start only, after .env is filled in. Run as ubuntu:
#   /var/www/scimscore/deploy/aws/first-run.sh admin@email AdminName
# Never run again on a live site: new keys would make encrypted data unreadable.
set -euo pipefail

APP=/var/www/scimscore
PHP=/usr/bin/php8.3
cd "$APP"

EMAIL="${1:?Usage: first-run.sh <admin-email> <AdminName>}"
NAME="${2:?Usage: first-run.sh <admin-email> <AdminName>}"

if grep -qE '^APP_KEY=base64:' .env; then
    echo "APP_KEY already set — this is not a first run. Stopping." >&2
    exit 1
fi

sudo chown ubuntu:www-data .env
chmod 640 .env

$PHP artisan key:generate --force
$PHP artisan security:generate-keys
$PHP artisan migrate --force --seed
$PHP artisan admin:create "$EMAIL" "$NAME"
$PHP artisan optimize
sudo systemctl reload php8.3-fpm

echo "Done. Back up the keys from .env (APP_KEY, BLIND_INDEX_KEY, AUDIT_HMAC_KEY) to a password manager."
