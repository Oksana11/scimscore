# SCIMSCORE on AWS (EC2 + RDS)

Server: Ubuntu 24.04, nginx, PHP 8.3-FPM. Code comes from the `deploy` branch (built by CI
on every merge to `main`, includes `vendor/` and `public/build/` — no Composer/Node on the server).

## First install
```bash
sudo mkdir -p /var/www/scimscore && sudo chown ubuntu:www-data /var/www/scimscore
git clone --branch deploy --single-branch https://github.com/Oksana11/scimscore.git /var/www/scimscore
bash /var/www/scimscore/deploy/aws/setup.sh YOUR-DOMAIN
```
Create the database once (if it does not exist):
```bash
mysql -h <RDS host> -u scimscore -p --ssl-mode=VERIFY_IDENTITY --ssl-ca=/etc/ssl/rds/global-bundle.pem \
  -e "CREATE DATABASE IF NOT EXISTS scimscore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```
Create `.env`: `cp deploy/aws/env.example .env && nano .env` — fill DB_PASSWORD, MAIL_PASSWORD,
APP_URL, TRUSTED_PROXIES. Then:
```bash
/var/www/scimscore/deploy/aws/first-run.sh admin@email AdminName
```
Back up APP_KEY, BLIND_INDEX_KEY and AUDIT_HMAC_KEY from `.env` to a password manager.

## Secrets Manager (optional, recommended)
DevOps: create secret `scimscore/production` (JSON: DB_PASSWORD, MAIL_PASSWORD, APP_KEY, BLIND_INDEX_KEY,
AUDIT_HMAC_KEY), attach an instance role to the EC2 with `secretsmanager:GetSecretValue` on that secret
only, install AWS CLI. Then set `SECRETS_ID=scimscore/production` in `.env` and run `deploy.sh` —
values are synced on every deploy; rotate a password in Secrets Manager and redeploy.

## Scheduler
`setup.sh` installs a cron entry for `www-data`: `php artisan schedule:run` every minute.
It runs `data:prune` daily (retention: config/scim.php → `retention`). Check: `sudo crontab -u www-data -l`.

## Every update
Merge to `main` → wait for "Build deploy branch" → on the server:
```bash
/var/www/scimscore/deploy/aws/deploy.sh
```

## Needs from DevOps
- outbound TCP 587 to smtp.sendgrid.net (email) must work
- CloudFront (+WAF) in front, HTTPS at CloudFront, origin on port 80; TRUSTED_PROXIES=*
- EC2 security group: port 80 only from the CloudFront origin-facing prefix list
- DNS for the domain; RDS automated backups
