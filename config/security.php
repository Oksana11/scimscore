<?php

return [

    /*
    | HMAC key for blind indexes: deterministic, non-reversible hashes of
    | encrypted fields, used for exact-match search and duplicate checks
    | without decrypting data. Never change it once data exists.
    | Generate with: php artisan security:generate-keys
    */
    'blind_index_key' => env('BLIND_INDEX_KEY'),

    /*
    | HMAC key that chains audit log records together, so any modified or
    | deleted record is detected by: php artisan audit:verify
    */
    'audit_hmac_key' => env('AUDIT_HMAC_KEY'),

    'headers' => [
        'csp' => (bool) env('CSP_ENABLED', true),
        'hsts' => (bool) env('HSTS_ENABLED', false),
        'hsts_max_age' => (int) env('HSTS_MAX_AGE', 31536000),
    ],

    /*
    | Comma-separated IPs/CIDRs of reverse proxies (Cloudflare, AWS ALB) whose
    | X-Forwarded-* headers are trusted. Empty = trust none.
    */
    'trusted_proxies' => env('TRUSTED_PROXIES', ''),

    /*
    | Keys removed from audit log context (secrets and medical data never logged).
    */
    'audit_redact_keys' => [
        'otp', 'code', 'token', 'password', 'secret', 'key', 'authorization',
        'cookie', 'session', 'scim_scores', 'answers',
    ],

];
