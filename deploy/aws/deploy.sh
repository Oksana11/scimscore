#!/usr/bin/env bash
# Update the site to the latest `deploy` branch. Run on the server as ubuntu:
#   /var/www/scimscore/deploy/aws/deploy.sh
set -euo pipefail
umask 002   # files created in storage/ stay writable for php-fpm (group www-data)

APP=/var/www/scimscore
PHP=/usr/bin/php8.3
cd "$APP"

git fetch --quiet origin deploy
echo "Deploying $(git rev-parse --short HEAD) -> $(git rev-parse --short origin/deploy)"

$PHP artisan down --retry=15 || true
trap 'echo "Deploy FAILED — bringing the site back up (check the error above)." >&2; $PHP artisan up' ERR
git reset --quiet --hard origin/deploy        # the deploy branch is rebuilt by CI on every merge
git clean -fdq -e .env -e storage -e bootstrap/cache
chmod 750 deploy/aws/*.sh

deploy/aws/sync-secrets.sh                    # no-op until SECRETS_ID is set in .env

$PHP artisan migrate --force
$PHP artisan optimize
$PHP artisan up
trap - ERR
sudo systemctl reload php8.3-fpm               # drop old code from opcache

echo "Done: $(git log -1 --format='%h %s')"
