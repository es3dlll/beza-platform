<?php

declare(strict_types=1);

namespace Modules\Cards\Events;

use Illuminate\Foundation\Events\Dispatchable;

class CardCreated
{
    use Dispatchable;

    public function __construct(
        public readonly string $cardId,
        public readonly string $userId,
        public readonly string $cardType,
        public readonly string $last4,
    ) {}
}
