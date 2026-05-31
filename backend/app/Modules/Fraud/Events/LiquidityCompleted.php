<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Events;

use App\Modules\Ledger\Models\LedgerEntry;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class LiquidityCompleted
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public string $agentId,
        public string $requestId,
        public int $amountFils,
        public LedgerEntry $ledgerEntry,
        public string $status = 'completed',
    ) {}
}
