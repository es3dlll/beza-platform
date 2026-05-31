<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Exceptions;

final class InvalidRiskScoreException extends ComplianceException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
