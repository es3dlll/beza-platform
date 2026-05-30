<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TransactionReversed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $originalTransactionId,
        public readonly string $reversalTransactionId,
        public readonly string $reason,
        public readonly string $initiatedBy,
    ) {}
}
