<?php

declare(strict_types=1);

namespace App\Modules\Agent\Events;

final readonly class CommissionReadyForPayout
{
    public function __construct(
        public string $agentId,
        public int $totalCommission,
        public string $currency,
        public int $timestamp,
    ) {}
}
