<?php

declare(strict_types=1);

namespace App\Modules\Agent\Exceptions;

use RuntimeException;

class AgentException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 4000, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
