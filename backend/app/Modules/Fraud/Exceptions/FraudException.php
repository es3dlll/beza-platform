<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Exceptions;

use RuntimeException;

class FraudException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 6000, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
