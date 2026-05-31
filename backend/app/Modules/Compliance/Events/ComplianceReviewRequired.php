<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Events;

final readonly class ComplianceReviewRequired
{
    public function __construct(
        public string $transactionId,
        public string $accountId,
        public int $riskScore,
        public array $triggeredRules,
        public int $timestamp,
    ) {}
}
