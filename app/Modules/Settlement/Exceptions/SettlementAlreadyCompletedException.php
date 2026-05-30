<?php

declare(strict_types=1);

namespace Modules\Settlement\Exceptions;

use Exception;

final class SettlementAlreadyCompletedException extends Exception
{
    public function __construct(string $id, string $status)
    {
        parent::__construct("Settlement $id is already $status");
    }
}
