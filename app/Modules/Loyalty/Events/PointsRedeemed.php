<?php

declare(strict_types=1);

namespace Modules\Loyalty\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PointsRedeemed
{
    use Dispatchable;

    public function __construct(
        public readonly string $userId,
        public readonly int $points,
        public readonly int $newBalance,
        public readonly ?string $rewardId,
    ) {}
}
