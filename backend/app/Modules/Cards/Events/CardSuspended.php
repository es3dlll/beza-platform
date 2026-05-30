<?php

declare(strict_types=1);

namespace Modules\Cards\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class CardSuspended
{
    use Dispatchable;

    public function __construct(
        public readonly string $cardId,
        public readonly string $userId,
        public readonly string $reason,
    ) {}
}
