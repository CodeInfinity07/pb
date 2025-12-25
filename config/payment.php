<?php

return [
    'coinments' => [
        'secret_key' => env('COINMENTS_SECRET_KEY'),
        'api_url' => env('COINMENTS_API_URL', 'https://gateway.predictor.guru'),
        'testnet' => env('COINMENTS_TESTNET', false),
        'timeout' => env('COINMENTS_TIMEOUT', 30),
    ],
];