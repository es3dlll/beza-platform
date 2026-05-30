<?php

declare(strict_types=1);

namespace Modules\Remittance\Exceptions;

use Exception;

final class RemittanceCorridorUnavailableException extends Exception
{
    public function __construct(string $corridorId)
    {
        parent::__construct("Remittance corridor unavailable: {$corridorId}");
    }
}
