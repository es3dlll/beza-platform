<?php

declare(strict_types=1);

return [
    'checks' => [
        'database' => [
            'enabled' => true,
            'timeout' => 5,
        ],
        'queue' => [
            'enabled' => true,
            'timeout' => 5,
        ],
        'storage' => [
            'enabled' => true,
            'disks' => ['local'],
        ],
        'cache' => [
            'enabled' => true,
        ],
    ],
    'response_ttl' => 60,
];
