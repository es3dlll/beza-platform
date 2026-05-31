<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Exceptions;

final class InvalidSettlementCycleException extends MerchantException
{
    public function __construct(string $cycle)
    {
        parent::__construct("Invalid settlement cycle: {$cycle}");
    }
}
