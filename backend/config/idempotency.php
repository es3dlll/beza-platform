<?php

return [
    'enabled' => env('IDEMPOTENCY_ENABLED', true),
    'ttl' => env('IDEMPOTENCY_TTL', 86400),
    'header' => 'Idempotency-Key',
    'store' => env('IDEMPOTENCY_STORE', 'redis'),
];
