<?php

declare(strict_types=1);

namespace Modules\Bills\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class BillPaymentFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string $billPaymentId,
        public readonly string $reason,
    ) {}
}
