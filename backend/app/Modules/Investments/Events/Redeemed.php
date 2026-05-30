<?php

declare(strict_types=1);

namespace Modules\Investments\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class Redeemed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $subscriptionId,
        public readonly string $userId,
        public readonly string $fundId,
        public readonly int $units,
        public readonly int $amount,
    ) {}
}
