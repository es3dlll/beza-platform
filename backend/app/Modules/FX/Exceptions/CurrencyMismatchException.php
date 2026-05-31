<?php

declare(strict_types=1);

namespace App\Modules\Fx\Exceptions;

final class CurrencyMismatchException extends FxException
{
    public function __construct(string $message = 'Currency mismatch in conversion', int $code = 5004)
    {
        parent::__construct($message, $code);
    }
}
