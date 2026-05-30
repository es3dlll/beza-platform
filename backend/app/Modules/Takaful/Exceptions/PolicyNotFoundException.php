<?php

declare(strict_types=1);

namespace Modules\Takaful\Exceptions;

use Exception;

final class PolicyNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Takaful policy not found: {$id}");
    }
}
