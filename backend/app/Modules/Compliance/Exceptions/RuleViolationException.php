<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Exceptions;

final class RuleViolationException extends ComplianceException
{
    public function __construct(string $ruleId, string $detail)
    {
        parent::__construct("Rule {$ruleId} violated: {$detail}");
    }
}
