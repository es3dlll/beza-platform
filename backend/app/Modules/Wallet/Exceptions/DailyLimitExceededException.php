<?php

declare(strict_types=1);

namespace Modules\Wallet\Exceptions;

use Exception;

final class DailyLimitExceededException extends Exception
{
    public function __construct(
        public readonly int $limit,
        public readonly int $used,
        public readonly int $requested,
    ) {
        parent::__construct("Daily limit exceeded: used $used, limit $limit, requested $requested");
    }
}
