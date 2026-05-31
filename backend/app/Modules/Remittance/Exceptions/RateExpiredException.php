<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Exceptions;

final class RateExpiredException extends RemittanceException
{
    public function __construct(string $remittanceId)
    {
        parent::__construct("Exchange rate expired for remittance {$remittanceId}");
    }
}
