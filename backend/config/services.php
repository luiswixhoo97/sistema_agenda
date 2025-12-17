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

    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL', 'https://wasenderapi.com/api'),
        'api_token' => env('WHATSAPP_API_TOKEN', 'ae02434e1d46b604bbb3190cc9aed71f1c960b6ca26f4fd898800a4cb929e45b'),
        'session_id' => env('WHATSAPP_SESSION_ID', ''),
    ],

    'firebase' => [
        'server_key' => env('FIREBASE_SERVER_KEY', 'AIzaSyBeO4Jc023gHxXV7N8D3efmtqvJ91EbCTk'),
        'fcm_url' => env('FIREBASE_FCM_URL', 'https://fcm.googleapis.com/fcm/send'),
    ],

    'mercadopago' => [
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN','APP_USR-8563970170397929-121716-aac8a8d4c33976af547881ea84989602-3074781789'),
    ],

];
