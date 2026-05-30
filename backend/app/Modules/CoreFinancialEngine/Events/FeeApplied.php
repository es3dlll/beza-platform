<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FeeApplied
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $feeType,
        public readonly string $accountId,
        public readonly int $feeAmount,
        public readonly string $currency,
        public readonly string $journalEntryId,
    ) {}
}
