<?php

declare(strict_types=1);

namespace App\Modules\Agent\Exceptions;

final class DailyLimitExceededException extends AgentException
{
    public function __construct(string $message = 'Agent daily transaction limit exceeded', int $code = 4004)
    {
        parent::__construct($message, $code);
    }
}
