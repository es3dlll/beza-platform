<?php

declare(strict_types=1);

namespace App\Modules\Bills\Events;

use App\Modules\Bills\Models\Bill;
use App\Modules\Core\ValueObjects\Money;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class BillPaymentApproved
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Bill $bill,
        public Money $amount,
        public int $riskScore,
    ) {}
}
