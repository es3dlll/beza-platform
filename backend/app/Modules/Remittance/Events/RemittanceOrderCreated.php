<?php

declare(strict_types=1);

namespace Modules\Remittance\Events;

use Illuminate\Foundation\Events\Dispatchable;

class RemittanceOrderCreated
{
    use Dispatchable;

    public function __construct(
        public readonly string $remittanceOrderId,
        public readonly string $corridorId,
        public readonly string $senderUserId,
        public readonly string $beneficiaryId,
        public readonly int $sourceAmount,
        public readonly string $sourceCurrency,
        public readonly int $targetAmount,
        public readonly string $purposeCode,
    ) {}
}
