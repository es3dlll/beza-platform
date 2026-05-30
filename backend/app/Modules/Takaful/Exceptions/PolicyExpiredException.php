<?php

declare(strict_types=1);

namespace Modules\Takaful\Exceptions;

use Exception;

final class PolicyExpiredException extends Exception
{
    public function __construct(string $policyId)
    {
        parent::__construct("Takaful policy {$policyId} is expired or not active");
    }
}
