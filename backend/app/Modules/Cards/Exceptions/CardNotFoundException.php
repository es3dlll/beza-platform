<?php

declare(strict_types=1);

namespace Modules\Cards\Exceptions;

use Exception;

final class CardNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Card not found: {$id}");
    }
}
