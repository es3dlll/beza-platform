<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Services;

final class EventBusHealthCheck
{
    public function __construct(
        private readonly PoisonPillMonitor $poisonMonitor,
        private readonly ConsumerRegistry $registry,
        private readonly RetryPolicy $retryPolicy,
        private readonly SchemaVersionManager $schemaManager,
    ) {}

    public function check(): array
    {
        $dlqStats = $this->poisonMonitor->countByStatus();

        return [
            'status' => $dlqStats['total'] > 50 ? 'degraded' : 'healthy',
            'consumers' => count($this->registry->getConsumers()),
            'consumer_names' => array_keys($this->registry->getConsumers()),
            'dead_letter' => $dlqStats,
            'retry_policy' => [
                'max_attempts' => $this->retryPolicy->getMaxAttempts(),
                'base_delay_seconds' => config('event_bus.retry.base_delay_seconds', 60),
                'multiplier' => config('event_bus.retry.multiplier', 2),
            ],
            'schema_version' => $this->schemaManager->getCurrentVersion(),
            'supported_versions' => $this->schemaManager->getSupportedVersions(),
        ];
    }
}
