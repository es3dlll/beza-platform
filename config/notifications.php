<?php

return [
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'api_key' => env('SMS_API_KEY'),
        'sender_id' => env('SMS_SENDER_ID', 'Beza'),
        'syriatel' => [
            'host' => env('SMS_SYRIATEL_HOST', 'smpp.syriatel.sy'),
            'port' => env('SMS_SYRIATEL_PORT', 2775),
            'username' => env('SMS_SYRIATEL_USERNAME'),
            'password' => env('SMS_SYRIATEL_PASSWORD'),
        ],
        'mtn' => [
            'host' => env('SMS_MTN_HOST', 'smpp.mtn.com.sy'),
            'port' => env('SMS_MTN_PORT', 2775),
            'username' => env('SMS_MTN_USERNAME'),
            'password' => env('SMS_MTN_PASSWORD'),
        ],
    ],
];
