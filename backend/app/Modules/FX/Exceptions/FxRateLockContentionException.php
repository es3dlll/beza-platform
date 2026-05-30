<?php

declare(strict_types=1);

namespace Modules\FX\Exceptions;

use Exception;

final class FxRateLockContentionException extends Exception
{
    public function __construct(string $quoteId)
    {
        parent::__construct("Rate lock contention: quote {$quoteId} already consumed");
    }
}
