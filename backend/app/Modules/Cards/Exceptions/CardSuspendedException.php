<?php

declare(strict_types=1);

namespace Modules\Cards\Exceptions;

use Exception;

final class CardSuspendedException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Card is suspended or blocked: {$id}");
    }
}
