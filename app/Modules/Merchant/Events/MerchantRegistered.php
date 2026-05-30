<?php

declare(strict_types=1);

namespace Modules\Merchant\Events;

use Illuminate\Foundation\Events\Dispatchable;

class MerchantRegistered
{
    use Dispatchable;

    public function __construct(
        public readonly string $merchantId,
        public readonly string $userId,
        public readonly string $businessName,
    ) {}
}
