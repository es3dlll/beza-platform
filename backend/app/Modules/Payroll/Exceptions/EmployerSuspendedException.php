<?php

declare(strict_types=1);

namespace Modules\Payroll\Exceptions;

use Exception;

final class EmployerSuspendedException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Employer account is suspended: {$id}");
    }
}
