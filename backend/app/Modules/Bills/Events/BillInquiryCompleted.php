<?php

declare(strict_types=1);

namespace Modules\Bills\Events;

use Illuminate\Foundation\Events\Dispatchable;

class BillInquiryCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly string $billPaymentId,
        public readonly string $userId,
        public readonly string $providerId,
        public readonly string $accountNumber,
        public readonly int $amountDue,
    ) {}
}
