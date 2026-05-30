<?php

declare(strict_types=1);

namespace Modules\Takaful\Exceptions;

use Exception;

final class TakafulProductNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Takaful product not found: {$id}");
    }
}
