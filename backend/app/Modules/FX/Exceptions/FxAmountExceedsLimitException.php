<?php

declare(strict_types=1);

namespace Modules\FX\Exceptions;

use Exception;

final class FxAmountExceedsLimitException extends Exception
{
    public function __construct(
        public readonly int $amount,
        public readonly int $dailyLimit,
        public readonly int $dailyUsed,
    ) {
        parent::__construct("FX amount {$amount} exceeds daily limit {$dailyLimit} (used: {$dailyUsed})");
    }
}
