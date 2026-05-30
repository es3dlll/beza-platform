<?php

declare(strict_types=1);

namespace Modules\FX\Exceptions;

use Exception;

final class FxRateExpiredException extends Exception
{
    public function __construct(string $quoteId)
    {
        parent::__construct("Rate quote {$quoteId} has expired");
    }
}
