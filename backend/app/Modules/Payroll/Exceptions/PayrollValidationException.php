<?php

declare(strict_types=1);

namespace Modules\Payroll\Exceptions;

use Exception;

final class PayrollValidationException extends Exception
{
    public function __construct(string $message = 'Payroll validation failed')
    {
        parent::__construct($message);
    }
}
