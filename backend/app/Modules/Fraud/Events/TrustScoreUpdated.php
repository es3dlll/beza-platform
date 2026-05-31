<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class TrustScoreUpdated
{
    use Dispatchable;

    public function __construct(
        public readonly string $deviceFingerprintId,
        public readonly string $walletId,
        public readonly int $oldScore,
        public readonly int $newScore,
        public readonly int $delta,
    ) {}
}
