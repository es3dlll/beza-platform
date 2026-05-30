<?php

declare(strict_types=1);

namespace Modules\Loyalty\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class CashbackApplied
{
    use Dispatchable;

    public function __construct(
        public readonly string $userId,
        public readonly int $cashbackAmount,
        public readonly int $transactionAmount,
        public readonly string $rule,
    ) {}
}
