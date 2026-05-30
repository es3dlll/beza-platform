<?php

declare(strict_types=1);

namespace Modules\Financing\Exceptions;

use Exception;

final class LoanNotApprovedException extends Exception
{
    public function __construct() { parent::__construct('Loan is not in approved status'); }
}
