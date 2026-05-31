<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Exceptions;

final class AnalyticsException extends \RuntimeException
{
    public function __construct(string $message, int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
