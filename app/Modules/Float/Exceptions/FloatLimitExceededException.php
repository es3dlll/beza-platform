<?php

declare(strict_types=1);

namespace Modules\Float\Exceptions;

use Exception;

final class FloatLimitExceededException extends Exception
{
    public function __construct(
        public readonly string $limit,
        public readonly int $max,
        public readonly int $current,
    ) {
        parent::__construct("Float $limit exceeded: max $max, current $current");
    }
}
