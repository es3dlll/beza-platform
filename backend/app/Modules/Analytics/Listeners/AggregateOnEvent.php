<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Listeners;

use App\Modules\Analytics\Services\AnalyticsAggregator;

final class AggregateOnEvent
{
    public function __construct(
        private readonly AnalyticsAggregator $aggregator,
    ) {}

    public function handle(object $event): void
    {
        $this->aggregator->aggregateDaily(now()->toDateString());
    }
}
