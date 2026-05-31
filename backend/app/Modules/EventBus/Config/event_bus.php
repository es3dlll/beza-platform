<?php

return [
    'exchanges' => [
        'financial' => [
            'name' => 'beza.financial',
            'type' => 'topic',
            'queues' => [
                'cfe.events' => 'financial.core.*',
                'agent.events' => 'financial.agent.*',
                'fx.events' => 'financial.fx.*',
                'fraud.monitor' => 'financial.fraud.*',
                'audit.log' => 'financial.#',
            ],
        ],
    ],

    'consumers' => [
        'velocity_update' => [
            'handler' => \App\Modules\EventBus\Consumers\VelocityUpdateConsumer::class,
            'events' => ['financial_core.transaction_posted'],
        ],
        'audit_log' => [
            'handler' => \App\Modules\EventBus\Consumers\AuditLogConsumer::class,
            'events' => ['financial_core.#', 'financial.agent.#', 'financial.fx.#', 'financial.fraud.#'],
        ],
    ],

    'retry' => [
        'max_attempts' => 3,
        'base_delay_seconds' => 60,
        'multiplier' => 2,
    ],

    'idempotency' => [
        'ttl_seconds' => 86400,
    ],

    'schema' => [
        'current_version' => 'v1',
        'supported_versions' => ['v1', 'v2'],
    ],
];
