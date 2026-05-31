<?php

declare(strict_types=1);

namespace App\Modules\Agent\Events;

final readonly class LowFloatWarning
{
    public function __construct(
        public string $agentId,
        public int $availableBalance,
        public int $minimumRequired,
        public int $timestamp,
    ) {}
}
