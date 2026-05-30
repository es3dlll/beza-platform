<?php

declare(strict_types=1);

namespace Modules\FX\Exceptions;

use Exception;

final class FxMarginTooHighException extends Exception
{
    public function __construct(float $marginPct, float $maxAllowed)
    {
        parent::__construct("Margin {$marginPct}% exceeds maximum allowed {$maxAllowed}%");
    }
}
