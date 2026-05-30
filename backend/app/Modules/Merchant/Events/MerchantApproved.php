<?php

declare(strict_types=1);

namespace Modules\Merchant\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class MerchantApproved
{
    use Dispatchable;

    public function __construct(
        public readonly string $merchantId,
        public readonly string $approvedBy,
    ) {}
}
