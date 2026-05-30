<?php

declare(strict_types=1);

namespace Modules\Remittance\Exceptions;

use Exception;

final class RemittanceSourceOfFundsRequiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('Source of funds declaration is required');
    }
}
