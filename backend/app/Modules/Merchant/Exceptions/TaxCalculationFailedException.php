<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Exceptions;

final class TaxCalculationFailedException extends MerchantException
{
    public function __construct(string $reason = 'Tax calculation error')
    {
        parent::__construct($reason);
    }
}
