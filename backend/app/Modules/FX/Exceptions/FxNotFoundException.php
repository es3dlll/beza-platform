<?php

declare(strict_types=1);

namespace Modules\FX\Exceptions;

use Exception;

final class FxNotFoundException extends Exception
{
    public function __construct(string $id, string $type = 'conversion')
    {
        parent::__construct("{$type} not found: {$id}");
    }
}
