<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Events;

final readonly class InvoiceCreated
{
    public function __construct(
        public string $invoiceId,
        public string $merchantId,
        public int $amount,
        public int $taxAmount,
        public int $totalAmount,
        public string $qrToken,
    ) {}
}
