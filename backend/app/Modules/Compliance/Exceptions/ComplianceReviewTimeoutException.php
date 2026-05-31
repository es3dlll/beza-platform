<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Exceptions;

final class ComplianceReviewTimeoutException extends ComplianceException
{
    public function __construct(string $caseId)
    {
        parent::__construct("Compliance review timeout for case {$caseId} (24h exceeded)");
    }
}
