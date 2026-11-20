#!/usr/bin/env bash
# One-time server setup for SCIMSCORE on Ubuntu 24.04 (EC2). Run as the ubuntu user:
#   bash setup.sh your-domain.example
# Safe to run again. Does NOT create .env (see deploy/aws/README.md).
set -euo pipefail

DOMAIN="${1:?Usage: bash setup.sh <domain>}"
APP=/var/www/scimscore
REPO=https://github.com/Oksana11/scimscore.git

echo "== Packages"
sudo apt-get update -q
sudo apt-get install -y -q php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-intl \
    php8.3-gd php8.3-zip php8.3-bcmath php8.3-xml php8.3-curl php8.3-opcache \
    mysql-client-core-8.0 unattended-upgrades

echo "== PHP hardening"
sudo tee /etc/php/8.3/fpm/conf.d/99-scimscore.ini >/dev/null <<'INI'
expose_php = Off
display_errors = Off
log_errors = On
upload_max_filesize = 6M
post_max_size = 8M
memory_limit = 256M
session.cookie_secure = 1
session.cookie_httponly = 1
opcache.enable = 1
opcache.validate_timestamps = 1
INI

echo "== RDS CA bundle"
sudo mkdir -p /etc/ssl/rds
sudo curl -fsSL -o /etc/ssl/rds/global-bundle.pem https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem
sudo chmod 644 /etc/ssl/rds/global-bundle.pem

echo "== Code (branch: deploy — vendor and built assets included)"
sudo mkdir -p "$APP"
sudo chown ubuntu:www-data "$APP"
if [ ! -d "$APP/.git" ]; then
    git clone --branch deploy --single-branch "$REPO" "$APP"
fi

echo "== Permissions"
cd "$APP"
mkdir -p storage/app/media storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
sudo chown -R ubuntu:www-data "$APP"
sudo find "$APP" -type d -exec chmod 750 {} \;
sudo find "$APP" -type f -exec chmod 640 {} \;
sudo chmod -R g+w storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod g+s {} \;
chmod 750 deploy/aws/*.sh

echo "== nginx"
sed "s/__DOMAIN__/$DOMAIN/g" deploy/aws/nginx.conf | sudo tee /etc/nginx/sites-available/scimscore >/dev/null
sudo ln -sf /etc/nginx/sites-available/scimscore /etc/nginx/sites-enabled/scimscore
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl enable --now php8.3-fpm nginx
sudo systemctl reload nginx

echo "== Scheduler (daily data:prune), runs as www-data like php-fpm"
CRON="* * * * * cd $APP && /usr/bin/php8.3 artisan schedule:run >> /dev/null 2>&1"
( sudo crontab -u www-data -l 2>/dev/null | grep -v 'artisan schedule:run'; echo "$CRON" ) | sudo crontab -u www-data -

echo "Done. Next: create $APP/.env (see deploy/aws/README.md), then run deploy/aws/first-run.sh"
