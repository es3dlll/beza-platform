<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Events;

final readonly class AccountTemporarilySuspended
{
    public function __construct(
        public string $accountId,
        public string $reason,
        public int $timestamp,
    ) {}
}
