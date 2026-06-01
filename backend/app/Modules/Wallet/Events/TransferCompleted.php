<?php

namespace App\Modules\Wallet\Events;

use App\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;

class TransferCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly Transaction $transaction,
    ) {}
}
