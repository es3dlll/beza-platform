<?php

declare(strict_types=1);

namespace Modules\Remittance\Events;

use Illuminate\Foundation\Events\Dispatchable;

class RemittanceOrderCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly string $remittanceOrderId,
        public readonly string $beneficiaryId,
        public readonly int $payoutAmount,
    ) {}
}
