<?php

declare(strict_types=1);

namespace App\Modules\Escrow\Events;

use App\Modules\Core\ValueObjects\Money;
use App\Modules\Escrow\Models\EscrowTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class EscrowInitiated
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public EscrowTransaction $transaction,
        public Money $amount,
        public string $userId,
    ) {}
}
