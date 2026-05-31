<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Events;

use App\Modules\Core\ValueObjects\Money;
use App\Modules\Remittance\Models\Remittance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class RemittanceInitiated
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Remittance $remittance,
        public Money $amount,
        public string $fromCurrency,
        public string $toCurrency,
        public string $userId,
    ) {}
}
