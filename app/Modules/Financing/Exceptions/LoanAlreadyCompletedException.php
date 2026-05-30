<?php

declare(strict_types=1);

namespace Modules\Financing\Exceptions;

use Exception;

class LoanAlreadyCompletedException extends Exception
{
    public function __construct() { parent::__construct('Loan is already completed'); }
}
