<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Events;

use App\Modules\Ledger\Models\LedgerEntry;
use App\Modules\Remittance\Models\Remittance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class RemittanceCompleted
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Remittance $remittance,
        public LedgerEntry $ledgerEntry,
        public string $status = 'completed',
    ) {}
}
