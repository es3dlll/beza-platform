<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Exceptions;

final class CannotReverseException extends FinancialCoreException
{
    public function __construct(string $message = 'Cannot reverse this transaction', int $code = 2006)
    {
        parent::__construct($message, $code);
    }
}
