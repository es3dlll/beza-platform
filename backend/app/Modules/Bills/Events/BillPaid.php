<?php

declare(strict_types=1);

namespace Modules\Bills\Events;

use Illuminate\Foundation\Events\Dispatchable;

class BillPaid
{
    use Dispatchable;

    public function __construct(
        public readonly string $billPaymentId,
        public readonly string $userId,
        public readonly string $providerId,
        public readonly int $amount,
        public readonly int $feeAmount,
    ) {}
}
