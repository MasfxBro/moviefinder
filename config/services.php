<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => ['key' => env('POSTMARK_API_KEY')],
    'resend' => ['key' => env('RESEND_API_KEY')],
    'ses' => ['key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY'), 'region' => env('AWS_DEFAULT_REGION', 'us-east-1')],
    'slack' => ['notifications' => ['bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'), 'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL')]],
    'tmdb' => ['token' => env('TMDB_READ_TOKEN'), 'image_base' => env('TMDB_IMAGE_BASE', 'https://image.tmdb.org/t/p/')],
    'gemini' => ['free_key' => env('GEMINI_API_KEY_FREE'), 'paid_key' => env('GEMINI_API_KEY_PAID')],
    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'production' => env('MIDTRANS_IS_PRODUCTION', false),
    ],
];
