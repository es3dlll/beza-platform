<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Exceptions;

final class AutoBlockTriggeredException extends ComplianceException
{
    public function __construct(string $accountId, string $reason)
    {
        parent::__construct("Account {$accountId} auto-blocked: {$reason}");
    }
}
