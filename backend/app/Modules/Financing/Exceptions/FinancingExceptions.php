<?php

declare(strict_types=1);

namespace Modules\Financing\Exceptions;

use Exception;

final class LoanProductNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Loan product not found: {$id}"); }
}
final class LoanNotApprovedException extends Exception
{
    public function __construct() { parent::__construct('Loan is not in approved status'); }
}
final class LoanAlreadyCompletedException extends Exception
{
    public function __construct() { parent::__construct('Loan is already completed'); }
}
final class RepaymentAmountExceedsBalanceException extends Exception
{
    public function __construct() { parent::__construct('Repayment exceeds outstanding balance'); }
}
final class LoanUnderReviewException extends Exception
{
    public function __construct(string $id) { parent::__construct("Loan {$id} is still under review"); }
}
final class LoanAlreadyDefaultedException extends Exception
{
    public function __construct(string $id) { parent::__construct("Loan {$id} is already defaulted"); }
}
final class CreditScoreTooLowException extends Exception
{
    public function __construct(int $score, int $threshold) { parent::__construct("Credit score {$score} is below threshold {$threshold}"); }
}
final class InstallmentNotDueException extends Exception
{
    public function __construct() { parent::__construct('Installment is not yet due'); }
}
final class BnplMerchantNotConfiguredException extends Exception
{
    public function __construct() { parent::__construct('Merchant does not support BNPL'); }
}
