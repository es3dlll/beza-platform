<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Exceptions;

final class ComplianceBlockedException extends RemittanceException
{
    public function __construct(string $message = 'Transfer blocked by compliance screening')
    {
        parent::__construct($message);
    }
}
