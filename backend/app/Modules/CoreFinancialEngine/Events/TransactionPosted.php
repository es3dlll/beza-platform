<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TransactionPosted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $transactionId,
        public readonly string $referenceType,
        public readonly string $referenceId,
        public readonly int $totalAmount,
        public readonly string $currency,
        public readonly string $channel,
    ) {}
}
