<?php

declare(strict_types=1);

namespace App\Modules\Fx\Exceptions;

final class RateNotFoundException extends FxException
{
    public function __construct(string $message = 'Exchange rate not found for currency pair', int $code = 5001)
    {
        parent::__construct($message, $code);
    }
}
