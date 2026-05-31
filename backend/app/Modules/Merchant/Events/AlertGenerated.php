<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Events;

final readonly class AlertGenerated
{
    public function __construct(
        public string $type,
        public string $invoiceId,
        public string $details,
    ) {}
}
