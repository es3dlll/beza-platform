<?php

declare(strict_types=1);

namespace Modules\Fraud\Exceptions;

use Exception;

class FraudReviewRequiredException extends Exception
{
    public function __construct(string $reason = 'Transaction flagged for manual fraud review')
    {
        parent::__construct($reason);
    }
}
