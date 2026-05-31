<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Exceptions;

final class InvalidCurrencyPairException extends RemittanceException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct("Invalid currency pair: {$from} → {$to}");
    }
}
