<?php

declare(strict_types=1);

namespace App\Modules\Bills\Events;

use App\Modules\Bills\Models\Bill;
use App\Modules\Ledger\Models\LedgerEntry;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class BillPaymentCompleted
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Bill $bill,
        public LedgerEntry $ledgerEntry,
        public string $receiptReference,
    ) {}
}
