<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'paystack' => [
        'public' => env('PAYSTACK_PUBLIC_KEY'),
        'secret' => env('PAYSTACK_SECRET_KEY'),
        'url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    ],
    'fincra' => [
        'api_key' => env('FINCRA_API_KEY'),
        'business_id' => env('FINCRA_BUSINESS_ID'),
        'base_url' => env('FINCRA_BASE_URL'),
        'database_slug' => 'fincra',
        'timeout' => env('FINCRA_TIMEOUT', 30),
        'retries' => env('FINCRA_RETRIES', 3),
        'webhook_secret' => env('FINCRA_WEBHOOK_SECRET'),
    ],

    'safehaven' => [
        'base_url' => env('SAFEHAVEN_BASE_URL'),
        'client_id' => env('SAFEHAVEN_CLIENT_ID'),
        'client_assertion' => env('SAFEHAVEN_CLIENT_ASSERTION'),
        'timeout' => env('SAFEHAVEN_TIMEOUT', 30),
        'retries' => env('SAFEHAVEN_RETRIES', 3),
        'accounts' => str_contains(env('SAFEHAVEN_BASE_URL', ''), 'sandbox') ?
            [
                'main' => env('SAFEHAVEN_ACCOUNT_MAIN'),
                'operations' => env('SAFEHAVEN_ACCOUNT_OPERATIONS'),
                'deposit' => env('SAFEHAVEN_ACCOUNT_DEPOSIT'),
            ] :
            [
                'operations' => env('SAFEHAVEN_ACCOUNT_OPERATIONS'),
                'deposit' => env('SAFEHAVEN_ACCOUNT_DEPOSIT'),
                'main' => env('SAFEHAVEN_ACCOUNT_MAIN'),
            ],
        'database_slug' => 'safehaven',
    ],

    'tenmg' => [
        'public' => env('TENMG_PUBKEY'),
        'secret' => env('TENMG_SECKEY'),
        'url' => env('TENMG_BASE_URL', 'https://staging-api.10mg.ai'),
    ],

    'google' => [
        'oauth_url' => env('GOOGLE_OAUTH2_URL'),
    ],

    'mono' => [
        'secret_key' => env('MONO_SEC_KEY'),
        'prove_secret_key' => env('MONO_PROVE_SEC_KEY'),
        'base_url' => env('MONO_BASE_URL', 'https://api.withmono.com'),
        'default_provider' => env('MONO_DEFAULT_PROVIDER', 'crc'),
    ],

    'tenmg_credit' => [
        'sdk_base_url' => env('TENMG_CREDIT_SDK_BASE_URL', env('APP_URL')),
    ],
];
