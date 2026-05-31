<?php

declare(strict_types=1);

namespace App\Modules\Agent\Exceptions;

final class CommissionCalculationFailedException extends AgentException
{
    public function __construct(string $detail)
    {
        parent::__construct("Commission calculation failed: {$detail}");
    }
}
