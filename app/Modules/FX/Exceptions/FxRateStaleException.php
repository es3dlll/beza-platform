<?php

declare(strict_types=1);

namespace Modules\FX\Exceptions;

use Exception;

final class FxRateStaleException extends Exception
{
    public function __construct(
        public readonly string $pair,
        public readonly int $staleMinutes,
    ) {
        parent::__construct("Rate feed for {$pair} is stale ({$staleMinutes} minutes old)");
    }
}
