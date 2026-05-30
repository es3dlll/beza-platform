<?php

declare(strict_types=1);

namespace Modules\Loyalty\Exceptions;

use Exception;

final class RewardNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Reward not found: {$id}");
    }
}
