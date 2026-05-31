<?php

declare(strict_types=1);

namespace App\Modules\Agent\Events;

use App\Modules\Agent\Models\Agent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class AgentRegistered
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Agent $agent,
    ) {}
}
