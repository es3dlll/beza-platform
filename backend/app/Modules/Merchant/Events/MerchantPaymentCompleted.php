<?php

declare(strict_types=1);

namespace Modules\Merchant\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class MerchantPaymentCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly string $paymentId,
        public readonly string $merchantId,
        public readonly string $payerUserId,
        public readonly int $amount,
        public readonly int $mdrFee,
        public readonly int $netAmount,
    ) {}
}
