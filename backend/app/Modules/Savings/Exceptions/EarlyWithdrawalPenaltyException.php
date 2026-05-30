<?php

declare(strict_types=1);

namespace Modules\Savings\Exceptions;

use Exception;

class EarlyWithdrawalPenaltyException extends Exception
{
    public function __construct(int $penaltyAmount)
    {
        parent::__construct("Early withdrawal penalty of {$penaltyAmount} SYP applies");
    }
}
