<?php

return [
    'plisio' => [
        'secret_key' => env('PLISIO_SECRET_KEY'),
        'api_url' => env('PLISIO_API_URL', 'https://api.plisio.net/api/v1'),
        'callback_url' => env('PLISIO_CALLBACK_URL'),
        'timeout' => env('PLISIO_TIMEOUT', 30),
    ],
];
