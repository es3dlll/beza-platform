<?php

namespace Modules\CoreFinancialEngine\Exceptions;

use Exception;

final class ReversalNotAllowedException extends Exception
{
    public function __construct(string $reason)
    {
        parent::__construct("Reversal not allowed: $reason");
    }
}
