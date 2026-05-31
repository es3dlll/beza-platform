<?php

declare(strict_types=1);

namespace App\Modules\Agent\Events;

final readonly class FloatUpdated
{
    public function __construct(
        public string $agentId,
        public int $newBalance,
        public int $previousBalance,
        public int $change,
        public string $reason,
        public int $timestamp,
    ) {}
}
