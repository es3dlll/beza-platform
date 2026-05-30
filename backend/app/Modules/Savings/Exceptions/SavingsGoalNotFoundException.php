<?php

declare(strict_types=1);

namespace Modules\Savings\Exceptions;

use Exception;

final class SavingsGoalNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Savings goal not found: {$id}");
    }
}
