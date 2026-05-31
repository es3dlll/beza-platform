<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Exceptions;

final class InvalidStateTransitionException extends FinancialCoreException
{
    public function __construct(string $message = 'Invalid state transition', int $code = 2002)
    {
        parent::__construct($message, $code);
    }
}
