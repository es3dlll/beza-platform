<?php

declare(strict_types=1);

namespace Modules\FX\Exceptions;

use Exception;

final class FxRateUnavailableException extends Exception
{
    public function __construct(string $base, string $quote, string $rateType = 'cbs_official')
    {
        parent::__construct("Exchange rate unavailable for {$base}/{$quote} ({$rateType})");
    }
}
