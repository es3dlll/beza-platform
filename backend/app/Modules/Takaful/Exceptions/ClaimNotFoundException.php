<?php

declare(strict_types=1);

namespace Modules\Takaful\Exceptions;

use Exception;

final class ClaimNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Takaful claim not found: {$id}");
    }
}
