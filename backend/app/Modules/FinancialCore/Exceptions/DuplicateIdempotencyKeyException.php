<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Exceptions;

final class DuplicateIdempotencyKeyException extends FinancialCoreException
{
    public function __construct(string $message = 'Duplicate idempotency key', int $code = 2003)
    {
        parent::__construct($message, $code);
    }
}
