<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Events;

final readonly class InvoicePaid
{
    public function __construct(
        public string $invoiceId,
        public string $merchantId,
        public int $amount,
        public int $taxAmount,
        public int $commissionAmount,
        public int $netAmount,
        public string $paidAt,
    ) {}
}
