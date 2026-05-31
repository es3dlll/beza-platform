<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Exceptions;

final class TransactionNotFoundException extends FinancialCoreException
{
    public function __construct(string $message = 'Transaction not found', int $code = 2004)
    {
        parent::__construct($message, $code);
    }
}
