<?php

declare(strict_types=1);

namespace App\Modules\Escrow\Events;

use App\Modules\Escrow\Models\EscrowTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class EscrowFunded
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public EscrowTransaction $transaction,
    ) {}
}
