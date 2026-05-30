<?php

declare(strict_types=1);

namespace Modules\Bills\Exceptions;

use Exception;

final class BillInvalidAmountException extends Exception
{
    public function __construct(int $expected, int $actual)
    {
        parent::__construct("Bill amount mismatch: expected {$expected}, got {$actual}");
    }
}
