<?php

declare(strict_types=1);

namespace App\Modules\Fx\Exceptions;

use RuntimeException;

class FxException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 5000, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
