<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Exceptions;

final class HoldNotFoundException extends FinancialCoreException
{
    public function __construct(string $message = 'Hold not found', int $code = 2005)
    {
        parent::__construct($message, $code);
    }
}
