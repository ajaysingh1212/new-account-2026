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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'nvidia' => [
        'key' => env('NVIDIA_API_KEY'),
        'model' => env('NVIDIA_MODEL', 'nvidia/nemotron-3.5-super-vl-120b-a12b'),
        'base_url' => env('NVIDIA_API_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
        'timeout' => (int) env('NVIDIA_API_TIMEOUT', 120),
        'ca_bundle' => base_path(env('NVIDIA_CA_BUNDLE', 'storage/app/certs/cacert.pem')),
    ],

];
