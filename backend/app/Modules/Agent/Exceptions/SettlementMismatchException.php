<?php

declare(strict_types=1);

namespace App\Modules\Agent\Exceptions;

final class SettlementMismatchException extends AgentException
{
    public function __construct(string $agentId, string $date, int $expected, int $actual)
    {
        parent::__construct(
            "Settlement mismatch for agent {$agentId} on {$date}: expected {$expected}, actual {$actual}"
        );
    }
}
