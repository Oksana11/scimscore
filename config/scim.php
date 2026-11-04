<?php

return [

    /*
    | Version of the Terms of Use. Changing it asks users to accept again
    | (a new user_consents row is stored with the date and time).
    */
    'terms_version' => env('SCIM_TERMS_VERSION', '2026-11'),

    'otp' => [
        'ttl_minutes' => 10,
        'max_attempts' => 5,
        'resend_seconds' => 60,
    ],

    'ror' => [
        'base_url' => env('ROR_API_URL', 'https://api.ror.org/v2'),
        // Optional client id (higher rate limits): https://ror.readme.io/docs/client-id
        'client_id' => env('ROR_CLIENT_ID'),
        'timeout' => 5,
        'cache_minutes' => 1440,
    ],

];
