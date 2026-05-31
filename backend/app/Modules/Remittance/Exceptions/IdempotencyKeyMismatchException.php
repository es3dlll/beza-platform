<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Exceptions;

final class IdempotencyKeyMismatchException extends RemittanceException
{
    public function __construct(string $key)
    {
        parent::__construct("Idempotency key conflict: {$key}");
    }
}
