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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'tap' => [
        'secret_key' => env('TAP_PAYMENT_SECRET_KEY'),
        'publishable_key' => env('TAP_PAYMENT_PUBLISHABLE_KEY'),
        'callback_url' => env('TAP_PAYMENT_CALLBACK_URL', rtrim(env('APP_URL', ''), '/') . '/payment/callback'),
        'webhook_url' => env('TAP_PAYMENT_WEBHOOK_URL', rtrim(env('APP_URL', ''), '/') . '/payment/webhook'),
    ],

];
