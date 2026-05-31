<?php

declare(strict_types=1);

namespace App\Modules\BillProvider\Events;

use App\Modules\BillProvider\Models\BillProvider;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class BillProviderDeactivated
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public BillProvider $provider,
    ) {}
}
