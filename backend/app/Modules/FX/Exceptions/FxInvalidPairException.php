<?php

declare(strict_types=1);

namespace Modules\FX\Exceptions;

use Exception;

final class FxInvalidPairException extends Exception
{
    public function __construct(string $base, string $quote)
    {
        parent::__construct("Unsupported currency pair: {$base}/{$quote}");
    }
}
