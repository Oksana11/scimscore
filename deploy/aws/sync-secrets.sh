#!/usr/bin/env bash
# Pulls credentials from AWS Secrets Manager into .env (no passwords typed by hand).
# Needs: AWS CLI, an EC2 instance role allowed `secretsmanager:GetSecretValue` on the secret,
# and SECRETS_ID=<secret name or ARN> in .env. The secret is a JSON object, e.g.
#   {"DB_PASSWORD":"…","MAIL_PASSWORD":"…"}
# Only keys from ALLOWED are written; values never appear in the output.
set -euo pipefail
umask 027

APP="${APP:-/var/www/scimscore}"
PHP="${PHP:-/usr/bin/php8.3}"
ENV="$APP/.env"
ALLOWED="DB_PASSWORD DB_USERNAME MAIL_PASSWORD APP_KEY BLIND_INDEX_KEY AUDIT_HMAC_KEY"

SECRETS_ID="$(grep -E '^SECRETS_ID=' "$ENV" | cut -d= -f2- | tr -d '"' || true)"
if [ -z "$SECRETS_ID" ]; then
    echo "SECRETS_ID is not set in .env — skipping Secrets Manager."
    exit 0
fi
REGION="${AWS_REGION:-il-central-1}"

JSON="$(aws secretsmanager get-secret-value --region "$REGION" --secret-id "$SECRETS_ID" --query SecretString --output text)"

TMP="$(mktemp "$APP/.env.XXXXXX")"
trap 'rm -f "$TMP"' EXIT
cp "$ENV" "$TMP"

for KEY in $ALLOWED; do
    VALUE="$(printf '%s' "$JSON" | "$PHP" -r '$d = json_decode(stream_get_contents(STDIN), true); echo is_array($d) && isset($d[$argv[1]]) ? $d[$argv[1]] : "";' "$KEY")"
    [ -z "$VALUE" ] && continue
    case "$VALUE" in *$'\n'*|*'"'*|*'\'*|*'$'*) echo "Refusing $KEY: value contains a newline, quote, backslash or $ (not safe in .env)." >&2; exit 1;; esac
    if grep -qE "^$KEY=" "$TMP"; then
        # Replace the line without passing the value through sed patterns.
        KEY="$KEY" VALUE="$VALUE" "$PHP" -r '$f=$argv[1]; $k=getenv("KEY"); $v=getenv("VALUE");
            $lines = file($f); foreach ($lines as &$l) { if (str_starts_with($l, "$k=")) { $l = "$k=\"$v\"\n"; } }
            file_put_contents($f, implode("", $lines));' "$TMP"
    else
        printf '%s="%s"\n' "$KEY" "$VALUE" >> "$TMP"
    fi
    echo "  $KEY ← Secrets Manager"
done

chgrp www-data "$TMP" && chmod 640 "$TMP"
mv "$TMP" "$ENV"
trap - EXIT
echo "Secrets synced from $SECRETS_ID."
