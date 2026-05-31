<?php

declare(strict_types=1);

namespace App\Modules\Agent\Exceptions;

final class AgentNotActiveException extends AgentException
{
    public function __construct(string $message = 'Agent is not active', int $code = 4002)
    {
        parent::__construct($message, $code);
    }
}
