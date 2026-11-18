#!/usr/bin/env bash
# Update the site to the latest `deploy` branch. Run on the server as ubuntu:
#   /var/www/scimscore/deploy/aws/deploy.sh
set -euo pipefail

APP=/var/www/scimscore
PHP=/usr/bin/php8.3
cd "$APP"

git fetch --quiet origin deploy
echo "Deploying $(git rev-parse --short HEAD) -> $(git rev-parse --short origin/deploy)"

$PHP artisan down --retry=15 || true
git reset --quiet --hard origin/deploy        # the deploy branch is rebuilt by CI on every merge
git clean -fdq -e .env -e storage -e bootstrap/cache
chmod 750 deploy/aws/*.sh

$PHP artisan migrate --force
$PHP artisan optimize
$PHP artisan up
sudo systemctl reload php8.3-fpm               # drop old code from opcache

echo "Done: $(git log -1 --format='%h %s')"
