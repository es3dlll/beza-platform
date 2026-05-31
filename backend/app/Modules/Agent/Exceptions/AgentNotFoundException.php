<?php

declare(strict_types=1);

namespace App\Modules\Agent\Exceptions;

final class AgentNotFoundException extends AgentException
{
    public function __construct(string $message = 'Agent not found', int $code = 4001)
    {
        parent::__construct($message, $code);
    }
}
