<?php

use Laravel\Socialite\Two\FacebookProvider;

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


    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'), // اسم المتغير من ملف `.env`
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', 'https://sahelnaha.systems/auth/facebook/callback'),
    ],
    'technician_google' => [
        'client_id' => env('TECHNICIAN_GOOGLE_CLIENT_ID'),
        'client_secret' => env('TECHNICIAN_GOOGLE_CLIENT_SECRET'),
        'redirect' => env('TECHNICIAN_GOOGLE_REDIRECT_URI'),
    ],

    'technician_facebook' => [
        'client_id' => env('TECHNICIAN_FACEBOOK_CLIENT_ID'),
        'client_secret' => env('TECHNICIAN_FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('TECHNICIAN_FACEBOOK_REDIRECT_URI'),
    ],

    'firebase' => [
        'credentials' => storage_path('app/firebase/sahelnaha-notifications-firebase.json'),
    ],
    'paymob' => [
        'api_key' => env('PAYMOB_API_KEY'),
        'base_url' => env('PAYMOB_BASE_URL'),
        'vodafone_cash_integration_id' => env('PAYMOB_VODAFONE_CASH_INTEGRATION_ID'),
        'card_payment_integration_id' => env('PAYMOB_CARD_PAYMENT_INTEGRATION_ID'),
        //'iframe_id' => env('PAYMOB_IFRAME_ID'),
        'vodafone_cash_iframe_id' => env('PAYMOB_VODAFONE_IFRAME_ID'),
        'card_iframe_id' => env('PAYMOB_CARD_IFRAME_ID'),
        'iframe_base_url' => 'https://accept.paymob.com/api/acceptance/iframes/',
        'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
    ],
];
