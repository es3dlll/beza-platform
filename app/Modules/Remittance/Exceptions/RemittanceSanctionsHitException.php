<?php

declare(strict_types=1);

namespace Modules\Remittance\Exceptions;

use Exception;

final class RemittanceSanctionsHitException extends Exception
{
    public function __construct()
    {
        parent::__construct('Transaction flagged by sanctions screening');
    }
}
