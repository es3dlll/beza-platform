<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Contracts;

use App\Modules\EventBus\Models\EventDeliveryLog;

interface EventConsumer
{
    public function handle(string $eventType, array $payload, EventDeliveryLog $log): void;
    public function getName(): string;
}
