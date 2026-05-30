<?php

declare(strict_types=1);

namespace Modules\Fraud\Exceptions;

use Exception;

final class FraudRapidSuccessiveTxnsException extends Exception
{
    public function __construct(int $txnCount = 0, int $windowSeconds = 60)
    {
        parent::__construct("{$txnCount} transactions in {$windowSeconds}s exceeds velocity limit");
    }
}
