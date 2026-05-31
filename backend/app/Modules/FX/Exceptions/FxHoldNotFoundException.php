<?php

declare(strict_types=1);

namespace App\Modules\Fx\Exceptions;

final class FxHoldNotFoundException extends FxException
{
    public function __construct(string $message = 'FX hold not found', int $code = 5003)
    {
        parent::__construct($message, $code);
    }
}
