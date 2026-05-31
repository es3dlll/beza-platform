<?php

declare(strict_types=1);

namespace App\Modules\Escrow\Events;

use App\Modules\Escrow\Models\DisputeCase;
use App\Modules\Escrow\Models\EscrowTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class EscrowDisputed
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public EscrowTransaction $transaction,
        public DisputeCase $dispute,
    ) {}
}
