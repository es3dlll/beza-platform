<?php

declare(strict_types=1);

namespace App\Modules\Fx\Exceptions;

final class RateExpiredException extends FxException
{
    public function __construct(string $message = 'Exchange rate lock has expired', int $code = 5002)
    {
        parent::__construct($message, $code);
    }
}
