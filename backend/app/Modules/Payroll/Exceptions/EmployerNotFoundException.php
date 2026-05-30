<?php

declare(strict_types=1);

namespace Modules\Payroll\Exceptions;

use Exception;

final class EmployerNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Employer not found: {$id}");
    }
}
