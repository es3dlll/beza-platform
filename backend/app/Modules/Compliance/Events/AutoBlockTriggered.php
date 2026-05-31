<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Events;

final readonly class AutoBlockTriggered
{
    public function __construct(
        public string $accountId,
        public string $reason,
        public int $riskScore,
        public int $timestamp,
    ) {}
}
