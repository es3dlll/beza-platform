<?php

declare(strict_types=1);

namespace Modules\Financing\Exceptions;

use Exception;

final class RepaymentAmountExceedsBalanceException extends Exception
{
    public function __construct() { parent::__construct('Repayment exceeds outstanding balance'); }
}
