<?php

declare(strict_types=1);

namespace Modules\Settlement\Exceptions;

use Exception;

final class SettlementNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Settlement not found: $id");
    }
}
