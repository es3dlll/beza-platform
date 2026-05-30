<?php

declare(strict_types=1);

namespace Modules\Bills\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class BillRefunded
{
    use Dispatchable;

    public function __construct(
        public readonly string $billPaymentId,
        public readonly int $amount,
        public readonly string $reason,
    ) {}
}
