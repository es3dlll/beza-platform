<?php

declare(strict_types=1);

namespace Modules\Fraud\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class FraudDeviceBlacklisted
{
    use Dispatchable;

    public function __construct(
        public readonly string $deviceId,
        public readonly string $reason,
        public readonly string $addedBy,
    ) {}
}
