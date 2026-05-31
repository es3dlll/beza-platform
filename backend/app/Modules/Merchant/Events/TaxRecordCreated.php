<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Events;

final readonly class TaxRecordCreated
{
    public function __construct(
        public string $invoiceId,
        public int $taxAmount,
        public string $taxCategory,
    ) {}
}
