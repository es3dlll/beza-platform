<?php

declare(strict_types=1);

namespace Modules\Loyalty\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class TierUpgraded
{
    use Dispatchable;

    public function __construct(
        public readonly string $userId,
        public readonly string $oldTier,
        public readonly string $newTier,
    ) {}
}
