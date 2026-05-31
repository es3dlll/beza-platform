<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Events;

final readonly class FXRateLocked
{
    public function __construct(
        public string $remittanceId,
        public string $fromCurrency,
        public string $toCurrency,
        public int $buyRate,
        public int $sellRate,
        public int $spreadBps,
        public int $expiresAt,
    ) {}
}
