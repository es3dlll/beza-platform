<?php

declare(strict_types=1);

namespace Modules\Payroll\Exceptions;

use Exception;

final class PayrollBatchNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Payroll batch not found: {$id}");
    }
}
