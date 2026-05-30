<?php

declare(strict_types=1);

namespace Modules\Savings\Exceptions;

use Exception;

class SavingsGoalCompletedException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Savings goal already completed: {$id}");
    }
}
