<?php

declare(strict_types=1);

namespace Modules\Cards\Exceptions;

use Exception;

class CardLimitExceededException extends Exception
{
    public function __construct(string $limitType, int $limit, int $attempted)
    {
        parent::__construct("Card {$limitType} limit exceeded: limit {$limit}, attempted {$attempted}");
    }
}
