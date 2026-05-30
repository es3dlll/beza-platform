<?php

declare(strict_types=1);

namespace Modules\Remittance\Events;

use Illuminate\Foundation\Events\Dispatchable;

class RemittanceOrderRefunded
{
    use Dispatchable;

    public function __construct(
        public readonly string $remittanceOrderId,
        public readonly int $refundAmount,
        public readonly string $reason,
    ) {}
}
