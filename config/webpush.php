<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VAPID Configuration
    |--------------------------------------------------------------------------
    |
    | Voluntary Application Server Identification (VAPID) allows push services
    | to identify your application server and associate it with your push
    | subscription endpoints.
    |
    */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@yourapp.com'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Cloud Messaging (Legacy)
    |--------------------------------------------------------------------------
    |
    | Legacy GCM configuration. Most applications should use FCM instead.
    |
    */
    'gcm' => [
        'key' => env('GCM_KEY'),
        'sender_id' => env('GCM_SENDER_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging
    |--------------------------------------------------------------------------
    |
    | FCM configuration for Google/Android push notifications.
    |
    */
    'fcm' => [
        'key' => env('FCM_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Notification Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for push notifications.
    |
    */
    'defaults' => [
        'ttl' => 3600, // Time to live in seconds
        'urgency' => 'normal', // very-low, low, normal, high
        'topic' => 'general', // For message coalescing
        'icon' => '/images/icons/192.png',
        'badge' => '/images/icons/72.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | Browser Support
    |--------------------------------------------------------------------------
    |
    | Configuration for different browser push services.
    |
    */
    'browsers' => [
        'chrome' => [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send',
        ],
        'firefox' => [
            'endpoint' => 'https://updates.push.services.mozilla.com',
        ],
        'safari' => [
            'endpoint' => 'https://web.push.apple.com',
        ],
    ],
];