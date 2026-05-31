<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Events;

final readonly class QRValidationFailed
{
    public function __construct(
        public string $invoiceId,
        public string $reason,
    ) {}
}
