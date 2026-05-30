<?php

declare(strict_types=1);

namespace Modules\Float\Exceptions;

use Exception;

final class FloatAccountNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Float account not found: $id");
    }
}
