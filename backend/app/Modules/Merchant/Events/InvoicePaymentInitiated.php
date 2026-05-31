<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Events;

final readonly class InvoicePaymentInitiated
{
    public function __construct(
        public string $invoiceId,
        public string $qrToken,
        public string $payeeId,
    ) {}
}
