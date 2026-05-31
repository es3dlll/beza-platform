<?php

declare(strict_types=1);

namespace App\Modules\Agent\Exceptions;

final class InsufficientFloatException extends AgentException
{
    public function __construct(string $message = 'Insufficient agent float balance', int $code = 4003)
    {
        parent::__construct($message, $code);
    }
}
