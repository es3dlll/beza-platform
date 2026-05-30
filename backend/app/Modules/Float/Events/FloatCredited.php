<?php

declare(strict_types=1);

namespace Modules\Float\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FloatCredited
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $floatAccountId,
        public readonly string $ownerType,
        public readonly string $ownerId,
        public readonly string $floatType,
        public readonly int $amount,
        public readonly int $newBalance,
    ) {}
}
