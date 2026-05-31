<?php

declare(strict_types=1);

return [
    'paths' => ['api/*', 'up', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => env('CORS_ALLOWED_ORIGINS')
        ? explode(',', env('CORS_ALLOWED_ORIGINS'))
        : [env('APP_URL', 'http://localhost')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'X-Request-Id', 'Authorization', 'Accept', 'X-Requested-With'],
    'exposed_headers' => ['X-Request-Id', 'X-RateLimit-Remaining'],
    'max_age' => 86400,
    'supports_credentials' => false,
];
