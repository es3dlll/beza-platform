<?php

declare(strict_types=1);

namespace Modules\Agent\Exceptions;

use Exception;

final class AgentFloatInsufficientException extends Exception
{
    public function __construct(
        public readonly int $balance,
        public readonly int $required,
    ) {
        parent::__construct("Agent float insufficient: balance $balance, required $required");
    }
}
