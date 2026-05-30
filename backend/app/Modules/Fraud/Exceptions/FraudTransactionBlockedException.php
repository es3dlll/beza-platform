<?php

declare(strict_types=1);

namespace Modules\Fraud\Exceptions;

use Exception;

final class FraudTransactionBlockedException extends Exception
{
    public function __construct(string $reason = 'Transaction automatically blocked by fraud detection')
    {
        parent::__construct($reason);
    }
}
