<?php

declare(strict_types=1);

namespace Modules\Remittance\Exceptions;

use Exception;

final class RemittanceLimitExceededException extends Exception
{
    public function __construct(string $limitType, int $amount, int $limit)
    {
        parent::__construct("Remittance limit exceeded: {$limitType} (attempted {$amount}, limit {$limit})");
    }
}
