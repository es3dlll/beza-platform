<?php

declare(strict_types=1);

namespace App\Modules\Agent\Events;

use App\Modules\Agent\Models\Agent;
use App\Modules\Core\ValueObjects\Money;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class CommissionCalculated
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Agent $agent,
        public Money $transferAmount,
        public Money $commission,
        public string $clientType,
    ) {}
}
