<?php
declare(strict_types=1);

namespace Modules\Ledger\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class HoldPlaced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $holdId,
        public readonly string $accountId,
        public readonly int $amount,
        public readonly string $reason,
        public readonly string $referenceType,
        public readonly string $referenceId,
    ) {}
}
