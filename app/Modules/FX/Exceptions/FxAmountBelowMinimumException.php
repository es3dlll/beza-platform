<?php

declare(strict_types=1);

namespace Modules\FX\Exceptions;

use Exception;

final class FxAmountBelowMinimumException extends Exception
{
    public function __construct(int $amount, int $minimum)
    {
        parent::__construct("Amount {$amount} below minimum {$minimum} for FX conversion");
    }
}
