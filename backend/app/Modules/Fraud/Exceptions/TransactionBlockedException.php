<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Exceptions;

final class TransactionBlockedException extends FraudException
{
    public function __construct(
        string $message = 'Transaction blocked by fraud prevention',
        int $code = 6001,
        public readonly int $score = 0,
        public readonly string $reason = '',
    ) {
        parent::__construct($message, $code);
    }
}
