<?php

declare(strict_types=1);

namespace App\Modules\Agent\Exceptions;

final class InvalidAgentStateException extends AgentException
{
    public function __construct(string $agentId, string $currentState, string $expectedState)
    {
        parent::__construct("Agent {$agentId} is in state '{$currentState}', expected '{$expectedState}'");
    }
}
