<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Exceptions;

use RuntimeException;

class FinancialCoreException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 2000, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
