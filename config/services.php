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

    'veriff' => [
        'api_key' => env('VERIFF_API_KEY'),
        'secret_key' => env('VERIFF_SECRET_KEY'),
        'environment' => env('VERIFF_ENVIRONMENT', 'sandbox'), // 'sandbox' or 'production'
    ],

    'game_api' => [
        'url' => env('GAME_API_URL', 'https://spy.winlottery9.com/login'),
        'timeout' => env('GAME_API_TIMEOUT', 10),
        'retry_attempts' => env('GAME_API_RETRY', 3),
    ],

];
