<?php

declare(strict_types=1);

namespace Modules\Loyalty\Exceptions;

use Exception;

final class InsufficientPointsException extends Exception
{
    public function __construct(int $required, int $available)
    {
        parent::__construct("Insufficient points: required {$required}, available {$available}");
    }
}
