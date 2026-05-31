<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Events;

use App\Modules\Agent\Models\Agent;
use App\Modules\Core\ValueObjects\Money;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class LiquidityApproved
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Agent $agent,
        public Money $amount,
        public int $riskScore,
        public string $requestId,
    ) {}
}
