<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Exceptions;

final class InsufficientBalanceException extends FinancialCoreException
{
    public function __construct(string $message = 'Insufficient balance', int $code = 2001)
    {
        parent::__construct($message, $code);
    }
}
