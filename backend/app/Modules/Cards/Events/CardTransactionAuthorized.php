<?php

declare(strict_types=1);

namespace Modules\Cards\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class CardTransactionAuthorized
{
    use Dispatchable;

    public function __construct(
        public readonly string $transactionId,
        public readonly string $cardId,
        public readonly string $userId,
        public readonly int $amount,
        public readonly string $merchantName,
    ) {}
}
