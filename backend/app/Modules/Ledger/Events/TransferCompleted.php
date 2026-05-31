<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Events;

use App\Modules\Core\ValueObjects\Money;
use App\Modules\Ledger\Models\LedgerEntry;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class TransferCompleted
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public LedgerEntry $entry,
        public string $fromUserId,
        public string $toUserId,
        public Money $amount,
        public ?string $requestId = null,
    ) {}
}
