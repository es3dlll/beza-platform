<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Events;

final readonly class TriggerMerchantSettlement
{
    public function __construct(
        public string $merchantId,
        public string $settlementCycle,
    ) {}
}
