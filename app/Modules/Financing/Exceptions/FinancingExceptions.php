<?php

declare(strict_types=1);

namespace Modules\Financing\Exceptions;

use Exception;

class LoanProductNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Loan product not found: {$id}"); }
}
class LoanNotApprovedException extends Exception
{
    public function __construct() { parent::__construct('Loan is not in approved status'); }
}
class LoanAlreadyCompletedException extends Exception
{
    public function __construct() { parent::__construct('Loan is already completed'); }
}
class RepaymentAmountExceedsBalanceException extends Exception
{
    public function __construct() { parent::__construct('Repayment exceeds outstanding balance'); }
}
